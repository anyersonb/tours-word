<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rebrand data migration: "Pacha Viva" / "Peru Local" / "Perú Local" ->
 * "INCA PALACE Travel" in already-persisted CMS content (demo seeds that
 * ran before the rename, including es/en JSON translation columns).
 *
 * Done row by row in PHP (not SQL REPLACE) so it is case-insensitive and
 * behaves the same on SQLite and MySQL, including JSON columns.
 *
 * Safety rules:
 * - Brand words must be separated by whitespace ("Pacha Viva"), and must not
 *   be glued to word characters or to @ / - (so "pachaviva", "x@pachaviva.pe",
 *   "instagram.com/pachaviva/" and "pachaviva-logo.png" never match).
 * - Any value containing "@", "://" or "www." (emails, URLs) and any bare
 *   file path (images/x.png) is skipped whole.
 * - In `settings` only an explicit list of visible-text keys is touched.
 *   Contact messages (visitor input) and legal-entity settings are left alone.
 * - Every original value is saved in `rebrand_inca_palace_backup` BEFORE it
 *   is rewritten; down() restores from it and drops it. Backups are unique per
 *   (table, row, column) and use insertOrIgnore, so a second up() neither
 *   duplicates backups nor changes anything (nothing matches any more).
 * - Setting::get() caches forever; each touched settings key is forgotten
 *   here. No other model caches exist in the app.
 *
 * DEPLOY: also run `php artisan cache:clear` after migrating (the cache
 * store may hold entries for keys this migration never saw).
 */
return new class extends Migration
{
    private const BACKUP_TABLE = 'rebrand_inca_palace_backup';

    private const PATTERN = '/(?<![\w@\/.\-])(?:Pacha\s+Viva|Per[uú]\s+Local)(?![\w@\/\-]|\.\w)/iu';

    private const REPLACEMENT = 'INCA PALACE Travel';

    /** @var array<string, list<string>> table => text/JSON columns */
    private const TARGETS = [
        'destinations' => ['name', 'description', 'cover_image_alt', 'meta_title', 'meta_description'],
        'experiences' => ['name', 'description', 'cover_image_alt', 'meta_title', 'meta_description'],
        'tours' => ['title', 'summary', 'description', 'duration_label', 'meeting_point', 'inclusions', 'exclusions', 'itinerary', 'meta_title', 'meta_description'],
        'destination_images' => ['alt'],
        'experience_images' => ['alt'],
        'tour_images' => ['alt'],
        'team_members' => ['name', 'role', 'description', 'photo_alt'],
        'settings' => ['value', 'description'],
    ];

    /**
     * Visible-text settings keys (from SettingSeeder and Setting::get() call
     * sites). Anything else (URLs, emails, ids, legal identity) is ignored.
     */
    private const TEXT_SETTING_KEYS = [
        'contact_address',
        'contact_schedule',
        'site_tagline',
        'site_description',
    ];

    public function up(): void
    {
        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            Schema::create(self::BACKUP_TABLE, function (Blueprint $table) {
                $table->id();
                $table->string('table_name', 64);
                $table->unsignedBigInteger('row_id');
                $table->string('column_name', 64);
                $table->longText('original_value');
                $table->timestamp('created_at')->nullable();
                $table->unique(['table_name', 'row_id', 'column_name'], 'rebrand_backup_unique');
            });
        }

        foreach (self::TARGETS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = array_values(array_filter($columns, fn (string $c) => Schema::hasColumn($table, $c)));
            if ($columns === []) {
                continue;
            }

            DB::table($table)->select(array_merge(['id'], $table === 'settings' ? ['key'] : [], $columns))
                ->orderBy('id')
                ->chunk(200, function ($rows) use ($table, $columns) {
                    foreach ($rows as $row) {
                        if ($table === 'settings' && ! in_array($row->key, self::TEXT_SETTING_KEYS, true)) {
                            continue;
                        }

                        $changes = [];
                        foreach ($columns as $column) {
                            $value = $row->{$column};
                            if (! is_string($value) || $value === '' || $this->isUnsafeValue($value)) {
                                continue;
                            }
                            $new = preg_replace(self::PATTERN, self::REPLACEMENT, $value);
                            if ($new !== null && $new !== $value) {
                                $changes[$column] = $new;
                            }
                        }

                        if ($changes === []) {
                            continue;
                        }

                        DB::transaction(function () use ($table, $row, $changes) {
                            foreach (array_keys($changes) as $column) {
                                DB::table(self::BACKUP_TABLE)->insertOrIgnore([
                                    'table_name' => $table,
                                    'row_id' => $row->id,
                                    'column_name' => $column,
                                    'original_value' => $row->{$column},
                                    'created_at' => now(),
                                ]);
                            }
                            DB::table($table)->where('id', $row->id)->update($changes);
                        });

                        if ($table === 'settings') {
                            Cache::forget(Setting::CACHE_PREFIX.$row->key);
                        }
                    }
                });
        }
    }

    /**
     * Restores every original value saved by up(), then drops the backup
     * table. Re-running up() afterwards rewrites again from scratch.
     */
    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            return;
        }

        DB::table(self::BACKUP_TABLE)->orderBy('id')->chunk(200, function ($backups) {
            foreach ($backups as $backup) {
                if (! Schema::hasTable($backup->table_name) || ! Schema::hasColumn($backup->table_name, $backup->column_name)) {
                    continue;
                }

                DB::table($backup->table_name)->where('id', $backup->row_id)
                    ->update([$backup->column_name => $backup->original_value]);

                if ($backup->table_name === 'settings') {
                    $key = DB::table('settings')->where('id', $backup->row_id)->value('key');
                    if ($key !== null) {
                        Cache::forget(Setting::CACHE_PREFIX.$key);
                    }
                }
            }
        });

        Schema::dropIfExists(self::BACKUP_TABLE);
    }

    /** Emails, URLs and bare file paths are never rewritten. */
    private function isUnsafeValue(string $value): bool
    {
        if (str_contains($value, '@') || str_contains($value, '://') || stripos($value, 'www.') !== false) {
            return true;
        }

        return preg_match('/^[\w\-.\/]+\.(?:png|jpe?g|webp|gif|svg|ico|pdf)$/i', trim($value)) === 1;
    }
};
