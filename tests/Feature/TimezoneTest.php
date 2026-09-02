<?php

namespace Tests\Feature;

use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Guards against the exact incident this project has already hit once
 * elsewhere: the app reasoning in UTC while the operator reasons in Lima
 * time. See config/database.php's "timezone" => "-05:00" fix for the MySQL
 * session, and config/app.php's "timezone" => env('APP_TIMEZONE').
 */
class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_timezone_is_america_lima(): void
    {
        $this->assertSame('America/Lima', config('app.timezone'));
        $this->assertSame('America/Lima', date_default_timezone_get());
    }

    public function test_now_reports_the_fixed_lima_offset(): void
    {
        // Peru does not observe DST, so the offset is always -05:00.
        $this->assertSame('-05:00', now()->format('P'));
    }

    public function test_a_timestamp_saved_and_reloaded_does_not_shift_across_the_php_mysql_boundary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-01 23:30:00', 'America/Lima'));

        $tour = Tour::factory()->create();

        $reloaded = Tour::query()->findOrFail($tour->id);

        $this->assertSame(
            '2026-09-01 23:30:00',
            $reloaded->created_at->format('Y-m-d H:i:s'),
            'A record saved at 23:30 Lima time must read back as 23:30 Lima time, not shifted to the next/previous day.'
        );

        Carbon::setTestNow();
    }
}
