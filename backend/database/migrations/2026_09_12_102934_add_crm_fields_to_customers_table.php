<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('location')->nullable()->after('phone');

            $table->string('status')
                ->default('new')
                ->index()
                ->after('location');

            $table->timestamp('last_contact_at')
                ->nullable()
                ->after('status');

            $table->foreignId('assigned_to')
                ->nullable()
                ->after('last_contact_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);

            $table->dropColumn([
                'location',
                'status',
                'last_contact_at',
                'assigned_to',
            ]);
        });
    }
};