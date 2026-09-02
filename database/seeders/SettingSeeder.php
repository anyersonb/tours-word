<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * The exchange rate is a fixed placeholder (NOT the real market rate —
     * nobody gave us that yet). It must be reviewed/edited by the client in
     * Configuración before this goes anywhere near production.
     */
    public function run(): void
    {
        Setting::query()->firstOrCreate(
            ['key' => 'exchange_rate_pen_usd'],
            [
                'value' => '3.75',
                'type' => 'float',
                'group' => 'moneda',
                'description' => 'Cuántos soles (PEN) equivalen a 1 dólar (USD). Valor fijo editable aquí, nunca una API. PLACEHOLDER: revisar con la clienta antes de producción.',
            ]
        );
    }
}
