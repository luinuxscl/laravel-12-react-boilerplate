# Guía de Windsurf para Proyecto Laravel

## Configuración del Proyecto
- **PHP**: 8.3.6
- **Laravel Framework**: ^12.0
- **Inertia.js**: ^2.0
- **React**: ^19.0
- **Tailwind CSS**: ^4.0
- **Pest PHP**: ^3.0

## Convenciones de Código
- **PHP**: PSR-12
- **JavaScript/TypeScript**: ESLint + Prettier
- **CSS**: Tailwind CSS con clases utilitarias
- **React**: Componentes funcionales con Hooks
- **Rutas**: Nombradas con notación kebab-case

## Estructura del Proyecto
```
resources/
  ├── js/
  │   ├── Components/     # Componentes reutilizables
  │   ├── Layouts/        # Layouts de la aplicación
  │   ├── Pages/          # Páginas de Inertia
  │   └── app.tsx         # Punto de entrada de la aplicación
  └── css/
      └── app.css         # Estilos globales
```

## Flujo de Trabajo
1. Crear migraciones para cambios en la base de datos
2. Generar modelos y controladores con sus pruebas
3. Desarrollar componentes de React
4. Escribir pruebas unitarias y de características
5. Revisar con PHPStan y ESLint
6. Ejecutar pruebas antes de hacer commit

## Comandos Útiles
```bash
# Instalar dependencias
composer install
npm install

# Desarrollo
npm run dev
php artisan serve

# Construir para producción
npm run build

# Ejecutar pruebas
php artisan test
./vendor/bin/pest
```

## Integración con Windsurf
- Usa `@[ruta/archivo]` para referenciar archivos
- Revisa los cambios propuestos antes de aplicarlos
- Usa la vista previa para ver cambios en tiempo real

## Mejores Prácticas
- Escribe pruebas para nueva funcionalidad
- Mantén los componentes pequeños y enfocados
- Documenta componentes complejos
- Usa TypeScript para tipado fuerte
- Sigue las convenciones de Laravel para nombres de archivos

## Solución de Problemas
- Si los cambios no se ven: `npm run build` o `npm run dev`
- Para problemas de caché: `php artisan cache:clear`
- Para regenerar autoload: `composer dump-autoload`
