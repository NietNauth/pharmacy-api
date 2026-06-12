<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\QaController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\SlideController;

Route::prefix('v1')->group(function () {
    // Auth
    Route::prefix('auth')->group(function () {
        // Public
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/forgot-password', [PasswordResetController::class, 'sendLink']);
        Route::post('/reset-password', [PasswordResetController::class, 'reset']);
        Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify');

        // Private
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
            Route::post('/resend-verification', [EmailVerificationController::class, 'resend']);
            
            // Addresses
            Route::get('/addresses', [\App\Http\Controllers\Api\Auth\UserAddressController::class, 'index']);
            Route::post('/addresses', [\App\Http\Controllers\Api\Auth\UserAddressController::class, 'store']);
            Route::put('/addresses/{id}', [\App\Http\Controllers\Api\Auth\UserAddressController::class, 'update']);
            Route::delete('/addresses/{id}', [\App\Http\Controllers\Api\Auth\UserAddressController::class, 'destroy']);
        });
    });

    // Public
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);
    Route::get('/products/{id}/qas', [QaController::class, 'index']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}/children', [CategoryController::class, 'children']);
    Route::get('/brands', [BrandController::class, 'index']);
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/slides', [SlideController::class, 'index']);
    
    // Public chatbot
    Route::post('/chatbot/message', [ChatbotController::class, 'message']);
    Route::get('/chatbot/history', [ChatbotController::class, 'history']);
    Route::delete('/chatbot/history', [ChatbotController::class, 'clearHistory']);
    Route::get('/chatbot/trending', [ChatbotController::class, 'trending']);
    Route::get('/chatbot/settings', [ChatbotController::class, 'settings']);
    Route::post('/chatbot/feedback/{messageId}', [ChatbotController::class, 'feedback']);

    // Pharmacist rules & Admin (Placed before Customer rules to avoid {id} conflict)
    Route::middleware(['auth:sanctum', 'role:pharmacist,admin'])->group(function () {
        Route::get('/prescriptions/pending', [PrescriptionController::class, 'pending']);
        Route::post('/prescriptions/{id}/review', [PrescriptionController::class, 'review']);
        Route::post('/prescriptions/{id}/assign', [PrescriptionController::class, 'assign']);
        
        Route::get('/inventory', [InventoryController::class, 'index']);
        Route::put('/inventory/{id}', [InventoryController::class, 'update']);
        Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock']);
        Route::get('/inventory/{id}/logs', [InventoryController::class, 'logs']);
        Route::post('/inventory/{id}/restock', [InventoryController::class, 'restock']);
    });

    // Customer rules
    Route::middleware(['auth:sanctum', 'role:customer,pharmacist,admin'])->group(function () {
        // Prescriptions
        Route::get('/prescriptions', [PrescriptionController::class, 'index']);
        Route::post('/prescriptions', [PrescriptionController::class, 'store']);
        Route::get('/prescriptions/{id}', [PrescriptionController::class, 'show']);

        // Cart
        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart/items', [CartController::class, 'addItem']);
        Route::post('/cart/merge', [CartController::class, 'merge']);
        Route::put('/cart/items/{id}', [CartController::class, 'updateItem']);
        Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);
        Route::delete('/cart', [CartController::class, 'clear']);

        // Coupon
        Route::post('/coupons/check', [CouponController::class, 'check']);

        // Orders
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{orderCode}', [OrderController::class, 'show']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
        Route::post('/orders/{id}/retry-payment', [OrderController::class, 'retryPayment']);

        // VNPay Callbacks
        Route::get('/vnpay-return', [OrderController::class, 'vnpayReturn']);
        Route::any('/vnpay-ipn', [OrderController::class, 'vnpayIpn']);

        // MoMo Callbacks
        Route::get('/momo-return', [OrderController::class, 'momoReturn']);
        Route::any('/momo-ipn', [OrderController::class, 'momoIpn']);

        // Q&As
        Route::post('/products/{id}/qas', [QaController::class, 'store']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);

        // Chat
        Route::get('/chat', [ChatController::class, 'index']);
        Route::post('/chat/start', [ChatController::class, 'start']);
        Route::get('/chat/{id}', [ChatController::class, 'show']);
        Route::post('/chat/{id}/send', [ChatController::class, 'send']);
    });

    // Admin & Pharmacist shared rules
    Route::prefix('admin')->middleware(['auth:sanctum', 'role:pharmacist,admin'])->group(function () {
        Route::get('/orders', [OrderController::class, 'adminIndex']);
        Route::get('/orders/{id}', [OrderController::class, 'adminShow']);
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
        Route::put('/orders/{id}/payment-status', [OrderController::class, 'updatePaymentStatus']);
        
        // Chatbot settings
        Route::get('/chatbot/suggestions', [\App\Http\Controllers\Api\Admin\ChatbotSettingController::class, 'getSuggestions']);
        Route::put('/chatbot/suggestions', [\App\Http\Controllers\Api\Admin\ChatbotSettingController::class, 'updateSuggestions']);
        Route::get('/chatbot/advanced', [\App\Http\Controllers\Api\Admin\ChatbotSettingController::class, 'getAdvancedSettings']);
        Route::put('/chatbot/advanced', [\App\Http\Controllers\Api\Admin\ChatbotSettingController::class, 'updateAdvancedSettings']);
        Route::get('/chatbot/trending', [ChatbotController::class, 'trending']);
        
        // Q&As management
        Route::get('/qas', [QaController::class, 'adminIndex']);
        Route::post('/qas/{id}/reply', [QaController::class, 'adminReply']);
        Route::put('/qas/{id}/toggle-visibility', [QaController::class, 'adminToggleVisibility']);
        Route::delete('/qas/{id}', [QaController::class, 'adminDestroy']);
        
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/dashboard/analytics', [DashboardController::class, 'analytics']);
    });

    // Admin rules
    Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::post('/upload', [ProductController::class, 'uploadImage']);
        
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        
        Route::post('/branches', [BranchController::class, 'store']);
        Route::put('/branches/{id}', [BranchController::class, 'update']);
        Route::delete('/branches/{id}', [BranchController::class, 'destroy']);
        
        Route::post('/brands', [BrandController::class, 'store']);
        Route::put('/brands/{id}', [BrandController::class, 'update']);
        Route::delete('/brands/{id}', [BrandController::class, 'destroy']);

        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::put('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);

        Route::get('/coupons', [CouponController::class, 'index']);
        Route::post('/coupons', [CouponController::class, 'store']);
        Route::put('/coupons/{id}', [CouponController::class, 'update']);
        Route::delete('/coupons/{id}', [CouponController::class, 'destroy']);

        // Slides
        Route::post('/slides', [SlideController::class, 'store']);
        Route::put('/slides/order', [SlideController::class, 'updateOrder']);
        Route::put('/slides/{id}', [SlideController::class, 'update']);
        Route::delete('/slides/{id}', [SlideController::class, 'destroy']);
        Route::post('/slides/upload', [SlideController::class, 'uploadImage']);
    });
});
