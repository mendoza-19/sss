<?php
namespace App\Services;

use Illuminate\Support\Facades\File;

class LogReaderService
{
    protected string $logPath;

    public function __construct(string $channel = 'auditoria')
    {
        $filename = config("logging.channels.{$channel}.with.stream") 
                    ?? storage_path('logs/laravel.log');
                    
        $this->logPath = $filename;
    }

    /**
     * Obtiene los logs de forma ultra optimizada usando fseek (Lee de atrás hacia adelante).
     */
    public function getLogs(int $limit = 10, int $offset = 0): array
    {
        if (!File::exists($this->logPath) || filesize($this->logPath) === 0) {
            return [];
        }

        $file = fopen($this->logPath, 'r');
        $lines = [];
        $lineCount = 0;
        
        // Mover el puntero al último byte del archivo
        fseek($file, -1, SEEK_END);
        
        $currentLine = '';
        $targetStart = $offset;
        $targetEnd = $offset + $limit;

        // Leer carácter por carácter hacia atrás
        while (ftell($file) > 0) {
            $char = fgetc($file);
            
            if ($char === "\n" || $char === "\r") {
                if (trim($currentLine) !== '') {
                    $lineCount++;
                    
                    // Si la línea pertenece al rango de paginación actual, la guardamos
                    if ($lineCount > $targetStart && $lineCount <= $targetEnd) {
                        $lines[] = strrev($currentLine);
                    }
                    
                    // Si ya completamos el límite requerido, rompemos el ciclo para ahorrar CPU
                    if ($lineCount >= $targetEnd) {
                        break;
                    }
                }
                $currentLine = '';
            } else {
                $currentLine .= $char;
            }
            
            // Retroceder dos posiciones (una por el carácter leído y otra para avanzar hacia atrás)
            fseek($file, -2, SEEK_CUR);
        }

        // Procesar la última línea restante del principio del archivo si aplica
        if (trim($currentLine) !== '' && $lineCount < $targetEnd) {
            $lineCount++;
            if ($lineCount > $targetStart) {
                $lines[] = strrev($currentLine);
            }
        }

        fclose($file);

        // Parsear las líneas obtenidas a objetos estructurados
        return $this->parseLogLines($lines);
    }

    /**
     * Cuenta el total de líneas sin cargar el archivo en memoria.
     */
    public function getTotalCount(): int
    {
        if (!File::exists($this->logPath)) {
            return 0;
        }

        // Método optimizado: Cuenta saltos de línea por bloques de buffer
        $file = fopen($this->logPath, 'r');
        $lines = 0;
        while (!feof($file)) {
            $lines += substr_count(fread($file, 8192), "\n");
        }
        fclose($file);

        return $lines;
    }

    /**
     * Convierte el texto plano en objetos limpios de PHP.
     */
    protected function parseLogLines(array $lines): array
    {
        $parsedLogs = [];
        $pattern = '/^\[(?P<date>.*?)\]\s(?P<channel>.*?)\.(?P<level>\w+):\s(?P<message>.*?)(?P<context>\s\{.*\}|)$/';

        foreach ($lines as $line) {
            if (preg_match($pattern, $line, $matches)) {
                $context = !empty($matches['context']) ? json_decode(trim($matches['context']), true) : [];

                $parsedLogs[] = (object) [
                    'date'    => $matches['date'],
                    'channel' => $matches['channel'],
                    'level'   => $matches['level'],
                    'message' => $matches['message'],
                    'context' => $context
                ];
            } else {
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
}
