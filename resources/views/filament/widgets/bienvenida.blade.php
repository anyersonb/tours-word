<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem 1.5rem;">
            <div>
                <h2 class="text-xl font-semibold text-gray-950">
                    Hola{{ filled($nombre) ? ', '.$nombre : '' }}
                </h2>
                <p class="text-sm text-gray-600" style="margin-top:.25rem;max-width:40rem;">
                    Desde aquí administras el sitio de {{ $marca }}: tus tours, destinos, experiencias y los mensajes que llegan por el formulario de contacto.
                </p>
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:.75rem;">
                <x-filament::button tag="a" :href="$urlNuevoTour" icon="heroicon-o-plus">
                    Nuevo tour
                </x-filament::button>
                <x-filament::button tag="a" :href="$urlMensajes" color="gray" icon="heroicon-o-envelope">
                    Ver mensajes
                </x-filament::button>
                <x-filament::button tag="a" :href="$urlSitio" color="gray" icon="heroicon-o-arrow-top-right-on-square" target="_blank" rel="noopener">
                    Ver el sitio
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
