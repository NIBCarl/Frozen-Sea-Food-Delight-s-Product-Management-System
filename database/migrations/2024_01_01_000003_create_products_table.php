<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('product_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->decimal('price', 10, 2);
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_level')->default(0);
            $table->string('sku')->unique()->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('dimensions')->nullable();
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->boolean('featured')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index('product_id');
            $table->index('slug');
            $table->index('sku');
            $table->index('status');
            $table->index('featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
