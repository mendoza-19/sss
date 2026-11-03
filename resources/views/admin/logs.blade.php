<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="container mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Visor de Logs del Sistema</h1>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full leading-normal">
            <thead>
                <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase">
                    <th class="px-5 py-3">Fecha</th>
                    <th class="px-5 py-3">Nivel</th>
                    <th class="px-5 py-3">Mensaje</th>
                    <th class="px-5 py-3">Contexto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr class="border-b border-gray-200 text-sm">
                        <td class="px-5 py-4 whitespace-no-wrap text-gray-500">{{ $log->date }}</td>
                        <td class="px-5 py-4">
                            <!-- Color dinámico según el nivel de severidad -->
                            <span class="px-2 py-1 font-semibold rounded-full text-xs
                                {{ $log->level === 'ERROR' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $log->level === 'INFO' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $log->level === 'WARNING' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                {{ $log->level }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-gray-800">{{ $log->message }}</td>
                        <td class="px-5 py-4 text-gray-500">
                            @if(!empty($log->context))
                                <pre class="text-xs bg-gray-50 p-2 rounded">{{ json_encode($log->context, JSON_PRETTY_PRINT) }}</pre>
                            @else
                                <span class="text-gray-400 font-italic">N/A</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Controles de Navegación Manual -->
    <div class="mt-4 flex justify-between items-center">
        <span>Mostrando {{ count($logs) }} de {{ $total }} registros</span>
        
        <div class="inline-flex">
            <!-- Botón Anterior -->
            @if($offset > 0)
                <a href="{{ route('admin.logs', ['limit' => $limit, 'offset' => max(0, $offset - $limit)]) }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-l">
                    Anterior
                </a>
            @endif

            <!-- Botón Siguiente -->
            @if($offset + $limit < $total)
                <a href="{{ route('admin.logs', ['limit' => $limit, 'offset' => $offset + $limit]) }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-r">
                    Siguiente
                </a>
            @endif
        </div>
    </div>
</div>

</body>
</html>