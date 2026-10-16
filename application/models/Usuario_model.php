<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Usuario_model extends MY_Model
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

    /** Usuarios visibles para el administrador autenticado, siempre de su empresa. */
    public function listar_empresa()
    {
        $q = $this->db->select('u.id, u.nombre, u.correo, u.activo, r.codigo AS rol_codigo, r.nombre AS rol_nombre')
            ->from('usuarios u')->join('roles r', 'r.id = u.rol_id')
            ->where('u.empresa_id', $this->empresa_id())->order_by('u.nombre')->get();
        if ($q === FALSE) { show_error('No se pudieron consultar los usuarios.', 503); }
        return $q->result_array();
    }

    public function buscar_para_gestion($id)
    {
        $q = $this->db->select('u.id, u.nombre, u.correo, u.rol_id, u.activo, r.nombre AS rol_nombre')
            ->from('usuarios u')->join('roles r', 'r.id = u.rol_id')
            ->where('u.empresa_id', $this->empresa_id())->where('u.id', (int) $id)->limit(1)->get();
        if ($q === FALSE) { show_error('No se pudo consultar el usuario.', 503); }
        return $q->row_array() ?: NULL;
    }

    public function roles_disponibles()
    {
        $q = $this->db->select('id, codigo, nombre, descripcion')->from('roles')->order_by('id')->get();
        if ($q === FALSE) { show_error('No se pudieron consultar los roles.', 503); }
        return $q->result_array();
    }

    public function correo_disponible($correo)
    {
        $q = $this->db->select('id')->from('usuarios')->where('correo', $correo)->limit(1)->get();
        if ($q === FALSE) { show_error('No se pudo validar el correo.', 503); }
        return $q->num_rows() === 0;
    }

    public function crear_en_empresa($datos)
    {
        return $this->db->insert('usuarios', array(
            'empresa_id' => $this->empresa_id(),
            'rol_id' => (int) $datos['rol_id'],
            'nombre' => $datos['nombre'],
            'correo' => $datos['correo'],
            'password_hash' => $datos['password_hash'],
            'activo' => 1
        ));
    }

    public function actualizar_en_empresa($id, $nombre, $rolId)
    {
        return $this->db->where('empresa_id', $this->empresa_id())->where('id', (int) $id)
            ->update('usuarios', array('nombre' => $nombre, 'rol_id' => (int) $rolId));
    }

    public function cambiar_estado_en_empresa($id, $activo)
    {
        return $this->db->where('empresa_id', $this->empresa_id())->where('id', (int) $id)
            ->update('usuarios', array('activo' => $activo ? 1 : 0));
    }
}
