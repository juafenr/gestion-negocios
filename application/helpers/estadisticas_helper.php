<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Los floats se usan solo para geometría; los importes visibles provienen de DECIMAL en SQL. */
function estadistica_numero($valor)
{
    return number_format((float) $valor, 2, '.', '');
}
function estadistica_fecha($fecha)
{
    return substr($fecha,8,2).'/'.substr($fecha,5,2);
}
function estadistica_escala($valor)
{
    if ($valor >= 1000000000) { return number_format($valor / 1000000000,1).' mil M'; }
    if ($valor >= 1000000) { return number_format($valor / 1000000,1).' M'; }
    if ($valor >= 1000) { return number_format($valor / 1000,1).' mil'; }
    return number_format($valor, $valor < 10 && $valor > 0 ? 2 : 0);
}
