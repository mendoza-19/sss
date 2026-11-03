<div>
    <h1>Panel de Control de Respaldos (Google Drive)</h1>

    <!-- Mensajes de estado -->
    @if(session('success')) <div style="color: green;">{{ session('success') }}</div> @endif
    @if(session('error')) <div style="color: red;">{{ session('error') }}</div> @endif

    <!-- Botón para forzar respaldo -->
    <form action="{{ route('backups.create') }}" method="POST" style="margin-bottom: 20px;">
        @csrf
        <button type="submit" style="padding: 10px; background: blue; color: white;">Generar Nuevo Respaldo Ahora</button>
    </form>

    <!-- Tabla de versiones -->
    <table border="1" cellpadding="10" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #eee;">
                <th>Fecha de Creación</th>
                <th>Nombre del Archivo</th>
                <th>Tamaño</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($backupsList as $backup)
                <tr>
                    <td>{{ $backup['date'] }}</td>
                    <td><code>{{ $backup['path'] }}</code></td>
                    <td>{{ $backup['size'] }}</td>
                    <td>
                        <form action="{{ route('backups.restore') }}" method="POST" onsubmit="return confirm('ATENCIÓN: Se destruirán los datos actuales para montar esta versión. ¿Proceder?');">
                            @csrf
                            <input type="hidden" name="disk" value="{{ $backup['disk'] }}">
                            <input type="hidden" name="path" value="{{ $backup['path'] }}">
                            <button type="submit" style="background: red; color: white; cursor: pointer;">Restaurar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No se encontraron respaldos en tu unidad de Google Drive.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
