<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_id')->nullable()->constrained('ads')->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('seller_phone', 30)->nullable();
            $table->string('title');
            $table->decimal('unit_price', 15, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
            $table->index(['seller_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
