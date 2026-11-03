<?php

namespace App\Providers;

use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
/*use Masbug\FlysystemGoogleDrive\GoogleDriveAdapter;*/
use Masbug\Flysystem\GoogleDriveAdapter;
use Google\Service\Drive;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Filesystem\FilesystemAdapter;
use Spatie\Backup\Events\BackupWasSuccessful;
use Exception;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(DatabaseRefreshed::class, function (DatabaseRefreshed $event): void {
            if ($event->seeding || ! app()->environment('local')) {
                return;
            }

            Artisan::call('db:seed', array_filter([
                '--database' => $event->database,
                '--force' => true,
            ]));
        });
        /*try {
            Storage::extend('google', function ($app, $config) {
                $client = new GoogleClient();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);

                // Si el token expira, el cliente de Google lo refresca automáticamente
                $service = new \Google\Service\Drive($client);

                // Inicializamos el adaptador del paquete masbug
                $adapter = new GoogleDriveAdapter($service, $config['folder'] ?? '/');

                return new \Illuminate\Filesystem\FilesystemAdapter(
                    new Filesystem($adapter, $config),
                    $adapter,
                    $config
                );
            });
        } catch (Exception $e) {
            // Evita que la app muera por completo si las credenciales de Google están vacías al inicio
            Log::error('Error registrando driver de Google Drive: ' . $e->getMessage());
        }*/
        try {
            Storage::extend('google', function ($app, $config) {
                $options = [];
                if (! empty($config['teamDriveId'] ?? null)) {
                    $options['teamDriveId'] = $config['teamDriveId'];
                }
                $client = new GoogleClient();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);
                $client->setApplicationName('Cliente web 1');
                $client->setAccessType('offline');

                $tokenResponse = $client->fetchAccessTokenWithRefreshToken($config['refreshToken']);
                if (isset($tokenResponse['error'])) {
                    throw new \InvalidArgumentException('Error de Google Auth: ' . $tokenResponse['error_description']);
                }

                $client->setAccessToken($tokenResponse);


                $service = new Drive($client);
                $adapter = new GoogleDriveAdapter($service, $config['folder']);

                $flysystem = new Filesystem($adapter, $config);

                return new FilesystemAdapter($flysystem, $adapter, $config);
            });
        } catch (Exception $e) {
            Log::error('Error registrando driver de Google Drive: ' . $e->getMessage());
        }

        //escuchamos el evento de respaldo exitoso para subir el archivo a Google Drive
        Event::listen(BackupWasSuccessful::class, function (BackupWasSuccessful $event) {
            // 1. Obtenemos la ruta local del archivo .zip que Spatie acaba de crear
            $localPath = $event->backupDestination->newestBackup()->path();

            // 2. Extraemos el nombre del archivo (Ej: panaderia-sys/backup-2026-11-02.zip)
            if (Storage::disk('local')->exists($localPath)) {
                $fileStream = Storage::disk('local')->readStream($localPath);

                // 3. Lo subimos de manera limpia a Google Drive usando Streams (eficiente con memoria)
                Storage::disk('google')->writeStream($localPath, $fileStream);

                // (Opcional) Si no deseas acumular archivos locales en tu PC, puedes borrarlo tras subirlo:
                Storage::disk('local')->delete($localPath);
            }
        });
    }
}
