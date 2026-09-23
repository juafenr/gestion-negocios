<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Identidad resuelta desde la base de datos, nunca desde formularios. */
final class UsuarioAutenticado
{
    private $id;
    private $empresaId;
    private $nombre;
    private $empresaNombre;
    private $rolCodigo;
    private $rolNombre;
    private $permisos;

    public function __construct(array $fila)
    {
        $this->id = (int) $fila['id'];
        $this->empresaId = (int) $fila['empresa_id'];
        $this->nombre = $fila['nombre'];
        $this->empresaNombre = $fila['empresa_nombre'];
        $this->rolCodigo = $fila['rol_codigo'];
        $this->rolNombre = $fila['rol_nombre'];
        // Un conjunto asociativo permite comprobar permisos sin recorrer toda la lista.
        $this->permisos = array_fill_keys($fila['permisos'], TRUE);
    }
    public function id() { return $this->id; }
    public function empresaId() { return $this->empresaId; }
    public function nombre() { return $this->nombre; }
    public function empresaNombre() { return $this->empresaNombre; }
    public function rol() { return $this->rolCodigo; }
    public function rolNombre() { return $this->rolNombre; }
    public function puede($permiso)
    {
        return is_string($permiso) && isset($this->permisos[$permiso]);
    }
    public function permisos() { return array_keys($this->permisos); }
}
