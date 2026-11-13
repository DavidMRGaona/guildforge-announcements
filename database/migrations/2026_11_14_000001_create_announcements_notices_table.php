<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which announcements were already told in the bell (announcements#63): one row each, so an
 * announcement is told once however often it is saved, turned off and on, or rescheduled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements_notices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('announcement_id')
                ->unique()
                ->constrained('announcements_announcements')
                ->cascadeOnDelete();
            $table->timestamp('announced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements_notices');
    }
};
