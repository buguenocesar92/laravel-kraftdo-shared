<?php

namespace Kraftdo\Shared\Rut;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regla de validación de RUT/RUN chileno.
 */
class RutValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! RutHelper::validate($value)) {
            $fail('El campo :attribute no es un RUT/RUN válido.');
        }
    }

    public static function formatear(string $rut): string
    {
        return RutHelper::format($rut);
    }
}
