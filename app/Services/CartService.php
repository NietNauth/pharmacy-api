<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidPrescriptionException;
use App\Exceptions\PrescriptionRequiredException;
use App\Exceptions\ProductNotFoundException;
use App\Models\Cart;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\User;

class CartService
{
    public function getCart(User $user)
    {
        $cart = $user->carts()->firstOrCreate([]);
        
        $cart->load(['items.product.images' => function ($query) {
            $query->where('is_primary', true);
        }, 'items.prescription']);
        
        return $cart;
    }

    public function addItem(User $user, array $data)
    {
        $product = Product::with('inventory')->find($data['product_id']);
        
        if (!$product || (is_object($product->status) ? $product->status->value !== 'active' : $product->status !== 'active')) {
            throw new ProductNotFoundException('Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.');
        }

        if ($product->requires_prescription) {
            if (empty($data['prescription_id'])) {
                throw new PrescriptionRequiredException('Sản phẩm yêu cầu phải có đơn thuốc.');
            }

            $prescription = Prescription::find($data['prescription_id']);
            
            if (!$prescription || $prescription->user_id !== $user->id || (is_object($prescription->status) ? $prescription->status->value !== 'approved' : $prescription->status !== 'approved')) {
                throw new InvalidPrescriptionException('Đơn thuốc không hợp lệ hoặc chưa được duyệt.');
            }
        }

        $cart = $this->getCart($user);
        $existingItem = $cart->items()->where('product_id', $product->id)->first();
        
        $requestedQuantity = $data['quantity'];
        if ($existingItem) {
            $requestedQuantity += $existingItem->quantity;
        }

        $availableStock = $product->inventory->sum(function ($inv) {
            return $inv->quantity_available - $inv->quantity_reserved;
        });

        if ($availableStock < $requestedQuantity) {
            throw new InsufficientStockException('Số lượng tồn kho không đủ.');
        }

        $currentPrice = $product->sale_price ?? $product->base_price;

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $requestedQuantity,
                'price_snapshot' => $currentPrice,
                'prescription_id' => $data['prescription_id'] ?? $existingItem->prescription_id,
            ]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $requestedQuantity,
                'price_snapshot' => $currentPrice,
                'prescription_id' => $data['prescription_id'] ?? null,
            ]);
        }

        return $this->getCart($user);
    }

    public function updateItem(User $user, $itemId, int $quantity)
    {
        $cart = $this->getCart($user);
        $item = $cart->items()->where('id', $itemId)->first();

        if (!$item) {
            throw new ProductNotFoundException('Sản phẩm không có trong giỏ hàng.');
        }

        $product = $item->product;
        $availableStock = $product->inventory->sum(function ($inv) {
            return $inv->quantity_available - $inv->quantity_reserved;
        });

        if ($availableStock < $quantity) {
            throw new InsufficientStockException('Số lượng tồn kho không đủ.');
        }

        $item->update(['quantity' => $quantity]);

        return $this->getCart($user);
    }

    public function removeItem(User $user, $itemId)
    {
        $cart = $this->getCart($user);
        $item = $cart->items()->where('id', $itemId)->first();

        if ($item) {
            $item->delete();
        }

        return $this->getCart($user);
    }

    public function clearCart(User $user)
    {
        $cart = $this->getCart($user);
        $cart->items()->delete();

        return $this->getCart($user);
    }

    public function mergeCart(User $user, array $items)
    {
        foreach ($items as $item) {
            try {
                $this->addItem($user, [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'prescription_id' => $item['prescription_id'] ?? null
                ]);
            } catch (\Exception $e) {
                // Ignore individual item failures so that other items can still merge successfully
                continue;
            }
        }
        return $this->getCart($user);
    }
}
