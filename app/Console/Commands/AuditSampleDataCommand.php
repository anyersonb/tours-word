<?php

namespace App\Console\Commands;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\TeamMember;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Repeatable pre-deploy audit (S4, docs/lote-1/01-esquema-lote1.md), born
 * from two lote-2 QA artifacts found live in `pachaviva`:
 *   - tours.id=3 ("Camino Inca Corto a Machu Picchu"): is_featured=1,
 *     order=0, no "[MUESTRA]" prefix — renders first in the Home carousel,
 *     indistinguishable from real client content.
 *   - users.id=2 (noadmin.qa@pachaviva.test): a QA-only account.
 *
 * This command deliberately does NOT guess what catalog content is safe to
 * touch: once the client's real data lands, legitimate content will
 * correctly lack the "[MUESTRA]" prefix too, so the default action is a
 * READ-ONLY report for a human to review before every deploy. The only
 * thing detected with full certainty is the project's own reserved test
 * email domain (`pachaviva.test`, an RFC 2606 special-use TLD — no real
 * client will ever be assigned an address on it). Any catalog cleanup
 * requires the operator to name the exact IDs after reading the report:
 * that is what "explicit, not default" means for --clean-tour-ids.
 */
class AuditSampleDataCommand extends Command
{
    protected $signature = 'data:audit-sample
        {--clean-test-users : Delete users whose email uses the reserved pachaviva.test domain}
        {--clean-tour-ids= : Comma-separated tour IDs to explicitly unpublish/un-feature (e.g. 3,7)}';

    protected $description = 'Reporta contenido sin prefijo [MUESTRA] y usuarios existentes, para revisar antes de cada despliegue. No borra ni modifica nada salvo que se pida explícitamente con --clean-*.';

    private const TEST_EMAIL_DOMAIN = 'pachaviva.test';

    private const SAMPLE_PREFIX = '[MUESTRA]';

    public function handle(): int
    {
        $this->auditUsers();
        $this->newLine();
        $this->auditCatalog();

        if ($this->option('clean-test-users')) {
            $this->newLine();
            $this->cleanTestUsers();
        }

        if ($tourIds = $this->option('clean-tour-ids')) {
            $this->newLine();
            $this->cleanTours($tourIds);
        }

        return self::SUCCESS;
    }

    private function auditUsers(): void
    {
        $users = User::query()->orderBy('id')->get();

        $this->info('Usuarios ('.$users->count().')');

        if ($users->isEmpty()) {
            $this->line('  (sin usuarios)');

            return;
        }

        $this->table(
            ['ID', 'Nombre', 'Email', 'is_admin', 'Dominio de prueba'],
            $users->map(fn (User $user) => [
                $user->id,
                $user->name,
                $user->email,
                $user->is_admin ? 'sí' : 'no',
                $this->isTestEmail($user->email) ? 'sí' : 'no',
            ])->all()
        );

        // Printed as plain lines, not table cells: Symfony's Table component
        // hard-wraps long cell content with real embedded newlines once it
        // exceeds the detected terminal width (narrow/fixed under a
        // BufferedOutput in tests), which would otherwise split this exact
        // phrase across two "cells" and make it ungreppable/unassertable.
        foreach ($users as $user) {
            if ($this->isTestEmail($user->email)) {
                $this->line("⚠ candidato a --clean-test-users: id={$user->id} {$user->email}");
            }
        }
    }

    private function auditCatalog(): void
    {
        $rows = collect();

        foreach (Tour::query()->orderBy('id')->get() as $tour) {
            $rows->push($this->catalogRow('Tour', $tour->id, $tour->getTranslation('title', 'es', false), $tour->is_published, $tour->is_featured, $tour->order));
        }

        foreach (Destination::query()->orderBy('id')->get() as $destination) {
            $rows->push($this->catalogRow('Destino', $destination->id, $destination->getTranslation('name', 'es', false), $destination->is_published, null, $destination->order));
        }

        foreach (Experience::query()->orderBy('id')->get() as $experience) {
            $rows->push($this->catalogRow('Experiencia', $experience->id, $experience->getTranslation('name', 'es', false), $experience->is_published, null, $experience->order));
        }

        foreach (TeamMember::query()->orderBy('id')->get() as $member) {
            $rows->push($this->catalogRow('Equipo', $member->id, $member->getTranslation('name', 'es', false), $member->is_published, null, $member->order));
        }

        /** @var Collection<int, array<string, mixed>> $notSample */
        $notSample = $rows->reject(fn (array $row) => $row['es_muestra']);

        $this->info('Contenido sin prefijo "'.self::SAMPLE_PREFIX.'" ('.$notSample->count().' de '.$rows->count().')');

        if ($notSample->isEmpty()) {
            $this->line('  (todo el contenido existente está marcado como muestra)');

            return;
        }

        $this->table(
            ['Tipo', 'ID', 'Texto (es)', 'Publicado', 'Destacado', 'Orden', 'Prioridad'],
            $notSample->map(fn (array $row) => [
                $row['type'],
                $row['id'],
                $row['text'],
                $row['is_published'] ? 'sí' : 'no',
                $row['is_featured'] === null ? '—' : ($row['is_featured'] ? 'sí' : 'no'),
                $row['order'],
                ($row['is_published'] && $row['is_featured'] && $row['order'] === 0) ? '⚠' : '',
            ])->all()
        );

        // See the comment in auditUsers(): plain lines, not table cells, so
        // the phrase can't be split by the table's terminal-width wrapping.
        foreach ($notSample as $row) {
            if ($row['is_published'] && $row['is_featured'] && $row['order'] === 0) {
                $this->line("⚠ prioridad de revisión: {$row['type']} id={$row['id']} \"{$row['text']}\" aparece primero en el carrusel de Home.");
            }
        }

        $this->warn('Este contenido NO tiene el prefijo "[MUESTRA]": puede ser contenido real ya aprobado por la clienta, o un artefacto de QA (como tours.id=3 en el incidente que motivó este comando). No se distingue automáticamente — revisar cada fila antes de desplegar.');
    }

    /**
     * @return array{type: string, id: int, text: string, is_published: bool, is_featured: bool|null, order: int, es_muestra: bool}
     */
    private function catalogRow(string $type, int $id, ?string $text, bool $isPublished, ?bool $isFeatured, int $order): array
    {
        return [
            'type' => $type,
            'id' => $id,
            'text' => $text ?? '(sin texto en es)',
            'is_published' => $isPublished,
            'is_featured' => $isFeatured,
            'order' => $order,
            'es_muestra' => $text !== null && Str::startsWith($text, self::SAMPLE_PREFIX),
        ];
    }

    private function isTestEmail(string $email): bool
    {
        return Str::endsWith(Str::lower($email), '@'.self::TEST_EMAIL_DOMAIN);
    }

    private function cleanTestUsers(): void
    {
        $victims = User::query()->get()->filter(fn (User $user) => $this->isTestEmail($user->email));

        if ($victims->isEmpty()) {
            $this->line('--clean-test-users: no hay usuarios con dominio @'.self::TEST_EMAIL_DOMAIN.'.');

            return;
        }

        foreach ($victims as $user) {
            $this->line("Eliminando usuario id={$user->id} ({$user->email})");
            $user->delete();
        }

        $this->info($victims->count().' usuario(s) eliminado(s).');
    }

    private function cleanTours(string $csvIds): void
    {
        $ids = collect(explode(',', $csvIds))
            ->map(fn (string $id) => (int) trim($id))
            ->filter()
            ->unique();

        if ($ids->isEmpty()) {
            $this->error('--clean-tour-ids requiere al menos un ID numérico.');

            return;
        }

        $tours = Tour::query()->whereKey($ids->all())->get();

        foreach ($tours as $tour) {
            $tour->forceFill(['is_published' => false, 'is_featured' => false])->save();
            $this->line("Tour id={$tour->id} despublicado y desmarcado como destacado (no se borra: queda en borrador para revisión).");
        }

        $missing = $ids->diff($tours->pluck('id'));

        foreach ($missing as $missingId) {
            $this->warn("Tour id={$missingId} no existe, se ignora.");
        }
    }
}
