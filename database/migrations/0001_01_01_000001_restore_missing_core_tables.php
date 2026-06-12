<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Brands
        Schema::create('brands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('country_of_origin')->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Products
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id');
            $table->uuid('brand_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->text('usage')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('requires_prescription')->default(false);
            $table->string('status')->default('active');
            $table->decimal('base_price', 15, 2);
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->string('unit')->nullable();
            $table->string('dosage_form')->nullable();
            $table->string('active_ingredient')->nullable();
            $table->string('manufacturer')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Product Images
        Schema::create('product_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('url');
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Product Attributes
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('attr_key');
            $table->text('attr_value');
            $table->timestamps();
        });

        // Branches
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('address');
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Inventory
        Schema::create('inventory', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('branch_id');
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_minimum')->default(10);
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        // Inventory Logs
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inventory_id');
            $table->uuid('actor_id')->nullable();
            $table->string('action_type');
            $table->integer('quantity_delta');
            $table->integer('quantity_after');
            $table->string('note')->nullable();
            $table->uuid('ref_order_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // User Addresses
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('label')->nullable();
            $table->string('address_line');
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // User Sessions
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('token_hash');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // Prescriptions
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('branch_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('hospital')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expires_date')->nullable();
            $table->string('image_url');
            $table->string('status')->default('pending');
            $table->text('reject_reason')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // Coupons
        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('discount_type');
            $table->decimal('discount_value', 15, 2);
            $table->decimal('min_order_value', 15, 2)->default(0);
            $table->decimal('max_discount', 15, 2)->nullable();
            $table->integer('max_uses')->default(1);
            $table->integer('used_count')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Carts
        Schema::create('carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->timestamps();
        });

        // Cart Items
        Schema::create('cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cart_id');
            $table->uuid('product_id');
            $table->integer('quantity');
            $table->decimal('price_snapshot', 15, 2);
            $table->uuid('prescription_id')->nullable();
            $table->timestamp('added_at')->nullable();
        });

        // Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('branch_id')->nullable();
            $table->string('order_code')->unique();
            $table->string('status')->default('pending');
            $table->string('delivery_type');
            $table->string('delivery_address')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('shipping_fee', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->uuid('coupon_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Order Items
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('product_id');
            $table->uuid('prescription_id')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->string('method');
            $table->string('status')->default('pending');
            $table->decimal('amount', 15, 2);
            $table->string('transaction_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Product QAs
        Schema::create('product_qas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('user_id')->nullable();
            $table->text('body');
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->text('admin_reply')->nullable();
            $table->timestamp('admin_replied_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // Drug Interactions
        Schema::create('drug_interactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_a_id');
            $table->uuid('product_b_id');
            $table->string('severity');
            $table->text('description')->nullable();
            $table->text('clinical_effect')->nullable();
            $table->string('source_reference')->nullable();
            $table->timestamps();
        });

        // Chatbot Sessions
        Schema::create('chatbot_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('session_token')->unique();
            $table->string('channel')->default('web');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
        });

        // Chatbot Messages
        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->string('role');
            $table->text('content');
            $table->string('intent_detected')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // Chatbot Product Refs
        Schema::create('chatbot_product_refs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('message_id');
            $table->uuid('product_id');
            $table->string('ref_type');
        });

        // Chatbot Feedback
        Schema::create('chatbot_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('message_id');
            $table->uuid('user_id')->nullable();
            $table->string('rating');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_feedback');
        Schema::dropIfExists('chatbot_product_refs');
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_sessions');
        Schema::dropIfExists('drug_interactions');
        Schema::dropIfExists('product_qas');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('user_addresses');
        Schema::dropIfExists('inventory_logs');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
    }
};
