<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_knowledge', function (Blueprint $table): void {
            $table->id();
            $table->string('category', 50)->index();
            $table->string('title', 180);
            $table->string('question', 500)->nullable();
            $table->text('answer');
            $table->string('language', 8)->default('sw');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_conversation_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('status', 30)->default('active')->index();
            $table->string('intent', 80)->nullable()->index();
            $table->string('stage', 80)->default('NEW');
            $table->string('language', 8)->nullable();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->json('context')->nullable();
            $table->text('summary')->nullable();
            $table->text('next_action')->nullable();
            $table->timestamp('last_customer_message_at')->nullable()->index();
            $table->timestamp('last_ai_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('administrator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor', 20)->default('ai')->index();
            $table->string('tool', 80)->index();
            $table->string('status', 20)->default('completed')->index();
            $table->string('summary', 500);
            $table->string('idempotency_key', 191)->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('ai_escalations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('taken_over_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('open')->index();
            $table->string('reason', 180);
            $table->string('intent', 80)->nullable();
            $table->text('summary')->nullable();
            $table->json('products_discussed')->nullable();
            $table->text('recommended_action')->nullable();
            $table->timestamp('taken_over_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'status']);
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('ai_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('scheduled')->index();
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->timestamp('scheduled_at')->index();
            $table->timestamp('sent_at')->nullable();
            $table->string('idempotency_key', 191)->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'status', 'scheduled_at']);
            $table->index(['customer_id', 'status']);
        });

        $now = now();
        DB::table('ai_settings')->insert([
            ['key' => 'ai_enabled', 'value' => 'false', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'auto_reply_enabled', 'value' => 'false', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'auto_order_creation', 'value' => 'true', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'auto_follow_up', 'value' => 'false', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'default_language', 'value' => 'auto', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'business_tone', 'value' => 'warm_professional', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'maximum_follow_ups', 'value' => '2', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'escalation_threshold', 'value' => '0.55', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'follow_up_delay_minutes', 'value' => '60', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'quiet_hours_start', 'value' => '21:00', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'quiet_hours_end', 'value' => '08:00', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'order_confirmation_required', 'value' => 'true', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_follow_ups');
        Schema::dropIfExists('ai_escalations');
        Schema::dropIfExists('ai_actions');
        Schema::dropIfExists('ai_conversation_states');
        Schema::dropIfExists('ai_knowledge');
        Schema::dropIfExists('ai_settings');
    }
};
