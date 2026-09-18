<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Heredar de Protected_Controller exige iniciar sesión antes de entrar aquí.
class Inicio extends Protected_Controller
{
    public function index()
    {
        $this->solo_metodo('GET');
        $this->load->view('inicio/index', array('usuario' => $this->usuario));
    }
}
