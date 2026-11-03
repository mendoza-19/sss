<?php
namespace App\Services;

use Illuminate\Support\Facades\File;

class LogReaderServiceFileVersion
{
    protected string $logPath;

    public function __construct(string $channel = 'auditoria')
    {
        // Obtiene la ruta del archivo según el canal configurado en config/logging.php
        $filename = config("logging.channels.{$channel}.with.stream") 
                    ?? storage_path('logs/laravel.log');
                    
        $this->logPath = $filename;
    }

    /**
     * Obtiene los logs parseados como objetos con paginación manual.
     */
    public function getLogs(int $limit = 10, int $offset = 0): array
    {
        if (!File::exists($this->logPath)) {
            return [];
        }

        // Leer todas las líneas del archivo (Elimina líneas vacías al final)
        $lines = file($this->logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Opcional: Invertir el orden para ver los logs más recientes primero
        $lines = array_reverse($lines);
        
        // Aplicar el "desde dónde comenzar" (offset) y "cuántos obtener" (limit)
        $paginatedLines = array_slice($lines, $offset, $limit);

        $parsedLogs = [];

        // Expresión regular para capturar el formato estándar de Monolog:
        // [%datetime%] %channel%.%level_name%: %message% %context%
        $pattern = '/^\[(?P<date>.*?)\]\s(?P<channel>.*?)\.(?P<level>\w+):\s(?P<message>.*?)(?P<context>\s\{.*\}|)$/';

        foreach ($paginatedLines as $line) {
            if (preg_match($pattern, $line, $matches)) {
                
                // Intentar decodificar el contexto JSON si existe
                $context = !empty($matches['context']) ? json_decode(trim($matches['context']), true) : [];

                // Guardar como un objeto estructurado
                $parsedLogs[] = (object) [
                    'date'    => $matches['date'],
                    'channel' => $matches['channel'],
                    'level'   => $matches['level'],
                    'message' => $matches['message'],
                    'context' => $context
                ];
            } else {
                // Si la línea no coincide (ej: un salto de línea de un bloque de excepción), 
                // se añade como texto genérico para no perder la información.
                $parsedLogs[] = (object) [
                    'date'    => null,
                    'channel' => 'unknown',
                    'level'   => 'DEBUG',
                    'message' => $line,
                    'context' => []
                ];
            }
        }

        return $parsedLogs;
    }

    /**
     * Cuenta el total de logs para poder calcular la paginación en la vista.
     */
    public function getTotalCount(): int
    {
        if (!File::exists($this->logPath)) {
            return 0;
        }
        return count(file($this->logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    }
}
