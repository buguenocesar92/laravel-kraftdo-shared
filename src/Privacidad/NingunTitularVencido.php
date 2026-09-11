<?php

namespace Kraftdo\Shared\Privacidad;

use Kraftdo\Shared\Privacidad\Contratos\ResuelveTitularesVencidos;
use Kraftdo\Shared\Privacidad\Contratos\TitularDeDatos;
use Kraftdo\Shared\Privacidad\Modelos\Finalidad;

/**
 * Enlace por defecto del contrato. Un sistema recién instalado que todavía no
 * definió desde cuándo trata a cada titular no debe reventar al correr el
 * comando: debe no purgar nada, que es el fallo seguro.
 */
class NingunTitularVencido implements ResuelveTitularesVencidos
{
    /** @return iterable<int, TitularDeDatos> */
    public function vencidos(Finalidad $finalidad): iterable
    {
        return [];
    }
}
