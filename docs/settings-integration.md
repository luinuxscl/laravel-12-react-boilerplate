# Integración de paquetes con settings del boilerplate

Este boilerplate expone comandos Artisan para leer/escribir settings de aplicación, ideales para ser usados por paquetes durante su instalación o actualización.

## Comandos disponibles

- `php artisan settings:set key value [--json]`
  - Ejemplos:
    - `php artisan settings:set site.name "Mi App"`
    - `php artisan settings:set site.appearance '{"theme":"dark"}' --json`
- `php artisan settings:import path/to/file.json [--prefix=site] [--dry]`
  - El JSON debe ser un objeto "key => value".
  - `--prefix` permite anteponer un prefijo a todas las claves.

## Ejemplo para un paquete: publicar un JSON y comando de instalación

1) Publicar un archivo JSON con los settings por defecto del paquete.

```php
// src/Providers/MyPackageServiceProvider.php
namespace Vendor\Package\Providers;

use Illuminate\Support\ServiceProvider;

class MyPackageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publicar archivo JSON con settings del paquete
        $this->publishes([
            __DIR__.'/../../stubs/package-settings.json' => base_path('config/vendor-package-settings.json'),
        ], 'vendor-package-settings');
    }
}
```

Estructura JSON (ejemplo):

```json
{
  "brand.logo_url": "/storage/branding/logo.png",
  "brand.favicon_url": "/storage/branding/favicon.ico",
  "appearance": { "theme": "system" }
}
```

2) (Opcional) Comando del paquete para importar settings usando los comandos del boilerplate.

```php
// src/Console/Commands/InstallPackage.php
namespace Vendor\Package\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallPackage extends Command
{
    protected $signature = 'vendor:package:install {--path=config/vendor-package-settings.json}';

    protected $description = 'Instala el paquete y aplica settings por defecto.';

    public function handle(): int
    {
        $path = (string) $this->option('path');

        // Llama al comando del boilerplate para importar
        $this->call('settings:import', [
            'path' => $path,
            '--prefix' => 'site', // opcional
        ]);

        $this->info('Vendor package instalado. Settings importados.');
        return self::SUCCESS;
    }
}
```

Registrar el comando en el ServiceProvider del paquete:

```php
public function register(): void
{
    if ($this->app->runningInConsole()) {
        $this->commands([\Vendor\Package\Console\Commands\InstallPackage::class]);
    }
}
```

3) Flujo recomendado para el usuario final del paquete

- Publicar el archivo de settings del paquete:

```bash
php artisan vendor:publish --tag=vendor-package-settings
```

- Ejecutar comando de instalación del paquete (si existe):

```bash
php artisan vendor:package:install
```

- O importar directamente usando el comando del boilerplate:

```bash
php artisan settings:import config/vendor-package-settings.json --prefix=site
```

## Uso desde scripts de Composer (app)

Si el paquete requiere preparar settings automáticamente después de instalarse, puedes documentar que el PROYECTO (no el paquete) agregue un script en `composer.json`:

```json
{
  "scripts": {
    "post-autoload-dump": [
      "@php artisan settings:import config/vendor-package-settings.json --prefix=site || true"
    ]
  }
}
```

Nota: Es preferible no ejecutar comandos Artisan automáticamente desde el paquete para respetar el control del proyecto anfitrión.

## Consideraciones

- Los settings se guardan vía `App\\Facades\\Settings`.
- Prefiere rutas públicas relativas (`/storage/...`) para evitar desalineación de host/puerto en desarrollo.
- Para íconos/imágenes servidas desde `/storage`, asegúrate de ejecutar `php artisan storage:link`.
