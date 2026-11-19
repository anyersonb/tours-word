<?php

namespace Tests\Feature;

use App\Enums\TourDifficulty;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoopBack1ProgramacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_faq_phone_follows_the_cms_setting_in_es_and_en(): void
    {
        Setting::set('contact_phone', '+51 900 111 222');

        foreach (['es', 'en'] as $locale) {
            $html = $this->get(route('home', ['locale' => $locale]))->assertOk()->getContent();
            $this->assertStringContainsString('+51 900 111 222', $html);
            $this->assertStringNotContainsString('959 173 246', $html, "locale {$locale}");
        }
    }

    public function test_home_faq_phone_falls_back_to_brand_config(): void
    {
        $this->get(route('home', ['locale' => 'es']))
            ->assertOk()
            ->assertSee(config('brand.contact.phone'));
    }

    public function test_lang_files_have_no_hardcoded_phone(): void
    {
        foreach (['es', 'en'] as $locale) {
            $this->assertStringNotContainsString('959 173 246', file_get_contents(lang_path("{$locale}/home.php")));
        }
    }

    public function test_difficulty_label_for_filament_has_accents_and_values_are_unchanged(): void
    {
        app()->setLocale('es');

        $this->assertSame('Difícil', TourDifficulty::Dificil->getLabel());
        $this->assertSame('Fácil', TourDifficulty::Facil->getLabel());
        $this->assertSame('dificil', TourDifficulty::Dificil->value);
        $this->assertSame('facil', TourDifficulty::Facil->value);
    }
}
