<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Punto 10 del cierre de lote (docs/lote-3/seguridad-2026-09-14.md, defecto
 * de PROCESO, no de código). `users` apareció vacía la noche del
 * 2026-09-13: `migrate:fresh --seed` se llevó al único administrador
 * porque nunca fue un seeder — se creó a mano por `tinker`
 * (docs/lote-2/README.md), y `DatabaseSeeder` deliberadamente no siembra
 * ningún usuario (ver su docblock: "a seeder that creates a user with a
 * predictable password is exactly the kind of thing that has leaked into
 * production before"). Las dos cosas eran correctas por separado y juntas
 * dejan el panel sin acceso después de cualquier migración limpia.
 *
 * Este comando repone un administrador de DESARROLLO bajo dos condiciones
 * innegociables:
 *
 *   (a) Se NIEGA a ejecutarse fuera de local -- no una advertencia que deje
 *       seguir, un exit(FAILURE) real, ANTES de tocar la base de datos. No
 *       hay --force ni ninguna otra puerta trasera: el único modo de
 *       correr esto es que Application::isLocal() (config('app.env') ===
 *       "local") sea cierto.
 *   (b) NINGUNA contraseña vive en este archivo ni en ningún commit: se
 *       pide interactivamente (oculta, con confirmación) o, si no hay TTY
 *       (CI, `--no-interaction`), se genera una aleatoria y se imprime UNA
 *       sola vez en la salida del comando -- nunca en un log, nunca en un
 *       archivo. Precedente que esto evita: unas credenciales de
 *       demostración de otro proyecto de la casa sobrevivieron hasta
 *       producción y quedaron activas ahí (ver la memoria del equipo).
 *
 * `dev@pachaviva.test` sigue teniendo que borrarse antes de producción —
 * eso ya está documentado en docs/lote-2/README.md y no es competencia de
 * este comando (que de todos modos se niega a correr ahí).
 */
class EnsureDevAdminCommand extends Command
{
    protected $signature = 'dev:ensure-admin
        {--email=dev@pachaviva.test : Correo del administrador de desarrollo}
        {--name=Dev Local : Nombre del administrador de desarrollo}';

    protected $description = 'Repone (o crea) el administrador de desarrollo local tras un migrate:fresh. Se niega a correr fuera de local. Nunca trae contraseñas en el repositorio.';

    private bool $generatedPassword = false;

    public function handle(): int
    {
        // (a) — hard refusal, checked before anything else touches the DB.
        // No --force, no override: the only way to run this is for
        // isLocal() to already be true.
        if (! $this->laravel->isLocal()) {
            $this->error(sprintf(
                'dev:ensure-admin se niega a ejecutarse: APP_ENV="%s", no "local". '.
                'Este comando existe solo para reponer el acceso de desarrollo tras un migrate:fresh local.',
                (string) config('app.env')
            ));

            return self::FAILURE;
        }

        $email = (string) $this->option('email');
        $name = (string) $this->option('name');

        $password = $this->resolvePassword();

        $user = User::query()->firstOrNew(['email' => $email]);
        $wasRecentlyCreated = ! $user->exists;

        $user->name = $name;
        $user->password = Hash::make($password);
        // User::$guarded = ['is_admin'] on purpose (V-2, docs/lote-2/
        // seguridad-2026-09-01.md, M-2): no ->fill()/create()/
        // updateOrCreate() path may ever set it, including this one.
        // Direct property assignment bypasses the guard exactly the way
        // docs/lote-2/README.md's manual tinker snippet already did — the
        // guard is protecting against mass assignment from user input,
        // not from an explicit, reviewed line of code like this.
        $user->is_admin = true;
        $user->save();

        $this->info(($wasRecentlyCreated ? 'Creado' : 'Actualizado')." administrador de desarrollo: {$email}");

        if ($this->generatedPassword) {
            $this->newLine();
            $this->warn('Contraseña generada (se muestra UNA sola vez, no queda guardada en ningún log ni archivo):');
            // Prefijo estable y sin espacios a propósito: parseable por un
            // script (o por el test de este comando) sin depender de que
            // el resto del texto decorativo no cambie nunca.
            $this->line("CONTRASENA_GENERADA={$password}");
            $this->newLine();
        }

        $this->comment('Recordatorio: esta cuenta es SOLO para desarrollo local y debe borrarse antes de cualquier despliegue (ver docs/lote-2/README.md).');

        return self::SUCCESS;
    }

    /**
     * (b) — never a hardcoded password. Interactive terminals get prompted
     * (hidden input, with confirmation so a typo isn't silently accepted);
     * a non-interactive run (CI, `--no-interaction`) gets a random password
     * that is only ever held in memory and printed once.
     */
    private function resolvePassword(): string
    {
        if ($this->input->isInteractive()) {
            $password = $this->secret('Contraseña para el administrador de desarrollo (mínimo 12 caracteres)');

            while (mb_strlen($password ?? '') < 12) {
                $this->error('La contraseña debe tener al menos 12 caracteres.');
                $password = $this->secret('Contraseña para el administrador de desarrollo (mínimo 12 caracteres)');
            }

            $confirmation = $this->secret('Confirma la contraseña');

            if ($password !== $confirmation) {
                $this->error('Las contraseñas no coinciden. Intenta de nuevo.');

                return $this->resolvePassword();
            }

            return $password;
        }

        $this->generatedPassword = true;

        return Str::password(20);
    }
}
