<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function index()
    {
        $metodo = $this->input->method(TRUE);
        if (!in_array($metodo, array('GET', 'POST'), TRUE)) { $this->solo_metodo('GET'); }
        if ($this->autenticacion->usuario_actual() !== NULL) { redirect('inicio'); }
        $datos = array('error' => '', 'correo' => '');
        if ($metodo === 'POST') {
            $correo = $this->input->post('correo');
            $password = $this->input->post('password');
            $correo = is_string($correo) ? strtolower(trim($correo)) : '';
            $datos['correo'] = substr($correo, 0, 190);
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 190
                || !is_string($password) || strlen($password) < 1 || strlen($password) > 72) {
                $datos['error'] = 'Introduce un correo válido y una contraseña de hasta 72 bytes.';
                $this->output->set_status_header(422);
            } else {
                $resultado = $this->autenticacion->ingresar($correo, $password);
                if ($resultado === 'ok') { redirect('inicio'); }
                if ($resultado === 'limite') {
                    $datos['error'] = 'Demasiados intentos. Espera 15 minutos e inténtalo de nuevo.';
                    $this->output->set_status_header(429)->set_header('Retry-After: 900');
                } else {
                    $datos['error'] = 'Correo o contraseña incorrectos.';
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
