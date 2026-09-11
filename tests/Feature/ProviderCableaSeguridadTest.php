<?php

use Kraftdo\Shared\KraftdoSharedServiceProvider;
use Kraftdo\Shared\Seguridad\CredencialesDePlantilla;

/**
 * Deuda diferida de la revisión de la Tarea 2: un test a nivel de arranque
 * que ejercita el cableado real del provider, no la clase aislada —eso ya lo
 * cubre tests/Seguridad/CredencialesDePlantillaTest.php—. Sin esto, alguien
 * podría borrar la llamada a `CredencialesDePlantilla::comprobar()` en
 * `KraftdoSharedServiceProvider::boot()` y ningún test lo notaría.
 */
afterEach(function () {
    app()['env'] = 'testing';
});

it('el register() del provider deja la config de credenciales de plantilla fusionada', function () {
    expect(config('credenciales-de-plantilla.valores'))
        ->toBe(CredencialesDePlantilla::POR_OMISION);
});

it('el boot() del provider hace cumplir el candado de credenciales de plantilla', function () {
    app()['env'] = 'production';
    config()->set('database.connections.testing.password', 'sistema_pass');

    // Instanciar el provider a mano y llamar boot() en vez de reusar el que ya
    // arrancó la app (con APP_ENV=testing, cuando el candado no dispara):
    // así se prueba que boot() de verdad ejecuta el candado, no solo que la
    // clase estática funciona sola —eso ya lo prueba CredencialesDePlantillaTest.
    expect(fn () => (new KraftdoSharedServiceProvider(app()))->boot())
        ->toThrow(RuntimeException::class, 'DB_PASSWORD');
});
