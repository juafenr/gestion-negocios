<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Usuario_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('Validacion_negocio');
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

    // Excepción al filtro tenant: ambos identificadores de acceso son globalmente únicos.
    public function por_correo($correo) { return $this->buscar('u.correo', $correo); }
    public function por_nombre_usuario($nombreUsuario) { return $this->buscar('u.nombre_usuario', $nombreUsuario); }
    public function activo_por_id($id) { return $this->buscar('u.id', (int) $id); }

    /** Usuarios visibles para el administrador autenticado, siempre de su empresa. */
    public function listar_empresa()
    {
        $q = $this->db->select('u.id, u.nombre, u.nombre_usuario, u.correo, u.activo, r.codigo AS rol_codigo, r.nombre AS rol_nombre')
            ->from('usuarios u')->join('roles r', 'r.id = u.rol_id')
            ->where('u.empresa_id', $this->empresa_id())->order_by('u.nombre')->get();
        if ($q === FALSE) { show_error('No se pudieron consultar los usuarios.', 503); }
        return $q->result_array();
    }

    public function buscar_para_gestion($id)
    {
        $q = $this->db->select('u.id, u.nombre, u.nombre_usuario, u.correo, u.rol_id, u.activo, r.nombre AS rol_nombre')
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

    public function nombre_usuario_disponible($nombreUsuario, $exceptoId = NULL)
    {
        $this->db->select('id')->from('usuarios')->where('nombre_usuario', $nombreUsuario);
        if ($exceptoId !== NULL) { $this->db->where('id !=', (int) $exceptoId); }
        $q = $this->db->limit(1)->get();
        if ($q === FALSE) { show_error('No se pudo validar el nombre de usuario.', 503); }
        return $q->num_rows() === 0;
    }

    public function crear_en_empresa($datos)
    {
        try {
            if (!is_array($datos)) { return FALSE; }
            $validos = Validacion_negocio::usuario($datos);
            $hash = $datos['password_hash'] ?? NULL;
            if (!is_string($hash) || (password_get_info($hash)['algoName'] ?? 'unknown') === 'unknown') { return FALSE; }
            if (!in_array($validos['rol_id'], array_map('intval', array_column($this->roles_disponibles(), 'id')), TRUE)) { return FALSE; }
            $datos = array_merge($datos, $validos);
        } catch (InvalidArgumentException $e) { return FALSE; }
        return $this->db->insert('usuarios', array(
            'empresa_id' => $this->empresa_id(),
            'rol_id' => (int) $datos['rol_id'],
            'nombre' => $datos['nombre'],
            'nombre_usuario' => $datos['nombre_usuario'],
            'correo' => $datos['correo'],
            'password_hash' => $datos['password_hash'],
            'activo' => 1
        ));
    }

    public function actualizar_en_empresa($id, $nombre, $nombreUsuario, $rolId)
    {
        try {
            $id = Validacion_negocio::id($id);
            $d = Validacion_negocio::usuario(array('nombre'=>$nombre, 'nombre_usuario'=>$nombreUsuario, 'rol_id'=>$rolId), FALSE);
            if (!in_array($d['rol_id'], array_map('intval', array_column($this->roles_disponibles(), 'id')), TRUE)) { return FALSE; }
            $nombre=$d['nombre']; $nombreUsuario=$d['nombre_usuario']; $rolId=$d['rol_id'];
        } catch (InvalidArgumentException $e) { return FALSE; }
        return $this->db->where('empresa_id', $this->empresa_id())->where('id', (int) $id)
            ->update('usuarios', array('nombre' => $nombre, 'nombre_usuario' => $nombreUsuario, 'rol_id' => (int) $rolId));
    }

    public function cambiar_estado_en_empresa($id, $activo)
    {
        return $this->db->where('empresa_id', $this->empresa_id())->where('id', (int) $id)
            ->update('usuarios', array('activo' => $activo ? 1 : 0));
    }
}
