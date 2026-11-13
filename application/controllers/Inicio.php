<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Heredar de Protected_Controller exige iniciar sesión antes de entrar aquí.
class Inicio extends Protected_Controller
{
    /** Token vigente para formularios abiertos en varias pestañas. Mantiene la rotación CSRF. */
    public function formulario()
    {
        $this->solo_metodo('GET');
        $this->output->set_content_type('application/json','utf-8')->set_output(json_encode(array(
            'nombre'=>$this->security->get_csrf_token_name(), 'token'=>$this->security->get_csrf_hash()
        )));
    }

    public function index()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('inicio.ver');
        $this->load->model('Inicio_model', 'inicio_model');
        $this->load->helper(array('negocio', 'estadisticas'));
        $this->load->model('Estadistica_model', 'estadistica_model');
        $dias = Estadistica_model::dias_validos($this->input->get('dias'));
        $estadisticas = array();
        $aviso_estadisticas = '';
        try {
            $estadisticas = $this->estadistica_model->panel($dias);
        } catch (RuntimeException $e) {
            log_message('error', 'No se pudieron cargar las estadísticas de inicio.');
            $aviso_estadisticas = 'Las estadísticas no están disponibles por el momento. Puedes seguir usando los accesos rápidos.';
        }
        $resumen = array();
        $aviso = '';
        try {
            $resumen = $this->inicio_model->resumen();
        } catch (RuntimeException $e) {
            log_message('error', 'No se pudo cargar el resumen de inicio.');
            $aviso = 'El resumen no está disponible por el momento. Puedes seguir usando los accesos rápidos.';
        }
        $this->load->view('inicio/index', array(
            'usuario' => $this->usuario, 'resumen' => $resumen, 'aviso_resumen' => $aviso,
            'estadisticas' => $estadisticas, 'aviso_estadisticas' => $aviso_estadisticas
        ));
    }
}
