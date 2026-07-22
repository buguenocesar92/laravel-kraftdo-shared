<?php

use Kraftdo\Shared\Persona\PersonaDTO;

describe('fromArray', function () {
    it('normaliza el RUT al construir desde un arreglo', function () {
        $dto = PersonaDTO::fromArray([
            'nro_documento' => '14.523.681-9',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
        ]);

        expect($dto->nroDocumento)->toBe('14523681-9')
            ->and($dto->nombres)->toBe('Ana')
            ->and($dto->apellidos)->toBe('Pérez');
    });

    it('usa "api" como source por defecto y system cae al source', function () {
        $dto = PersonaDTO::fromArray(['nro_documento' => '145236819']);

        expect($dto->source)->toBe('api')
            ->and($dto->system)->toBe('api');
    });

    it('respeta un system explícito distinto del source', function () {
        $dto = PersonaDTO::fromArray([
            'nro_documento' => '145236819',
            'source' => 'local',
            'system' => 'disc',
        ]);

        expect($dto->source)->toBe('local')
            ->and($dto->system)->toBe('disc');
    });

    it('deja en null los campos opcionales ausentes', function () {
        $dto = PersonaDTO::fromArray(['nro_documento' => '145236819']);

        expect($dto->email)->toBeNull()
            ->and($dto->telefono)->toBeNull()
            ->and($dto->fechaNacimiento)->toBeNull();
    });
});

describe('toArray', function () {
    it('produce las claves con nombres de columna (snake_case)', function () {
        $dto = PersonaDTO::fromArray([
            'nro_documento' => '145236819',
            'nombres' => 'Ana',
            'email' => 'ana@example.cl',
        ]);

        expect($dto->toArray())
            ->toHaveKeys(['nro_documento', 'nombres', 'email', 'direccion'])
            ->and($dto->toArray()['nro_documento'])->toBe('14523681-9')
            ->and($dto->toArray()['email'])->toBe('ana@example.cl');
    });
});
