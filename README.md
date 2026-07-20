# laravel-kraftdo-shared

Código compartido por los sistemas del ecosistema KraftDo (NFC, Sitio, CRM, Hub).

Lo que vive aquí estaba duplicado byte a byte en los cuatro proyectos, porque
todos nacen de `scaffold-laravel-filament-pwa`. El scaffold da autonomía inicial;
este paquete recoge lo común cuando el proyecto entra al ecosistema.

## Contenido

| Módulo | Qué trae |
|---|---|
| `Onboarding\` | Tours guiados: modelos, controlador y migración |
| `Rut\` | `RutHelper` y la regla de validación `RutValido` |
| `Persona\` | Contrato, DTO y resolvers (local / API del maestro) |
| `Support\Coordenadas` | Utilidades de geolocalización |
| `Casts\EncryptedSeguro` | Cast cifrado tolerante a valores no cifrados |
| `Eco\ClienteEcosistema` | Cliente HTTP entre sistemas: bearer, timeout, reintentos y **degradación con gracia** |
| `Filament\` | Recursos comunes del panel + `KraftdoSharedPlugin` |

## Instalación

```bash
composer require kraftdo/laravel-kraftdo-shared
```

El service provider se auto-descubre. Para los recursos de Filament, registra el
plugin en el panel:

```php
->plugin(\Kraftdo\Shared\Filament\KraftdoSharedPlugin::make())
```

## Notas

- **`UserResource` no está aquí** a propósito: depende del modelo `User` de cada
  app, que sí difiere entre proyectos.
- Existe un paquete equivalente para el ecosistema municipal
  (`muni-graneros/laravel-muni-shared`). Son **deliberadamente separados**: son
  productos distintos con ciclos de vida distintos.
- Proyectos nuevos: `./scaffold new "Nombre" --shared=kraftdo` los cablea solo.
