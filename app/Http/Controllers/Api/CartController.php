<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidPrescriptionException;
use App\Exceptions\PrescriptionRequiredException;
use App\Exceptions\ProductNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponse;

    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index(Request $request)
    {
        $cart = $this->cartService->getCart($request->user());
        return $this->success(new CartResource($cart));
    }

    public function addItem(AddToCartRequest $request)
    {
        try {
            $cart = $this->cartService->addItem($request->user(), $request->validated());
            return $this->success(new CartResource($cart), 'Đã thêm vào giỏ hàng.', 201);
        } catch (ProductNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (InsufficientStockException|PrescriptionRequiredException|InvalidPrescriptionException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->error('Đã xảy ra lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function updateItem(UpdateCartItemRequest $request, $itemId)
    {
        try {
            $cart = $this->cartService->updateItem($request->user(), $itemId, $request->quantity);
            return $this->success(new CartResource($cart), 'Cập nhật giỏ hàng thành công.');
        } catch (ProductNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (InsufficientStockException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->error('Đã xảy ra lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function removeItem(Request $request, $itemId)
    {
        try {
            $cart = $this->cartService->removeItem($request->user(), $itemId);
            return $this->success(new CartResource($cart), 'Đã xóa sản phẩm khỏi giỏ hàng.');
        } catch (\Exception $e) {
            return $this->error('Đã xảy ra lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function clear(Request $request)
    {
        try {
            $cart = $this->cartService->clearCart($request->user());
            return $this->success(new CartResource($cart), 'Đã xóa toàn bộ giỏ hàng.');
        } catch (\Exception $e) {
            return $this->error('Đã xảy ra lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function merge(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.prescription_id' => 'nullable|exists:prescriptions,id',
        ]);

        try {
            $cart = $this->cartService->mergeCart($request->user(), $request->input('items'));
            return $this->success(new CartResource($cart), 'Đồng bộ giỏ hàng thành công.');
        } catch (\Exception $e) {
            return $this->error('Đã xảy ra lỗi khi đồng bộ giỏ hàng: ' . $e->getMessage(), 500);
        }
    }
}
