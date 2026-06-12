<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->char('id', 36)->primary()->default(DB::raw('(UUID())'));
                $table->char('parent_id', 36)->nullable();
                $table->string('name', 100);
                $table->string('slug', 120)->unique();
                $table->string('icon_url', 512)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('display_order')->default(0);
                
                $table->foreign('parent_id')
                    ->references('id')
                    ->on('categories')
                    ->onDelete('set null');
                
                $table->index('parent_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
