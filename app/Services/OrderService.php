<?php

namespace App\Services;

use App\Exceptions\CouponExpiredException;
use App\Exceptions\CouponMaxUsesException;
use App\Exceptions\CouponMinOrderException;
use App\Exceptions\EmptyCartException;
use App\Exceptions\NoBranchAvailableException;
use App\Exceptions\InsufficientStockException;
use App\Http\Resources\OrderDetailResource;

use App\Models\Branch;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\User;
use App\Models\UserAddress;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class OrderService
{
    public function placeOrder(User $user, array $data)
    {
        $userAddress = null;
        // Populate address details if address_id is provided
        if (isset($data['address_id'])) {
            $userAddress = UserAddress::where('id', $data['address_id'])->where('user_id', $user->id)->first();
            if ($userAddress) {
                $data['delivery_address'] = $userAddress->address_line . ', ' . $userAddress->district . ', ' . $userAddress->city;
                $data['recipient_name'] = $userAddress->recipient_name ?? $user->full_name;
                $data['recipient_phone'] = $userAddress->recipient_phone ?? $user->phone;
            }
        }

        $cart = $user->carts()->with('items.product')->first();
        if (!$cart || $cart->items->isEmpty()) {
            throw new EmptyCartException('Giỏ hàng của bạn đang trống.');
        }

        $items = $cart->items;
        foreach ($items as $item) {
            $product = $item->product;
            if (!$product || (is_object($product->status) ? $product->status->value !== 'active' : $product->status !== 'active')) {
                throw new \Exception("Sản phẩm {$product->name} không khả dụng.");
            }
        }

        $branchId = null;
        if ($data['delivery_type'] === 'pickup') {
            $branchId = $data['branch_id'];
            foreach ($items as $item) {
                $stock = Inventory::where('branch_id', $branchId)
                    ->where('product_id', $item->product_id)
                    ->sum(DB::raw('quantity_available - quantity_reserved'));
                if ($stock < $item->quantity) {
                    throw new InsufficientStockException("Chi nhánh không đủ tồn kho cho sản phẩm {$item->product->name}.");
                }
            }
        } else {
            $branches = Branch::where('is_active', true)->get();
            $availableBranches = [];
            foreach ($branches as $branch) {
                $hasStock = true;
                foreach ($items as $item) {
                    $stock = Inventory::where('branch_id', $branch->id)
                        ->where('product_id', $item->product_id)
                        ->sum(DB::raw('quantity_available - quantity_reserved'));
                    if ($stock < $item->quantity) {
                        $hasStock = false;
                        break;
                    }
                }
                if ($hasStock) {
                    $availableBranches[] = $branch;
                }
            }

            if (empty($availableBranches)) {
                throw new NoBranchAvailableException('Không có hệ thống cửa hàng nào đủ tồn kho cho toàn bộ đơn hàng của bạn.');
            }

            // Find the nearest branch among those with stock
            if ($userAddress && $userAddress->lat && $userAddress->lng) {
                $minDistance = INF;
                foreach ($availableBranches as $branch) {
                    if ($branch->lat && $branch->lng) {
                        $dist = $this->calculateDistance(
                            (float)$branch->lat, (float)$branch->lng,
                            (float)$userAddress->lat, (float)$userAddress->lng
                        );
                        if ($dist < $minDistance) {
                            $minDistance = $dist;
                            $branchId = $branch->id;
                        }
                    }
                }
            }

            // Fallbacks if lat/lng is missing or no branch has lat/lng
            if (!$branchId) {
                if ($userAddress) {
                    // Try to match by district & city
                    foreach ($availableBranches as $branch) {
                        if ($branch->city === $userAddress->city && $branch->district === $userAddress->district) {
                            $branchId = $branch->id;
                            break;
                        }
                    }
                    if (!$branchId) {
                        // Try to match by city
                        foreach ($availableBranches as $branch) {
                            if ($branch->city === $userAddress->city) {
                                $branchId = $branch->id;
                                break;
                            }
                        }
                    }
                }

                // If still not set, default to first available branch
                if (!$branchId) {
                    $branchId = $availableBranches[0]->id;
                }
            }
        }

        $subtotal = $items->sum(function ($item) {
            return $item->price_snapshot * $item->quantity;
        });

        $couponId = null;
        $discountAmount = 0;
        $couponCode = $data['coupon_code'] ?? null;

        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            if (!$coupon || !$coupon->is_active) {
                throw new \Exception('Mã giảm giá không tồn tại hoặc không hoạt động.');
            }

            $today = now();
            if ($today < $coupon->valid_from || $today > $coupon->valid_until) {
                throw new CouponExpiredException('Mã giảm giá đã hết hạn hoặc chưa đến thời gian sử dụng.');
            }

            if ($coupon->used_count >= $coupon->max_uses) {
                throw new CouponMaxUsesException('Mã giảm giá đã hết lượt sử dụng.');
            }

            if ($subtotal < $coupon->min_order_value) {
                throw new CouponMinOrderException("Giá trị đơn hàng tối thiểu để dùng mã này là {$coupon->min_order_value}.");
            }

            $couponId = $coupon->id;
            $type = is_object($coupon->discount_type) ? $coupon->discount_type->value : $coupon->discount_type;
            if ($type === 'percent') {
                $calc = $subtotal * ($coupon->discount_value / 100);
                if ($coupon->max_discount) {
                    $calc = min($calc, $coupon->max_discount);
                }
                $discountAmount = $calc;
            } else {
                $discountAmount = $coupon->discount_value;
            }
        }

        $shippingFee = 0;
        $FREE_SHIPPING_THRESHOLD = 500000;

        if ($data['delivery_type'] !== 'pickup' && $subtotal < $FREE_SHIPPING_THRESHOLD) {
            $branch = Branch::find($branchId);
            $userAddress = isset($data['address_id']) ? UserAddress::find($data['address_id']) : null;

            if ($data['delivery_type'] === 'express') {
                if ($branch && $userAddress && $branch->lat && $branch->lng && $userAddress->lat && $userAddress->lng) {
                    $distance = $this->calculateDistance(
                        (float)$branch->lat, (float)$branch->lng,
                        (float)$userAddress->lat, (float)$userAddress->lng
                    );
                    
                    if ($distance < 2) {
                        $shippingFee = 0;
                    } else {
                        $fee = ($distance * 5000) + 5000 + 20000;
                        $shippingFee = max(35000, round($fee / 1000) * 1000);
                    }
                } else {
                    $shippingFee = 50000;
                }
            } else {
                // Standard shipping logic
                if ($branch && $userAddress) {
                    if ($branch->city === $userAddress->city && $branch->district === $userAddress->district) {
                        $shippingFee = 20000;
                    } elseif ($branch->city === $userAddress->city) {
                        $shippingFee = 30000;
                    } else {
                        $shippingFee = 40000;
                    }
                } else {
                    $shippingFee = 30000;
                }
            }
        }

        $total = $subtotal - $discountAmount + $shippingFee;

        DB::beginTransaction();
        try {
            // Re-verify stock with lock INSIDE transaction
            foreach ($items as $item) {
                $inventory = Inventory::where('branch_id', $branchId)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $availableStock = $inventory ? ($inventory->quantity_available - $inventory->quantity_reserved) : 0;
                if ($availableStock < $item->quantity) {
                    throw new InsufficientStockException("Sản phẩm {$item->product->name} đã hết hàng hoặc không đủ tồn kho tại chi nhánh này.");
                }
            }

            // Re-verify and lock coupon
            if ($couponId) {
                $coupon = Coupon::where('id', $couponId)->lockForUpdate()->first();
                if (!$coupon || !$coupon->is_active || $coupon->used_count >= $coupon->max_uses) {
                    throw new \Exception('Mã giảm giá đã hết lượt sử dụng hoặc không khả dụng.');
                }
            }

            // Generate robust order code (Atomic via retry logic or unique prefix)
            $orderCode = $this->generateUniqueOrderCode();

            $order = Order::create([
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'order_code' => $orderCode,
                'status' => 'pending',
                'delivery_type' => $data['delivery_type'],
                'delivery_address' => $data['delivery_address'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? null,
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_fee' => $shippingFee,
                'total' => max($total, 0),
                'coupon_id' => $couponId,
                'note' => $data['note'] ?? null,
                'created_at' => now(),
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'prescription_id' => $item->prescription_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->price_snapshot,
                    'subtotal' => $item->price_snapshot * $item->quantity,
                ]);

                // We already have $inventory from the lock above, but let's re-fetch to be safe or use it
                $inventory = Inventory::where('branch_id', $branchId)
                    ->where('product_id', $item->product_id)
                    ->first();
                    
                if ($inventory) {
                    $inventory->increment('quantity_reserved', $item->quantity);

                    InventoryLog::create([
                        'inventory_id' => $inventory->id,
                        'actor_id' => $user->id,
                        'action_type' => 'reserved',
                        'quantity_delta' => $item->quantity,
                        'quantity_after' => $inventory->quantity_available - $inventory->quantity_reserved,
                        'note' => 'Đặt hàng',
                        'ref_order_id' => $order->id,
                    ]);
                }
            }

            $order->payment()->create([
                'method' => $data['payment_method'],
                'status' => 'pending',
                'amount' => max($total, 0),
            ]);

            if ($couponId) {
                $coupon->increment('used_count');
            }

            $cart->items()->delete();

            $this->recordHistory($order, 'pending', 'Đặt hàng thành công', $user->id);

            DB::commit();



            $order->load(['items.product.images', 'payment', 'coupon', 'branch']);

            // Notify staff
            $staff = User::getStaff($order->branch_id);
            Notification::send($staff, new SystemNotification(
                'Đơn hàng mới',
                "Đơn hàng {$order->order_code} đã được đặt thành công.",
                "/orders/{$order->id}",
                'success'
            ));

            return new OrderDetailResource($order);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function cancelOrder(User $user, $orderId)
    {
        $order = Order::where('id', $orderId)->where('user_id', $user->id)->firstOrFail();

        $statusValue = is_object($order->status) ? $order->status->value : $order->status;
        if (!in_array($statusValue, ['pending', 'confirmed'])) {
            throw new \Exception('Không thể hủy đơn hàng này.');
        }

        DB::beginTransaction();
        try {
            $order->update(['status' => 'cancelled']);
            $this->recordHistory($order, 'cancelled', 'Khách hàng hủy đơn hàng', $user->id);

            foreach ($order->items as $item) {
                $inventory = Inventory::where('branch_id', $order->branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($inventory) {
                    $inventory->decrement('quantity_reserved', $item->quantity);

                    InventoryLog::create([
                        'inventory_id' => $inventory->id,
                        'actor_id' => $user->id,
                        'action_type' => 'released',
                        'quantity_delta' => -$item->quantity,
                        'quantity_after' => $inventory->quantity_available - $inventory->quantity_reserved,
                        'note' => 'Hủy đơn hàng',
                        'ref_order_id' => $order->id,
                    ]);
                }
            }

            if ($order->coupon_id) {
                Coupon::where('id', $order->coupon_id)->decrement('used_count');
            }

            DB::commit();

            // Notify staff
            $staff = User::getStaff($order->branch_id);
            Notification::send($staff, new SystemNotification(
                'Đơn hàng bị hủy',
                "Khách hàng đã hủy đơn hàng {$order->order_code}.",
                "/orders/{$order->id}",
                'warning'
            ));

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateStatus($orderId, $newStatus)
    {
        $order = Order::findOrFail($orderId);

        DB::beginTransaction();
        try {
            $statusValue = is_object($newStatus) ? $newStatus->value : $newStatus;
            
            $order->update(['status' => $newStatus]);
            $this->recordHistory($order, $statusValue, null, auth()->id());
            
            if ($statusValue === 'delivered') {
                foreach ($order->items as $item) {
                    $inventory = Inventory::where('branch_id', $order->branch_id)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if ($inventory) {
                        $inventory->decrement('quantity_available', $item->quantity);
                        $inventory->decrement('quantity_reserved', $item->quantity);

                        InventoryLog::create([
                            'inventory_id' => $inventory->id,
                            'actor_id' => auth()->id() ?? User::where('role', 'admin')->first()->id,
                            'action_type' => 'sale',
                            'quantity_delta' => -$item->quantity,
                            'quantity_after' => $inventory->quantity_available - $inventory->quantity_reserved,
                            'note' => 'Giao hàng thành công',
                            'ref_order_id' => $order->id,
                        ]);
                        if ($inventory->quantity_available <= $inventory->quantity_minimum) {
                            $staff = User::getStaff($order->branch_id);
                            Notification::send($staff, new SystemNotification(
                                'Cảnh báo hết hàng',
                                "Sản phẩm {$item->product->name} tại chi nhánh {$order->branch->name} sắp hết hàng.",
                                "/inventory?branch_id={$order->branch_id}&is_low_stock=true",
                                'warning'
                            ));
                        }
                    }
                }
            }

            DB::commit();

            // Notify customer
            $statusLabel = \App\Enums\OrderStatus::from($statusValue)->label();
            $order->user->notify(new SystemNotification(
                'Cập nhật đơn hàng',
                "Đơn hàng {$order->order_code} của bạn đã chuyển sang trạng thái: " . $statusLabel,
                "/orders/{$order->order_code}",
                'info'
            ));

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updatePaymentStatus($orderId, $newStatus, $transactionId = null)
    {
        $order = Order::with('payment')->findOrFail($orderId);
        $payment = $order->payment;
        
        if (!$payment) {
            throw new \Exception('Không tìm thấy thông tin thanh toán cho đơn hàng này.');
        }

        $data = ['status' => $newStatus];
        if ($newStatus === 'paid' || (is_object($newStatus) && $newStatus->value === 'paid')) {
            $data['paid_at'] = now();
            if ($transactionId) {
                $data['transaction_id'] = $transactionId;
            }
        }

        $payment->update($data);
        
        return $order->fresh(['payment', 'items.product.images', 'user', 'branch', 'coupon']);
    }

    protected function recordHistory(Order $order, $status, $note = null, $actorId = null)
    {
        $statusValue = is_object($status) ? $status->value : $status;
        
        $order->histories()->create([
            'status' => $statusValue,
            'note' => $note,
            'actor_id' => $actorId,
            'created_at' => now(),
        ]);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }

    private function generateUniqueOrderCode()
    {
        $prefix = 'ORD-' . date('Ymd');
        
        // Use a loop to ensure uniqueness in case of extreme concurrency
        for ($i = 0; $i < 5; $i++) {
            $todayStart = now()->startOfDay();
            $todayEnd = now()->endOfDay();
            $sequence = Order::whereBetween('created_at', [$todayStart, $todayEnd])->count() + 1;
            $code = $prefix . '-' . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);
            
            if (!Order::where('order_code', $code)->exists()) {
                return $code;
            }
            // If exists, wait a bit or let the loop try again with updated count
            usleep(10000); 
        }

        // Fallback to random if sequence logic fails
        return $prefix . '-' . strtoupper(\Illuminate\Support\Str::random(4));
    }
}
