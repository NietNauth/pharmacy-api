<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::table('product_attributes', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) { $table->dropForeign(['order_id']); });
        Schema::table('order_items', function (Blueprint $table) { 
            $table->dropForeign(['order_id']); 
            $table->dropForeign(['product_id']); 
        });
        Schema::table('inventory', function (Blueprint $table) { 
            $table->dropForeign(['product_id']); 
            $table->dropForeign(['branch_id']); 
        });
        Schema::table('product_attributes', function (Blueprint $table) { $table->dropForeign(['product_id']); });
        Schema::table('product_images', function (Blueprint $table) { $table->dropForeign(['product_id']); });
        Schema::table('products', function (Blueprint $table) { 
            $table->dropForeign(['category_id']); 
            $table->dropForeign(['brand_id']); 
        });
    }
};
