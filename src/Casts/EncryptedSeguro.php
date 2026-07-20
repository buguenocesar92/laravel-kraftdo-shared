<?php

namespace Kraftdo\Shared\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Cifrado en reposo resiliente.
 *
 * A diferencia del cast nativo `encrypted` (que lanza DecryptException ante
 * cualquier valor que no sea texto cifrado válido), este cast degrada con
 * elegancia: si el valor todavía está en texto plano (fila heredada que aún no
 * pasó por la migración, o escrita por una ruta que evita el modelo), lo devuelve
 * tal cual en vez de hacer crashear la vista.
 *
 * - Escritura: cifra siempre (los datos quedan cifrados en reposo).
 * - Lectura: descifra; si no es descifrable, devuelve el valor crudo.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class EncryptedSeguro implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value; // texto plano heredado: no romper la lectura
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return Crypt::encryptString($value);
    }
}
