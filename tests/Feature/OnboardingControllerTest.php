<?php

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Kraftdo\Shared\Onboarding\OnboardingController;
use Kraftdo\Shared\Onboarding\OnboardingStep;
use Kraftdo\Shared\Onboarding\OnboardingTour;

uses(RefreshDatabase::class);

/**
 * El título y la descripción de cada paso los escribe quien administra el
 * tour desde el panel (`OnboardingTourResource`), no el usuario final que lo
 * ve. Si un admin comprometido (o una cuenta con permiso mal asignado) guarda
 * un paso con `<script>`, ese HTML viajaba tal cual al JSON que consume el
 * front y se ejecutaba en el navegador de TODOS los que vieran el tour: un
 * XSS almacenado con alcance amplio, no acotado a quien lo escribió.
 */
function crearTourConPaso(string $titulo, string $descripcion): OnboardingTour
{
    $tour = OnboardingTour::create([
        'name' => 'Tour de prueba',
        'system' => 'hub',
        'role' => 'cualquiera',
        'surface' => 'app',
        'active' => true,
    ]);

    OnboardingStep::create([
        'tour_id' => $tour->id,
        'order' => 1,
        'title' => $titulo,
        'description' => $descripcion,
        'position' => 'bottom',
    ]);

    return $tour;
}

function pedirTour(): array
{
    $usuario = new AuthUser;
    $usuario->id = 1;

    $request = Request::create('/onboarding/tour', 'GET', ['system' => 'hub', 'surface' => 'app']);
    $request->setUserResolver(fn () => $usuario);

    $respuesta = (new OnboardingController)->tour($request);

    return $respuesta->getData(true);
}

it('escapa el HTML del título y la descripción del paso antes de mandarlos al front', function () {
    crearTourConPaso('<script>alert(1)</script>', '<img src=x onerror=alert(2)>');

    $datos = pedirTour();

    expect($datos['steps'][0]['title'])->toBe('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->and($datos['steps'][0]['description'])->toBe('&lt;img src=x onerror=alert(2)&gt;')
        ->and($datos['steps'][0]['title'])->not->toContain('<script>')
        ->and($datos['steps'][0]['description'])->not->toContain('<img');
});

it('deja pasar texto normal sin alterarlo más allá de las entidades HTML', function () {
    crearTourConPaso('Bienvenido', 'Este es el primer paso');

    $datos = pedirTour();

    expect($datos['steps'][0]['title'])->toBe('Bienvenido')
        ->and($datos['steps'][0]['description'])->toBe('Este es el primer paso');
});
