<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();

            $table->string('order_number')->unique();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            $table->string('product_name');
            $table->string('product_size')->nullable();

            $table->unsignedInteger('quantity')->default(1);

            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_amount', 12, 2);

            $table->string('status', 30)
                ->default('pending')
                ->index();

            $table->string('payment_status', 30)
                ->default('unpaid')
                ->index();

            $table->string('delivery_status', 30)
                ->default('pending')
                ->index();

            $table->text('delivery_address')->nullable();

            $table->text('notes')->nullable();

            $table->timestamp('ordered_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};