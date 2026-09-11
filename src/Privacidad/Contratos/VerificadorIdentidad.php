<?php

namespace Kraftdo\Shared\Privacidad\Contratos;

use Kraftdo\Shared\Privacidad\ResultadoVerificacion;

/**
 * La costura que permite un único flujo ARCOP para sistemas que verifican
 * identidad de formas distintas: cédula presencial donde no hay cuentas de
 * titular, sesión autenticada o Keycloak donde sí las hay.
 *
 * El módulo registra CÓMO se verificó; no decide cómo verificar.
 */
interface VerificadorIdentidad
{
    /** @param array<string, mixed> $contexto */
    public function verificar(array $contexto): ResultadoVerificacion;
}
