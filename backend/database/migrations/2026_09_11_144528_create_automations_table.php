<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();

            /*
             * The event that starts the automation.
             * Example: message_received
             */
            $table->string('trigger', 50)->index();

            /*
             * JSON configuration for conditions.
             * Example:
             * {"keywords":["bei","godoro"]}
             */
            $table->json('conditions')->nullable();

            /*
             * JSON configuration for actions.
             * Example:
             * {"type":"send_text","message":"Karibu Wonder Godoro Point"}
             */
            $table->json('actions')->nullable();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};