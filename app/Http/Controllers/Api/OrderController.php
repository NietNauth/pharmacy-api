<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\PlaceOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Requests\Order\UpdatePaymentStatusRequest;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\VNPayService;
use App\Services\MomoService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;

    protected OrderService $orderService;
    protected VNPayService $vnpayService;
    protected MomoService $momoService;

    public function __construct(OrderService $orderService, VNPayService $vnpayService, MomoService $momoService)
    {
        $this->orderService = $orderService;
        $this->vnpayService = $vnpayService;
        $this->momoService = $momoService;
    }

    public function store(PlaceOrderRequest $request)
    {
        try {
            $orderDetail = $this->orderService->placeOrder($request->user(), $request->validated());
            $order = $orderDetail->resource;

            $responseData = [
                'order' => $orderDetail,
            ];

            if ($request->payment_method === 'vnpay') {
                $paymentUrl = $this->vnpayService->createPaymentUrl($order);
                if (!$paymentUrl) {
                    return $this->error('Không thể tạo liên kết thanh toán VNPay', 500);
                }
                $responseData['payment_url'] = $paymentUrl;
            } elseif ($request->payment_method === 'momo') {
                $paymentUrl = $this->momoService->createPaymentUrl($order);
                if (!$paymentUrl) {
                    return $this->error('Không thể tạo liên kết thanh toán MoMo', 500);
                }
                $responseData['payment_url'] = $paymentUrl;
            }

            return $this->success($responseData, 'Đặt hàng thành công', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function index(Request $request)
    {
        $query = Order::with('payment')
            ->where('user_id', $request->user()->id)
            ->latest('created_at');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(10);
        return $this->paginated(OrderResource::collection($orders));
    }

    public function show(Request $request, $orderCode)
    {
        $order = Order::with(['items.product.images', 'payment', 'coupon', 'branch'])
            ->where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return $this->success(new OrderDetailResource($order));
    }

    public function cancel(Request $request, $id)
    {
        try {
            $order = $this->orderService->cancelOrder($request->user(), $id);
            return $this->success(new OrderResource($order), 'Hủy đơn hàng thành công.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function retryPayment(Request $request, $id)
    {
        try {
            $order = Order::with('payment')
                ->where('id', $id)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            if ($order->status === 'cancelled') {
                return $this->error('Đơn hàng đã bị hủy, không thể thanh toán.', 400);
            }

            if (now()->diffInMinutes($order->created_at) >= 2 * 60) {
                return $this->error('Đã quá thời gian thanh toán (2 giờ). Vui lòng đặt đơn hàng mới.', 400);
            }

            $paymentStatus = is_object($order->payment->status) ? $order->payment->status->value : $order->payment->status;
            if ($paymentStatus === 'paid') {
                return $this->error('Đơn hàng này đã được thanh toán.', 400);
            }

            $currentMethod = is_object($order->payment->method) ? $order->payment->method->value : $order->payment->method;
            $paymentMethod = $request->input('payment_method', $currentMethod);

            $paymentUrl = null;
            if ($paymentMethod === 'vnpay') {
                $paymentUrl = $this->vnpayService->createPaymentUrl($order);
                if (!$paymentUrl) {
                    return $this->error('Không thể tạo liên kết thanh toán VNPay', 500);
                }
            } elseif ($paymentMethod === 'momo') {
                $paymentUrl = $this->momoService->createPaymentUrl($order);
                if (!$paymentUrl) {
                    return $this->error('Không thể tạo liên kết thanh toán MoMo', 500);
                }
            } else {
                return $this->error('Phương thức thanh toán không hỗ trợ.', 400);
            }

            if ($currentMethod !== $paymentMethod) {
                $order->payment->update(['method' => $paymentMethod]);
            }

            return $this->success(['payment_url' => $paymentUrl], 'Tạo liên kết thanh toán thành công.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function adminIndex(Request $request)
    {
        $user = $request->user();
        $query = Order::with(['payment', 'user', 'branch'])
            ->latest('created_at');

        // If pharmacist, only show orders from their branch
        if ($user->role === \App\Enums\UserRole::Pharmacist) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->paginate(20);
        return $this->paginated(OrderResource::collection($orders));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, $id)
    {
        try {
            $order = $this->orderService->updateStatus($id, $request->status);
            return $this->success(new OrderResource($order), 'Cập nhật trạng thái thành công.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function adminShow($id)
    {
        $order = Order::with(['items.product.images', 'payment', 'user', 'branch', 'coupon', 'histories'])
            ->findOrFail($id);

        return $this->success(new OrderDetailResource($order));
    }

    public function updatePaymentStatus(UpdatePaymentStatusRequest $request, $id)
    {
        try {
            $order = $this->orderService->updatePaymentStatus($id, $request->status, $request->transaction_id);
            return $this->success(new OrderDetailResource($order), 'Cập nhật trạng thái thanh toán thành công.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function vnpayReturn(Request $request)
    {
        $isValid = $this->vnpayService->validateResponse($request->all());
        
        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Chữ ký không hợp lệ'
            ], 400);
        }

        $vnp_ResponseCode = $request->vnp_ResponseCode;
        $vnp_TxnRef = $request->vnp_TxnRef;
        $orderCode = explode('_', $vnp_TxnRef)[0];
        
        $order = Order::where('order_code', $orderCode)->firstOrFail();

        if ($vnp_ResponseCode == "00") {
            $this->orderService->updatePaymentStatus($order->id, 'paid', $request->vnp_TransactionNo);
            
            return response()->json([
                'success' => true,
                'message' => 'Thanh toán thành công',
                'data' => new OrderDetailResource($order->fresh(['payment', 'items.product.images', 'user', 'branch', 'coupon']))
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Thanh toán thất bại',
                'data' => new OrderDetailResource($order->fresh(['payment', 'items.product.images', 'user', 'branch', 'coupon']))
            ]);
        }
    }

    public function vnpayIpn(Request $request)
    {
        $isValid = $this->vnpayService->validateResponse($request->all());
        if (!$isValid) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $vnp_ResponseCode = $request->vnp_ResponseCode;
        $vnp_TxnRef = $request->vnp_TxnRef;
        $orderCode = explode('_', $vnp_TxnRef)[0];
        
        $order = Order::with('payment')->where('order_code', $orderCode)->first();
        if (!$order) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        $paymentStatus = is_object($order->payment->status) ? $order->payment->status->value : $order->payment->status;
        if ($paymentStatus !== 'pending') {
            return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        }

        if ($vnp_ResponseCode == "00") {
            $this->orderService->updatePaymentStatus($order->id, 'paid', $request->vnp_TransactionNo);
        }

        return response()->json(['RspCode' => '00', 'Message' => 'Confirm success']);
    }

    public function momoReturn(Request $request)
    {
        $isValid = $this->momoService->validateResponse($request->all());
        
        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Chữ ký không hợp lệ'
            ], 400);
        }

        $resultCode = $request->resultCode;
        $orderId = $request->orderId;
        $orderCode = explode('_', $orderId)[0];
        
        $order = Order::where('order_code', $orderCode)->firstOrFail();

        if ($resultCode == "0") {
            $this->orderService->updatePaymentStatus($order->id, 'paid', $request->transId);
            
            return response()->json([
                'success' => true,
                'message' => 'Thanh toán thành công',
                'data' => new OrderDetailResource($order->fresh(['payment', 'items.product.images', 'user', 'branch', 'coupon']))
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Thanh toán thất bại: ' . $request->message,
                'data' => new OrderDetailResource($order->fresh(['payment', 'items.product.images', 'user', 'branch', 'coupon']))
            ]);
        }
    }

    public function momoIpn(Request $request)
    {
        $isValid = $this->momoService->validateResponse($request->all());
        if (!$isValid) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        $resultCode = $request->resultCode;
        $orderId = $request->orderId;
        $orderCode = explode('_', $orderId)[0];
        
        $order = Order::with('payment')->where('order_code', $orderCode)->first();
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $paymentStatus = is_object($order->payment->status) ? $order->payment->status->value : $order->payment->status;
        if ($paymentStatus !== 'pending') {
            return response()->json(['success' => true, 'message' => 'Order already confirmed']);
        }

        if ($resultCode == "0") {
            $this->orderService->updatePaymentStatus($order->id, 'paid', $request->transId);
        }

        return response()->json(['success' => true]);
    }
}
