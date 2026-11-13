<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Empresa_model extends MY_Model
{
    public function __construct() { parent::__construct(); $this->load->database(); }
    public function propina_porcentaje()
    {
        $fila = $this->db->select('propina_porcentaje')
            ->where('id', $this->empresa_id())->get('empresas')->row_array();
        if (!$fila) { throw new RuntimeException('Empresa no encontrada.'); }
        return (int) $fila['propina_porcentaje'];
    }

    public function cambiar_propina($porcentaje)
    {
        if (!in_array($porcentaje, array('0', '10'), TRUE)) {
            return 'Selecciona 0 % o 10 %.';
        }
        $ok = $this->db->where('id', $this->empresa_id())
            ->update('empresas', array('propina_porcentaje' => (int) $porcentaje));
        return $ok ? NULL : 'No se pudo guardar la configuración.';
    }
}
