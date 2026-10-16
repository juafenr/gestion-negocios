<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Límite persistente: no se reinicia borrando las cookies. */
class Intento_model extends CI_Model
{
    public function __construct() { parent::__construct(); $this->load->database(); }

    public function reservar($correo, $ip)
    {
        $ahora = time();
        $buckets = array(hash('sha256', 'correo:'.$correo) => 5, hash('sha256', 'ip:'.$ip) => 30);
        $permitido = TRUE;
        $this->db->trans_begin();
        foreach ($buckets as $clave => $limite) {
            // Incremento atómico antes de verificar contraseña: también limita peticiones paralelas.
            $ok = $this->db->query(
                'INSERT INTO login_intentos (clave, intentos, inicio) VALUES (?, 1, ?) '
                .'ON DUPLICATE KEY UPDATE intentos = IF(inicio <= ?, 1, intentos + 1), '
                .'inicio = IF(inicio <= ?, ?, inicio)',
                array($clave, $ahora, $ahora - 900, $ahora - 900, $ahora)
            );
            if (!$ok) { $this->db->trans_rollback(); show_error('No se pudo verificar el acceso.', 503); }
            $q = $this->db->get_where('login_intentos', array('clave' => $clave));
            if (!$q) { $this->db->trans_rollback(); show_error('No se pudo verificar el acceso.', 503); }
            if ((int) $q->row()->intentos > $limite) { $permitido = FALSE; }
        }
        if (!$this->db->trans_status()) { $this->db->trans_rollback(); show_error('No se pudo verificar el acceso.', 503); }
        $this->db->trans_commit();
        // Limpieza de claves antiguas para no conservarlas indefinidamente.
        $this->db->where('inicio <', $ahora - 86400)->delete('login_intentos');
        return $permitido;
    }
    public function limpiar_correo($correo)
    {
        $this->db->delete('login_intentos', array('clave' => hash('sha256', 'correo:'.$correo)));
    }
}
