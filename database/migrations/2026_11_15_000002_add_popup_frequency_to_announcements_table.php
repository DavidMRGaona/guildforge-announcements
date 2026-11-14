<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How often a window returns to the same person (announcements#68). Every window saved so far
 * opens once; the days only matter for a window that returns every few days.
 */
return new class extends Migration
{
    private const string TABLE = 'announcements_announcements';

    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->string('popup_frequency', 16)->default('once');
            $table->unsignedSmallInteger('popup_frequency_days')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropColumn(['popup_frequency', 'popup_frequency_days']);
        });
    }
};
