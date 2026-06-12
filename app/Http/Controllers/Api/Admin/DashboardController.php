<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function stats(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();
        $requestedBranchId = $request->get('branch_id');
        
        $orderQuery = Order::query();

        // Branch filtering logic
        if ($user->role === \App\Enums\UserRole::Pharmacist) {
            $orderQuery->where('branch_id', $user->branch_id);
        } elseif ($user->role === \App\Enums\UserRole::Admin && $requestedBranchId) {
            $orderQuery->where('branch_id', $requestedBranchId);
        }

        $totalOrders = $orderQuery->count();
        $todayOrders = (clone $orderQuery)->whereDate('created_at', $today)->count();
                             
        $totalUsers = User::where('role', 'customer')->count();
        $totalActiveProducts = Product::where('status', 'active')->count();

        $recentOrdersQuery = Order::with(['user'])->latest();
        if ($user->role === \App\Enums\UserRole::Pharmacist) {
            $recentOrdersQuery->where('branch_id', $user->branch_id);
        } elseif ($user->role === \App\Enums\UserRole::Admin && $requestedBranchId) {
            $recentOrdersQuery->where('branch_id', $requestedBranchId);
        }
        $recentOrders = $recentOrdersQuery->take(5)->get();

        // Operational counts
        $pendingOrders = (clone $orderQuery)->whereIn('status', ['pending', 'confirmed'])->count();
        $pendingPrescriptions = \App\Models\Prescription::where('status', 'pending')
            ->when($user->role === \App\Enums\UserRole::Pharmacist, function($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })
            ->when($user->role === \App\Enums\UserRole::Admin && $requestedBranchId, function($q) use ($requestedBranchId) {
                $q->where('branch_id', $requestedBranchId);
            })
            ->count();

        $lowStockProducts = \App\Models\Inventory::where('quantity_available', '<=', 10)
            ->when($user->role === \App\Enums\UserRole::Pharmacist, function($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })
            ->when($user->role === \App\Enums\UserRole::Admin && $requestedBranchId, function($q) use ($requestedBranchId) {
                $q->where('branch_id', $requestedBranchId);
            })->count();
            
        $newCustomersToday = User::where('role', 'customer')->whereDate('created_at', Carbon::today())->count();

        return $this->success([
            'orders' => [
                'total' => $totalOrders,
                'today' => $todayOrders,
                'pending' => $pendingOrders,
            ],
            'prescriptions' => [
                'pending' => $pendingPrescriptions,
            ],
            'products' => [
                'total_active' => $totalActiveProducts,
                'low_stock' => $lowStockProducts,
            ],
            'users' => [
                'total_customers' => $totalUsers,
                'new_today' => $newCustomersToday,
            ],
            'recent_orders' => $recentOrders,
        ]);
    }

    public function analytics(Request $request)
    {
        $type = $request->get('type', 'month'); // day, month, year
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $requestedBranchId = $request->get('branch_id');
        $queryDate = Carbon::parse($date);
        
        $orderQuery = Order::where('status', 'delivered');
        $user = $request->user();
        
        // Branch filtering logic
        if ($user->role === \App\Enums\UserRole::Pharmacist) {
            $orderQuery->where('branch_id', $user->branch_id);
        } elseif ($user->role === \App\Enums\UserRole::Admin && $requestedBranchId) {
            $orderQuery->where('branch_id', $requestedBranchId);
        }

        if ($type === 'day') {
            // Show last 7 days
            $startDate = (clone $queryDate)->subDays(6);
            $orderQuery->whereBetween('created_at', [$startDate->startOfDay(), $queryDate->endOfDay()]);
            
            $orders = (clone $orderQuery)
                ->selectRaw('DATE(created_at) as time, SUM(total) as revenue, COUNT(*) as orders_count')
                ->groupBy('time')->get();
                
            for ($i = 0; $i < 7; $i++) {
                $current = (clone $startDate)->addDays($i);
                $found = $orders->firstWhere('time', $current->format('Y-m-d'));
                $chartData[] = [
                    'label' => $current->format('d/m'),
                    'revenue' => $found ? (float)$found->revenue : 0,
                    'orders' => $found ? (int)$found->orders_count : 0,
                ];
            }
        } elseif ($type === 'month') {
            // Show 12 months of the year
            $orderQuery->whereYear('created_at', $queryDate->year);
            
            $orders = (clone $orderQuery)
                ->selectRaw('MONTH(created_at) as time, SUM(total) as revenue, COUNT(*) as orders_count')
                ->groupBy('time')->get();
                
            for ($i = 1; $i <= 12; $i++) {
                $found = $orders->firstWhere('time', $i);
                $chartData[] = [
                    'label' => 'T' . $i,
                    'revenue' => $found ? (float)$found->revenue : 0,
                    'orders' => $found ? (int)$found->orders_count : 0,
                ];
            }
        } elseif ($type === 'year') {
            // Show last 10 years
            $startYear = $queryDate->year - 9;
            $orderQuery->whereYear('created_at', '>=', $startYear);
            
            $orders = (clone $orderQuery)
                ->selectRaw('YEAR(created_at) as time, SUM(total) as revenue, COUNT(*) as orders_count')
                ->groupBy('time')->get();
                
            for ($i = 0; $i < 10; $i++) {
                $year = $startYear + $i;
                $found = $orders->firstWhere('time', $year);
                $chartData[] = [
                    'label' => (string)$year,
                    'revenue' => $found ? (float)$found->revenue : 0,
                    'orders' => $found ? (int)$found->orders_count : 0,
                ];
            }
        }

        $totalOrders = (clone $orderQuery)->count();
        $totalRevenue = (clone $orderQuery)->sum('total');
        
        $orderIds = (clone $orderQuery)->pluck('id');
        $totalProductsSold = \App\Models\OrderItem::whereIn('order_id', $orderIds)->sum('quantity');

        $topProducts = \App\Models\OrderItem::whereIn('order_id', $orderIds)
            ->select('product_id', \Illuminate\Support\Facades\DB::raw('SUM(quantity) as total_quantity'), \Illuminate\Support\Facades\DB::raw('SUM(subtotal) as total_revenue'))
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->with(['product' => function($query) {
                $query->select('id', 'name', 'slug', 'base_price', 'sale_price', 'unit')->with('images');
            }])
            ->take(10)
            ->get()
            ->map(function ($item) {
                if ($item->product) {
                    $item->product->append('primary_image');
                    $item->product->makeHidden('images');
                }
                return $item;
            });

        return $this->success([
            'sales' => [
                'total_orders' => $totalOrders,
                'total_revenue' => (float) $totalRevenue,
                'total_products_sold' => (int) $totalProductsSold,
            ],
            'chart_data' => $chartData,
            'top_products' => $topProducts
        ]);
    }
}
