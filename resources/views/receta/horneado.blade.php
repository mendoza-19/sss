@once
    @push('styless')
        <link rel="stylesheet" href="{{ asset('css/recetas/style.css') }}?v={{ md5_file(public_path('css/recetas/style.css')) }}">
    @endpush
    @push('js')
        @include('principal.sidebar-menu')
    @endpush
@endonce

<x-canvas>
    <x-slot name="header">
        <title>Horneado - El Buen Sabor</title>
    </x-slot>

    <main>
        <x-sidebar></x-sidebar>
        <div class="body-content">
            <x-navegation></x-navegation>
            <div class="page-content">
                <div class="recipe-page">
                    <div class="recipe-page-heading">
                        <div>
                            <h1>Horneado</h1>
                            <p>{{ $preparacion->receta->nombre }} · Harina usada: {{ $preparacion->libras_planeadas ?? '—' }} lb · {{ $preparacion->unidades_planeadas }} panes estimados</p>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="recipe-feedback" role="status">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="recipe-feedback recipe-feedback-error" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <div class="recipe-table-container">
                        <table class="recipe-table">
                            <thead>
                                <tr><th>Ingrediente</th><th>Consumido</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($preparacion->proporciones as $proporcion)
                                    <tr>
                                        <td>{{ $proporcion->ingrediente->nombre ?? 'Ingrediente' }}</td>
                                        <td>{{ rtrim(rtrim(number_format((float) $proporcion->unidades, 3, '.', ''), '0'), '.') }} {{ $proporcion->medida }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($preparacion->unidades_horneadas === null)
                        <form method="POST" action="{{ route('preparaciones.hornear', $preparacion) }}" class="recipe-form-grid">
                            @csrf
                            @include('receta.partials.latas-fields')
                            <div class="recipe-form-group">
                                <label for="unidades-quemadas">Quemados o dañados</label>
                                <input type="number" id="unidades-quemadas" name="unidades_quemadas"
                                    min="0" step="1" value="{{ old('unidades_quemadas', 0) }}" required>
                            </div>
                            <div class="recipe-form-group">
                                <label>Unidades buenas</label>
                                <p id="unidades-buenas-preview" class="recipe-info-text">{{ $preparacion->unidades_planeadas }}</p>
                            </div>
                            <div class="recipe-modal-actions">
                                <button type="submit" class="recipe-primary-button">Guardar horneado y calcular costo</button>
                            </div>
                        </form>
                    @else
                        <div class="recipe-form-grid">
                            <div class="recipe-form-group">
                                <label>Latas horneadas</label>
                                <p class="recipe-info-text">{{ $preparacion->latas_horneadas ?? '—' }}</p>
                            </div>
                            <div class="recipe-form-group">
                                <label>Distribución por lata</label>
                                <p class="recipe-info-text">
                                    @forelse ($preparacion->detalle_latas ?? [] as $detalleLata)
                                        {{ $detalleLata['cantidad'] }} × {{ $detalleLata['panes_por_lata'] }} panes{{ !$loop->last ? ' · ' : '' }}
                                    @empty
                                        —
                                    @endforelse
                                </p>
                            </div>
                            <div class="recipe-form-group">
                                <label>Horneados</label>
                                <p class="recipe-info-text">{{ $preparacion->unidades_horneadas }}</p>
                            </div>
                            <div class="recipe-form-group">
                                <label>Quemados o dañados</label>
                                <p class="recipe-info-text">{{ $preparacion->unidades_quemadas }}</p>
                            </div>
                            <div class="recipe-form-group">
                                <label>Unidades buenas</label>
                                <p class="recipe-info-text">{{ $preparacion->unidades_producidas }}</p>
                            </div>
                            <div class="recipe-form-group">
                                <label>Costo total del lote</label>
                                <p class="recipe-info-text">${{ number_format((float) $preparacion->costo_total, 2) }}</p>
                            </div>
                            @if ($costoUnitario !== null)
                                <div class="recipe-form-group">
                                    <label>Costo por pan horneado</label>
                                    <p class="recipe-info-text">${{ number_format((float) $costoUnitario, 2) }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    @if ($preparacion->unidades_horneadas === null)
        <script>
            const trayBreakdown = document.querySelector('[data-tray-breakdown]');
            const burntInput = document.getElementById('unidades-quemadas');
            const goodOutput = document.getElementById('unidades-buenas-preview');
            const updateGoodOutput = () => {
                const baked = Number(trayBreakdown.querySelector('[data-tray-total]').textContent || 0);
                goodOutput.textContent = Math.max(0, baked - Number(burntInput.value || 0));
                burntInput.setCustomValidity(Number(burntInput.value || 0) > baked
                    ? 'Los dañados no pueden superar los panes horneados.'
                    : '');
            };
            trayBreakdown.addEventListener('tray-breakdown:change', updateGoodOutput);
            burntInput.addEventListener('input', updateGoodOutput);
            updateGoodOutput();
        </script>
    @endif
</x-canvas>