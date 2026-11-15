<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The site block a window carries (announcements#70): its key and settings as JSON, and whether a
 * window whose block cannot be drawn is left out. Every window saved so far has no block.
 */
return new class extends Migration
{
    private const string TABLE = 'announcements_announcements';

    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->json('popup_block')->nullable();
            $table->boolean('popup_block_hide_when_unavailable')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropColumn(['popup_block', 'popup_block_hide_when_unavailable']);
        });
    }
};
