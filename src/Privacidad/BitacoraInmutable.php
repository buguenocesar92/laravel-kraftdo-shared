<?php

namespace Kraftdo\Shared\Privacidad;

use DomainException;

/**
 * Se lanza al intentar alterar una entrada de la bitácora.
 *
 * La bitácora es lo que la organización muestra ante una fiscalización: un registro
 * de evidencia que se puede editar no acredita nada.
 */
class BitacoraInmutable extends DomainException {}
