<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The popup format (announcements#67): every announcement saved so far is a banner, and the
 * window settings start from their defaults (a centred window on every page and device).
 */
return new class extends Migration
{
    private const string TABLE = 'announcements_announcements';

    /** @var list<string> */
    private const array COLUMNS = [
        'format',
        'popup_shape',
        'popup_image_public_id',
        'popup_action_label',
        'popup_action_url',
        'popup_trigger_delay',
        'popup_trigger_scroll',
        'popup_trigger_exit_intent',
        'popup_trigger_page_views',
        'popup_pages',
        'popup_paths',
        'popup_device',
    ];

    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->string('format', 16)->default('banner');
            $table->string('popup_shape', 16)->default('centered');
            $table->string('popup_image_public_id')->nullable();
            $table->string('popup_action_label', 60)->nullable();
            $table->string('popup_action_url', 2048)->nullable();
            $table->unsignedSmallInteger('popup_trigger_delay')->nullable();
            $table->unsignedSmallInteger('popup_trigger_scroll')->nullable();
            $table->boolean('popup_trigger_exit_intent')->default(false);
            $table->unsignedSmallInteger('popup_trigger_page_views')->nullable();
            $table->string('popup_pages', 16)->default('all');
            $table->json('popup_paths')->nullable();
            $table->string('popup_device', 16)->default('all');
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropColumn(self::COLUMNS);
        });
    }
};
