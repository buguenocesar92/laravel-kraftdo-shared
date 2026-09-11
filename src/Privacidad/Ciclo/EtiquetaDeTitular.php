<?php

namespace Kraftdo\Shared\Privacidad\Ciclo;

use Kraftdo\Shared\Privacidad\Contratos\TitularDeDatos;
use Kraftdo\Shared\Privacidad\Modelos\Solicitud;

/**
 * Cómo se nombra a un titular en pantalla.
 *
 * Siempre por el contrato, nunca por columnas del adoptante, y con el documento
 * TAL COMO lo devuelve `titularDocumento()`: hay registros con pasaporte y hay
 * sistemas que no identifican por RUT, y darles formato de RUT los vuelve
 * irreconocibles en el buscador.
 */
final class EtiquetaDeTitular
{
    /**
     * El titular es un morph y puede estar huérfano: la anonimización por
     * retención anula `titular_id` a propósito.
     */
    public static function estaAnonimizada(Solicitud $solicitud): bool
    {
        return $solicitud->getAttribute('titular_id') === null;
    }

    public static function deLaSolicitud(Solicitud $solicitud): string
    {
        if (self::estaAnonimizada($solicitud)) {
            return 'Caso anonimizado';
        }

        $titular = $solicitud->titular;

        if ($titular === null) {
            // `titular_id` existe pero el registro relacionado ya no: huérfano
            // sin haber pasado por la anonimización del módulo.
            return 'Titular no disponible';
        }

        if ($titular instanceof TitularDeDatos) {
            return $titular->titularNombre().' ('.$titular->titularDocumento().')';
        }

        // El morph siempre resuelve a un Model (lo declara `Solicitud::titular()`):
        // no hay una tercera clase de valor que llegue hasta acá.
        return class_basename($titular).' #'.$titular->getKey();
    }

    public static function de(?TitularDeDatos $titular): ?string
    {
        return $titular === null
            ? null
            : $titular->titularNombre().' — '.$titular->titularDocumento();
    }
}
