<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Http\Resources\InventoryLogResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Inventory::with(['product', 'branch']);

        // If pharmacist, only show inventory for their branch
        if ($user->role === \App\Enums\UserRole::Pharmacist) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('is_low_stock')) {
            $isLowOption = filter_var($request->is_low_stock, FILTER_VALIDATE_BOOLEAN);
            if ($isLowOption) {
                $query->whereColumn('quantity_available', '<=', 'quantity_minimum');
            } else {
                $query->whereColumn('quantity_available', '>', 'quantity_minimum');
            }
        }

        $inventories = $query->paginate(20);

        return $this->paginated(InventoryResource::collection($inventories));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity_available' => ['nullable', 'integer', 'min:0'],
            'quantity_minimum' => ['nullable', 'integer', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $inventory = Inventory::findOrFail($id);

        // Check if pharmacist belongs to this branch
        if ($request->user()->role === \App\Enums\UserRole::Pharmacist && $inventory->branch_id !== $request->user()->branch_id) {
            return $this->error('Bạn không có quyền cập nhật tồn kho của chi nhánh khác.', 403);
        }

        $oldQuantity = $inventory->quantity_available;

        DB::beginTransaction();
        try {
            if (isset($validated['quantity_available'])) {
                $inventory->quantity_available = $validated['quantity_available'];
            }
            if (isset($validated['quantity_minimum'])) {
                $inventory->quantity_minimum = $validated['quantity_minimum'];
            }
            if (array_key_exists('expiry_date', $validated)) {
                $inventory->expiry_date = $validated['expiry_date'];
            }

            $inventory->save();

            if (isset($validated['quantity_available']) && $validated['quantity_available'] != $oldQuantity) {
                InventoryLog::create([
                    'inventory_id' => $inventory->id,
                    'actor_id' => $request->user()->id,
                    'action_type' => 'adjustment',
                    'quantity_delta' => $validated['quantity_available'] - $oldQuantity,
                    'quantity_after' => $validated['quantity_available'] - $inventory->quantity_reserved,
                    'note' => $validated['note'] ?? 'Điều chỉnh tồn kho thủ công',
                    'ref_order_id' => null,
                ]);
            }

            DB::commit();

            $inventory->load(['product', 'branch']);

            return $this->success(new InventoryResource($inventory), 'Cập nhật tồn kho thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Lỗi khi cập nhật tồn kho: ' . $e->getMessage(), 500);
        }
    }

    public function lowStock(Request $request)
    {
        $user = $request->user();
        $query = Inventory::with(['product', 'branch'])
            ->whereColumn('quantity_available', '<=', 'quantity_minimum');

        if ($user->role === \App\Enums\UserRole::Pharmacist) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $inventories = $query->get();

        return $this->success(InventoryResource::collection($inventories));
    }

    public function logs(Request $request, $id)
    {
        $inventory = Inventory::findOrFail($id);

        // Check if pharmacist belongs to this branch
        if ($request->user()->role === \App\Enums\UserRole::Pharmacist && $inventory->branch_id !== $request->user()->branch_id) {
            return $this->error('Bạn không có quyền xem nhật ký tồn kho của chi nhánh khác.', 403);
        }

        $query = $inventory->logs()->with('actor');

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(10);

        return $this->paginated(InventoryLogResource::collection($logs));
    }

    public function restock(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'quantity.required' => 'Vui lòng nhập số lượng.',
            'quantity.integer' => 'Số lượng phải là số nguyên.',
            'quantity.min' => 'Số lượng nhập phải ít nhất là 1.',
        ]);

        $inventory = Inventory::findOrFail($id);
        
        $user = $request->user();
        if ($user->role === \App\Enums\UserRole::Pharmacist && $inventory->branch_id !== $user->branch_id) {
            return $this->error('Bạn không có quyền nhập kho cho chi nhánh khác.', 403);
        }

        DB::beginTransaction();
        try {
            $inventory->quantity_available += $validated['quantity'];
            $inventory->save();

            $inventory->logs()->create([
                'actor_id' => $user->id,
                'action_type' => 'restock',
                'quantity_delta' => $validated['quantity'],
                'quantity_after' => $inventory->quantity_available,
                'note' => $validated['note'] ?? 'Nhập kho bổ sung',
            ]);

            DB::commit();
            
            $inventory->load(['product', 'branch']);
            return $this->success(new InventoryResource($inventory), 'Nhập kho thành công');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Restock Error: ' . $e->getMessage(), [
                'inventory_id' => $id,
                'user_id' => $user->id,
                'exception' => $e
            ]);
            return $this->error('Lỗi khi nhập kho: ' . $e->getMessage(), 500);
        }
    }
}
