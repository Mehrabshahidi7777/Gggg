<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('total_amount', 15, 2);
            $table->string('phone', 30);
            $table->text('address');
            $table->enum('status', ['pending','paid','processing','shipped','completed','cancelled'])->default('pending');
            $table->string('payment_transaction_id')->nullable();
            $table->string('payment_reference_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['buyer_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('orders'); }
};
