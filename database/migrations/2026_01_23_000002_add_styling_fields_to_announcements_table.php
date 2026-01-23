<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements_announcements', function (Blueprint $table) {
            $table->string('position')->default('before-header')->after('visibility');
            $table->string('background_color')->nullable()->after('position');
            $table->string('text_color')->nullable()->after('background_color');
        });
    }

    public function down(): void
    {
        Schema::table('announcements_announcements', function (Blueprint $table) {
            $table->dropColumn(['position', 'background_color', 'text_color']);
        });
    }
};
