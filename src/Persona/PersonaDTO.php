<?php

namespace Kraftdo\Shared\Persona;

use Kraftdo\Shared\Rut\RutHelper;

/**
 * Representación canónica e inmutable de una persona, independiente de la fuente.
 */
class PersonaDTO
{
    public function __construct(
        public readonly string $nroDocumento,
        public readonly string $nombres,
        public readonly string $apellidos,
        public readonly ?string $fechaNacimiento,
        public readonly ?string $sexo,
        public readonly ?string $telefono,
        public readonly ?string $email,
        public readonly ?string $direccion,
        public readonly ?string $sector,
        public readonly string $source,  // local|api|etc
        public readonly string $system,
        public readonly ?string $fechaRegistro = null,
        public readonly ?string $tipoDocumento = null,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        $source = $data['source'] ?? 'api';

        return new self(
            nroDocumento: RutHelper::normalize($data['nro_documento'] ?? ''),
            nombres: $data['nombres'] ?? '',
            apellidos: $data['apellidos'] ?? '',
            fechaNacimiento: $data['fecha_nacimiento'] ?? null,
            sexo: $data['sexo'] ?? null,
            telefono: $data['telefono'] ?? null,
            email: $data['email'] ?? null,
            direccion: $data['direccion'] ?? null,
            sector: $data['sector'] ?? null,
            source: $source,
            system: $data['system'] ?? $source,
            fechaRegistro: $data['fecha_registro'] ?? null,
            tipoDocumento: $data['tipo_documento'] ?? null,
        );
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'nro_documento' => $this->nroDocumento,
            'tipo_documento' => $this->tipoDocumento,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'fecha_nacimiento' => $this->fechaNacimiento,
            'sexo' => $this->sexo,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'sector' => $this->sector,
            'fecha_registro' => $this->fechaRegistro,
        ];
    }
}
