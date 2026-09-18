<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Base de modelos de negocio: obtener siempre la empresa autenticada. */
class MY_Model extends CI_Model
{
    protected function empresa_id()
    {
        $ci =& get_instance();
        $ci->load->library('Autenticacion');
        $usuario = $ci->autenticacion->usuario_actual();
        if ($usuario === NULL) { show_error('Autenticación requerida.', 401); }
        return $usuario->empresaId();
    }
}
