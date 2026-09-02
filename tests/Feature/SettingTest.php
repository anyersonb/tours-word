<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_values_are_cast_according_to_their_declared_type(): void
    {
        Setting::set('a_float', 3.75, 'float');
        Setting::set('an_integer', 42, 'integer');
        Setting::set('a_boolean', true, 'boolean');
        Setting::set('a_json_value', ['a' => 1], 'json');
        Setting::set('a_string', 'hello', 'string');

        $this->assertSame(3.75, Setting::get('a_float'));
        $this->assertSame(42, Setting::get('an_integer'));
        $this->assertTrue(Setting::get('a_boolean'));
        $this->assertSame(['a' => 1], Setting::get('a_json_value'));
        $this->assertSame('hello', Setting::get('a_string'));
    }

    public function test_missing_key_returns_the_given_default(): void
    {
        $this->assertSame('fallback', Setting::get('does_not_exist', 'fallback'));
        $this->assertNull(Setting::get('does_not_exist_either'));
    }
}
