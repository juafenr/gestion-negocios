<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/** Formatea incluso sumas grandes de DECIMAL sin convertirlas a float. */
function negocio_moneda($valor)
{
    $valor=(string)$valor;
    if (!preg_match('/^([0-9]+)(?:\.([0-9]{1,2}))?$/D',$valor,$m)) { return 'Q —'; }
    return 'Q '.preg_replace('/\B(?=(\d{3})+(?!\d))/',',',$m[1]).'.'.str_pad($m[2] ?? '',2,'0');
}
