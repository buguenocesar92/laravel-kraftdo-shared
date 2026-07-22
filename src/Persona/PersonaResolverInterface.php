<?php

namespace Kraftdo\Shared\Persona;

/**
 * Contrato del resolvedor de personas.
 *
 * Cualquier implementación (BD local hoy, API centralizada mañana) cumple este contrato.
 * Los sistemas consumidores dependen solo de esta interfaz.
 */
interface PersonaResolverInterface
{
    /**
     * Buscar persona por documento/RUT.
     */
    public function findByRut(string $rut): ?PersonaDTO;

    /**
     * Verificar si un documento/RUT ya existe.
     */
    public function existsByRut(string $rut): bool;

    /**
     * Retorna el origen del dato encontrado.
     */
    public function getSource(): string;
}
