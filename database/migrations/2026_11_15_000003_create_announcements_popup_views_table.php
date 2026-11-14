<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What an account has seen of each window (announcements#68): when it was last shown to it and
 * when it closed it, so a window does not come back on another device. One row per account and
 * announcement; it goes with the account and with the announcement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements_popup_views', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignUuid('announcement_id')
                ->constrained('announcements_announcements')
                ->cascadeOnDelete();
            $table->timestamp('last_shown_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();

            $table->unique(['user_id', 'announcement_id']);
            // The cascade of an announcement looks rows up by it
            $table->index('announcement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements_popup_views');
    }
};
