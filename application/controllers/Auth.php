<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function index()
    {
        $metodo = $this->input->method(TRUE);
        if (!in_array($metodo, array('GET', 'POST'), TRUE)) { $this->solo_metodo('GET'); }
        if ($this->autenticacion->usuario_actual() !== NULL) { redirect('inicio'); }
        $datos = array('error' => '', 'identificador' => '');
        if ($metodo === 'POST') {
            $identificador = $this->input->post('identificador');
            if ($identificador === NULL) { $identificador = $this->input->post('correo'); }
            $password = $this->input->post('password');
            $identificador = is_string($identificador) ? strtolower(trim($identificador)) : '';
            $datos['identificador'] = substr($identificador, 0, 190);
            $esCorreo = filter_var($identificador, FILTER_VALIDATE_EMAIL) && strlen($identificador) <= 190;
            $esUsuario = preg_match('/^[a-z][a-z0-9._-]{2,39}$/D', $identificador) === 1;
            if ((!$esCorreo && !$esUsuario)
                || !is_string($password) || strlen($password) < 1 || strlen($password) > 72) {
                $datos['error'] = 'Introduce un usuario o correo válido y una contraseña de hasta 72 bytes.';
                $this->output->set_status_header(422);
            } else {
                $resultado = $this->autenticacion->ingresar($identificador, $password);
                if ($resultado === 'ok') { redirect('inicio'); }
                if ($resultado === 'limite') {
                    $datos['error'] = 'Demasiados intentos. Espera 15 minutos e inténtalo de nuevo.';
                    $this->output->set_status_header(429)->set_header('Retry-After: 900');
                } else {
                    $datos['error'] = 'Usuario, correo o contraseña incorrectos.';
                    $this->output->set_status_header(401);
                }
            }
        }
        $this->load->view('auth/login', $datos);
    }
    public function salir()
    {
        $this->solo_metodo('POST');
        $this->autenticacion->salir();
        redirect('login');
    }
}
