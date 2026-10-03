<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('lead_source')->nullable()->after('assigned_to');
            $table->string('district')->nullable()->after('lead_source');
            $table->string('area')->nullable()->after('district');
            $table->timestamp('next_follow_up_at')->nullable()->after('area');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->string('sku')->nullable()->unique()->after('name');
            $table->decimal('cost_price', 12, 2)->default(0)->after('price');
            $table->unsignedInteger('stock_quantity')->default(0)->after('cost_price');
            $table->unsignedInteger('reorder_level')->default(0)->after('stock_quantity');
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->timestamp('failed_at')->nullable()->after('read_at');
            $table->string('error_code')->nullable()->after('failed_at');
            $table->text('error_message')->nullable()->after('error_code');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('product_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('products')
                ->nullOnDelete();

            $table->foreignId('delivery_assigned_to')
                ->nullable()
                ->after('delivery_status')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('delivery_area')->nullable()->after('delivery_address');
            $table->timestamp('delivered_at')->nullable()->after('delivery_area');
            $table->text('delivery_notes')->nullable()->after('delivered_at');
            $table->string('delivery_proof_path')->nullable()->after('delivery_notes');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['delivery_assigned_to']);
            $table->dropColumn([
                'product_id',
                'delivery_assigned_to',
                'delivery_area',
                'delivered_at',
                'delivery_notes',
                'delivery_proof_path',
            ]);
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->dropColumn(['failed_at', 'error_code', 'error_message']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique(['sku']);
            $table->dropColumn(['sku', 'cost_price', 'stock_quantity', 'reorder_level']);
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['lead_source', 'district', 'area', 'next_follow_up_at']);
        });
    }
};
