<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Punto 10 del cierre de lote (docs/lote-3/seguridad-2026-09-14.md, defecto
 * de proceso): `dev:ensure-admin` repone el administrador de desarrollo que
 * un `migrate:fresh --seed` se lleva por delante (no es un seeder, se creó
 * a mano). Dos condiciones innegociables cubiertas aquí: (a) se niega a
 * correr fuera de local, (b) nunca hay una contraseña escrita en el
 * repositorio.
 */
class EnsureDevAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * (a) — control negativo primero: si esto no fallara, cualquier otro
     * resultado del comando no probaría nada sobre el gate de entorno.
     */
    public function test_it_refuses_to_run_outside_local_and_touches_nothing(): void
    {
        $this->app['env'] = 'production';

        $exitCode = Artisan::call('dev:ensure-admin', ['--no-interaction' => true]);

        $this->assertSame(1, $exitCode);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_refuses_to_run_under_the_testing_environment_too(): void
    {
        // La suite corre bajo APP_ENV=testing (phpunit.xml) -- sin forzar
        // "local" en los tests de abajo, el propio gate bloquearía esos
        // tests también. Este test prueba justo eso: "testing" no es
        // "local", y el comando debe rechazarlo igual que "production".
        $this->assertNotSame('local', $this->app['env']);

        $exitCode = Artisan::call('dev:ensure-admin', ['--no-interaction' => true]);

        $this->assertSame(1, $exitCode);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * (b) — sin TTY (--no-interaction), el comando genera una contraseña
     * aleatoria y la imprime en su propia salida en vez de aceptar
     * cualquier valor por defecto. Se captura esa salida (no se conoce la
     * contraseña de antemano) y se verifica que el hash guardado coincide
     * con lo impreso -- si esto pasara con un valor fijo, no probaría que
     * la contraseña de verdad se generó.
     */
    public function test_it_creates_the_admin_locally_with_a_generated_password_when_non_interactive(): void
    {
        $this->app['env'] = 'local';

        $exitCode = Artisan::call('dev:ensure-admin', ['--no-interaction' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Contraseña generada', $output);

        // "Creado administrador..." (vs. "Actualizado...") en la salida es
        // la señal observable de que se insertó una fila nueva --
        // wasRecentlyCreated es un flag en memoria de ESE objeto Eloquent,
        // no algo que sobreviva a volver a consultar el registro aquí.
        $this->assertStringContainsString('Creado administrador de desarrollo', $output);

        $user = User::query()->where('email', 'dev@pachaviva.test')->firstOrFail();
        $this->assertTrue($user->is_admin);

        $printedPassword = $this->extractPrintedPassword($output);
        $this->assertNotNull($printedPassword, 'La salida del comando debe incluir la contraseña generada.');
        $this->assertGreaterThanOrEqual(20, mb_strlen($printedPassword));
        $this->assertTrue(Hash::check($printedPassword, $user->password));
    }

    /**
     * Repetir el comando actualiza a la MISMA cuenta (updateOrCreate) en
     * vez de crear un segundo administrador -- exactamente lo que hace
     * falta para que sea seguro correrlo después de cada migrate:fresh sin
     * acumular usuarios.
     */
    public function test_running_it_twice_updates_the_same_account_instead_of_creating_a_second_one(): void
    {
        $this->app['env'] = 'local';

        Artisan::call('dev:ensure-admin', ['--no-interaction' => true]);
        $firstId = User::query()->where('email', 'dev@pachaviva.test')->value('id');

        Artisan::call('dev:ensure-admin', ['--no-interaction' => true]);
        $secondId = User::query()->where('email', 'dev@pachaviva.test')->value('id');

        $this->assertSame($firstId, $secondId);
        $this->assertSame(1, User::query()->where('email', 'dev@pachaviva.test')->count());
    }

    public function test_the_email_and_name_options_are_respected(): void
    {
        $this->app['env'] = 'local';

        Artisan::call('dev:ensure-admin', [
            '--no-interaction' => true,
            '--email' => 'otro-dev@pachaviva.test',
            '--name' => 'Otro Dev',
        ]);

        $user = User::query()->where('email', 'otro-dev@pachaviva.test')->firstOrFail();
        $this->assertSame('Otro Dev', $user->name);
        $this->assertTrue($user->is_admin);
    }

    private function extractPrintedPassword(string $output): ?string
    {
        // rtrim(\r): Windows console output can carry \r\n line endings,
        // which "." (not matching \n under /m) happily swallows into the
        // capture group -- an invisible trailing character that silently
        // breaks Hash::check() without changing the visible password at
        // all.
        return preg_match('/^CONTRASENA_GENERADA=(.+)$/m', $output, $matches) === 1
            ? rtrim($matches[1], "\r")
            : null;
    }
}
