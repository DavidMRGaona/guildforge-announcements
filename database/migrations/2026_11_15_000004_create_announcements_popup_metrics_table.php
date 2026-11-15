<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anonymous counters of the windows (announcements#69): how many times each was shown, closed
 * and its button pressed, per display day. No account, session or address is kept, only the
 * window, the day and the number. They go with the announcement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements_popup_metrics', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('announcement_id')
                ->constrained('announcements_announcements')
                ->cascadeOnDelete();
            $table->date('day');
            $table->string('metric', 20);
            $table->unsignedInteger('count')->default(0);

            $table->unique(['announcement_id', 'day', 'metric']);
            // Pruning looks rows up by day
            $table->index('day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements_popup_metrics');
    }
};
