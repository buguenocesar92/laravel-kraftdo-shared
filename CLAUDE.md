# CLAUDE.md — laravel-kraftdo-shared

Paquete Composer **compartido** por los cuatro sistemas KraftDo (nfc-v2, sitio, crm, hub). Un
cambio aquí afecta a los cuatro a la vez: trátalo con más cuidado que a un repo de aplicación.

## Qué contiene

- `Rut/` — `RutHelper` (normalizar/formatear/validar RUT, dígito verificador módulo 11) y la regla
  `RutValido` que delega en él.
- `Support/Coordenadas` — parser de coordenadas pegadas desde Google Maps.
- `Persona/` — `PersonaDTO`, `PersonaResolverInterface` y dos resolvers (`Local`, `Api`).
- `Onboarding/` — tours de bienvenida (modelo, pasos, progreso, controlador).
- `Filament/` — `KraftdoSharedPlugin` + recursos (Activity, OnboardingTour).
- `Casts/EncryptedSeguro` — cast para cifrar PII en reposo.

## Sesiones en paralelo (Claude Code / Gemini Antigravity / terminal manual)

Varias sesiones de Claude Code y Gemini Antigravity trabajan sobre las mismas
copias de trabajo de `~/Dev`, esta incluida. Antes de tocar nada:
`~/Dev/scripts/sesion estado .`. Al empezar: `sesion tomar . "qué vas a
hacer"`. Al terminar: `sesion soltar .`. No bloquea — es un aviso — pero si el
marcador es ajeno, mirá `git log --oneline -5` y `git status` antes de cualquier
`reset`/checkout, y commiteá siempre con `git commit --only -- <rutas>` (el índice
es compartido). Detalle y motivo en `~/Dev/CLAUDE.md`.

## Desarrollo

```bash
composer install     # deps públicas (Laravel + Pest + Testbench)
composer test        # o ./vendor/bin/pest
```

Los tests usan **Orchestra Testbench** (no hay app Laravel real): `tests/TestCase.php` registra
`KraftdoSharedServiceProvider`. La lógica pura (RUT, coordenadas, DTO) se testea sin base de datos.

## Publicar una versión

Es un paquete **privado** consumido vía `vcs`. Los cuatro sistemas lo requieren como `^1.0`.

- Cambios compatibles → tag `1.x`.
- **Quitar o renombrar una clase pública es incompatible → exige `2.0.0`** y actualizar la
  restricción en los cuatro consumidores. Ver la deuda de poda abajo.

## Deuda conocida

- **5 clases sin consumidor** (verificado 2026-07-22): `ClienteEcosistema`, `Coordenadas`,
  `RutValido`, `EncryptedSeguro`, `ApiPersonaResolver`. Poda pendiente como `2.0.0`:
  - Borrar `ClienteEcosistema` (no encaja en ninguno de los 4 clientes reales), `Coordenadas` y
    `RutValido`.
  - **Conservar** `ApiPersonaResolver` (2ª implementación de una interfaz en uso) y
    `EncryptedSeguro` (habrá PII pronto).
- **Sin CI** todavía: los tests corren en local pero no hay workflow que los ejecute en cada push.

## Nota del ecosistema (no es de este repo, pero conviene recordarlo)

**El producto KraftDo aún no puede cobrar**: `WEBPAY_ENV` está en `fake` en nfc-v2 y falta
configurar las credenciales de Transbank. Es el bloqueante de negocio nº1 del ecosistema.
Detalle completo en `~/Dev/AUDITORIA_KRAFTDO.md` (mientras exista) o en el CLAUDE.md del hub.
