<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Usuario_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    private function buscar($campo, $valor)
    {
        $q = $this->db->select('u.id, u.empresa_id, u.nombre, u.password_hash, '
                .'e.nombre AS empresa_nombre, r.id AS rol_id, r.codigo AS rol_codigo, r.nombre AS rol_nombre')
            ->from('usuarios u')
            ->join('empresas e', 'e.id = u.empresa_id')
            ->join('roles r', 'r.id = u.rol_id')
            ->where($campo, $valor)->where('u.activo', 1)->where('e.activa', 1)->limit(1)->get();
        if ($q === FALSE) { show_error('No se pudo consultar la base de datos. Revisa la instalación.', 503); }
        $fila = $q->row_array();
        if (!$fila) { return NULL; }
        $fila['permisos'] = $this->permisos_del_rol((int) $fila['rol_id']);
        return $fila;
    }

    private function permisos_del_rol($rolId)
    {
        $q = $this->db->select('p.codigo')->from('permisos p')
            ->join('rol_permisos rp', 'rp.permiso_id = p.id')
            ->where('rp.rol_id', $rolId)->order_by('p.codigo')->get();
        if ($q === FALSE) { show_error('No se pudieron consultar los permisos del usuario.', 503); }
        return array_column($q->result_array(), 'codigo');
    }

    // Excepción al filtro tenant: identifica a quien inicia sesión por correo global único.
    public function por_correo($correo) { return $this->buscar('u.correo', $correo); }
    public function activo_por_id($id) { return $this->buscar('u.id', (int) $id); }
}
