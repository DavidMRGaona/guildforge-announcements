<?php

declare(strict_types=1);

use App\Application\Content\Services\TrixHtmlConverterInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rewrites announcement content saved by the Trix editor (Filament 3) into the HTML the
 * TipTap editor of Filament 5 produces, so the first save in the new panel does not change
 * it. The original HTML of every converted row is kept in a backup table; down() restores it.
 */
return new class extends Migration
{
    private const TABLE = 'announcements_announcements';

    private const BACKUP_TABLE = 'announcements_trix_content_backup';

    public function up(): void
    {
        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            Schema::create(self::BACKUP_TABLE, function (Blueprint $table): void {
                $table->uuid('announcement_id')->primary();
                $table->text('content');
                $table->timestamp('created_at')->nullable();
            });
        }

        // Resolved on the first row: an empty table (a fresh install) needs no converter
        $converter = null;

        foreach (DB::table(self::TABLE)->select(['id', 'content'])->lazyById(100) as $row) {
            $converter ??= app(TrixHtmlConverterInterface::class);
            $converted = $converter->convert((string) $row->content);

            if ($converted === $row->content) {
                continue;
            }

            // A second run finds the converted row: the backup keeps the original Trix HTML
            DB::table(self::BACKUP_TABLE)->insertOrIgnore([
                'announcement_id' => $row->id,
                'content' => $row->content,
                'created_at' => now(),
            ]);

            // Query builder on purpose: a format change is not an edit, updated_at stays
            DB::table(self::TABLE)->where('id', $row->id)->update(['content' => $converted]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            return;
        }

        foreach (DB::table(self::BACKUP_TABLE)->lazyById(100, 'announcement_id') as $row) {
            DB::table(self::TABLE)->where('id', $row->announcement_id)->update(['content' => $row->content]);
        }

        Schema::dropIfExists(self::BACKUP_TABLE);
    }
};
