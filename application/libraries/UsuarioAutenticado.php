<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Identidad resuelta desde la base de datos, nunca desde formularios. */
final class UsuarioAutenticado
{
    private $id;
    private $empresaId;
    private $nombre;
    private $empresaNombre;

    public function __construct(array $fila)
    {
        $this->id = (int) $fila['id'];
        $this->empresaId = (int) $fila['empresa_id'];
        $this->nombre = $fila['nombre'];
        $this->empresaNombre = $fila['empresa_nombre'];
    }
    public function id() { return $this->id; }
    public function empresaId() { return $this->empresaId; }
    public function nombre() { return $this->nombre; }
    public function empresaNombre() { return $this->empresaNombre; }
}
