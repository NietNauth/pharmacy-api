<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    Schema::dropIfExists('employee_profiles');
    
    Schema::create('employee_profiles', function (Blueprint $table) {
        $table->charset = 'utf8mb4';
        $table->collation = 'utf8mb4_general_ci';

        $table->char('id', 36)->primary();
        $table->char('user_id', 36)->index();
        $table->string('employee_code')->unique();
        $table->string('position')->nullable();
        $table->decimal('salary', 15, 2)->nullable();
        $table->date('join_date')->nullable();
        $table->string('certificate_code')->nullable();
        $table->enum('status', ['active', 'on_leave', 'quit'])->default('active');
        $table->text('notes')->nullable();
        $table->timestamps();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
    echo "Table employee_profiles created successfully.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
