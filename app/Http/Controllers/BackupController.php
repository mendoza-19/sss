<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\BackupDestination\BackupDestinationFactory;
use Spatie\Backup\Config\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;

class BackupController extends Controller
{
    // 1. LISTAR VERSIONES DESDE GOOGLE DRIVE
    public function index()
    {
        $backupsList = [];
        $disk = 'google';

        try {
            // 1. Validamos que el disco de Google Drive esté disponible
            if (Storage::disk($disk)) {

                // 2. Listamos todos los archivos dentro de la carpeta de Drive
                // Si tus respaldos se guardan dentro de una subcarpeta (ej: 'panaderia-sys'), 
                // pon Storage::disk($disk)->files('panaderia-sys');
                $files = Storage::disk($disk)->files(env('BACKUP_NAME', 'panaderia-sys'));

                foreach ($files as $file) {
                    // Filtramos para asegurarnos de listar únicamente los archivos .zip
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {

                        $backupsList[] = [
                            'disk' => $disk,
                            'path' => $file, // Ruta completa requerida por wnx/laravel-backup-restore
                            'size' => round(Storage::disk($disk)->size($file) / 1024 / 1024, 2) . ' MB',
                            // Convertimos la última fecha de modificación a un formato legible
                            'date' => Carbon::createFromTimestamp(Storage::disk($disk)->lastModified($file))
                                ->format('Y-m-d H:i:s'),
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            // Si hay un error de conexión con Google, lo registramos en los logs
            Log::error("Error al listar respaldos de Google Drive: " . $e->getMessage());
        }

        // Ordenamos el arreglo para que el respaldo más reciente aparezca arriba del todo
        $backupsList = array_reverse($backupsList);

        return view('admin.backups', compact('backupsList'));
    }



    // 2. DISPARAR RESPALDO MANUAL DESDE LA WEB
    public function create()
    {
        try {
            // Ejecuta el comando en segundo plano para no congelar la pantalla
            //Artisan::call('queue:clear'); // Limpiar la cola antes de agregar un nuevo trabajo
            //Artisan::queue('backup:run --only-db');
            Artisan::call('backup:run --only-db');
            return redirect()->back()->with('success', 'El respaldo se está generando en Google Drive...');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error al solicitar respaldo: ' . $e->getMessage());
        }
    }

    // 3. RESTAURACIÓN AUTOMÁTICA DESDE LA WEB
    public function restore(Request $request)
    {
        $request->validate([
            'disk' => 'required|string',
            'path' => 'required|string',
        ]);

        try {
            // wnx/laravel-backup-restore descarga el ZIP de Google Drive,
            // extrae el SQL y sobreescribe la base de datos local automáticamente.
            $exitCode = Artisan::call('backup:restore', [
                '--disk' => $request->input('disk'),
                '--backup' => $request->input('path'),
                '--no-interaction' => true // Evita que pida confirmación "Y/N" por consola
            ]);

            if ($exitCode === 0) {
                return redirect()->back()->with('success', '¡Base de datos restaurada correctamente!');
            }

            return redirect()->back()->with('error', 'La restauración falló (Código de salida distinto a cero).');
        } catch (Exception $e) {
            Log::error("Error crítico de restauración: " . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un problema: ' . $e->getMessage());
        }
    }
}
