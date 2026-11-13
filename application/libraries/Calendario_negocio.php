<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Fechas del negocio independientes de la zona horaria configurada en PHP/XAMPP. */
class Calendario_negocio
{
    public static function hoy(?DateTimeImmutable $ahora = NULL, $zona = 'America/Guatemala')
    {
        $zona = new DateTimeZone($zona);
        $ahora = $ahora ?? new DateTimeImmutable('now', $zona);
        return $ahora->setTimezone($zona)->setTime(0,0,0);
    }
}
