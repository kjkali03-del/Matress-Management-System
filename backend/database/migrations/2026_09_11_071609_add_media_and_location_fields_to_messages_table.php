<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->string('media_id')->nullable()->after('body');
            $table->text('media_url')->nullable()->after('media_id');
            $table->string('media_mime_type')->nullable()->after('media_url');
            $table->string('media_filename')->nullable()->after('media_mime_type');
            $table->text('media_caption')->nullable()->after('media_filename');

            $table->decimal('latitude', 10, 7)->nullable()->after('media_caption');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('location_name')->nullable()->after('longitude');
            $table->text('location_address')->nullable()->after('location_name');

            $table->index('media_id');
            $table->index('message_type');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropIndex(['media_id']);
            $table->dropIndex(['message_type']);

            $table->dropColumn([
                'media_id',
                'media_url',
                'media_mime_type',
                'media_filename',
                'media_caption',
                'latitude',
                'longitude',
                'location_name',
                'location_address',
            ]);
        });
    }
};