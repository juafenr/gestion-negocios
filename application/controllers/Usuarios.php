<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Usuarios extends Protected_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Usuario_model', 'usuarios_model');
    }

    public function index()
    {
        $this->solo_metodo('GET');
        $this->requerir_permiso('usuarios.ver');
        $this->load->view('usuarios/index', array(
            'usuario' => $this->usuario,
            'usuarios' => $this->usuarios_model->listar_empresa()
        ));
    }

    public function crear()
    {
        $this->requerir_permiso('usuarios.gestionar');
        $metodo = $this->input->method(TRUE);
        if (!in_array($metodo, array('GET', 'POST'), TRUE)) { $this->solo_metodo('GET'); }
        $roles = $this->usuarios_model->roles_disponibles();
        $datos = array('nombre' => '', 'correo' => '', 'rol_id' => '', 'error' => '');
        if ($metodo === 'POST') {
            $datos = array(
                'nombre' => $this->texto_post('nombre'),
                'correo' => strtolower($this->texto_post('correo')),
                'rol_id' => $this->texto_post('rol_id'),
                'error' => ''
            );
            $password = $this->input->post('password');
            $error = $this->validar($datos, $roles, $password, TRUE);
            if ($error === '' && !$this->usuarios_model->correo_disponible($datos['correo'])) {
                $error = 'Ya existe una cuenta con ese correo.';
            }
            if ($error === '') {
                $ok = $this->usuarios_model->crear_en_empresa(array(
                    'nombre' => $datos['nombre'], 'correo' => $datos['correo'], 'rol_id' => $datos['rol_id'],
                    'password_hash' => password_hash($password, PASSWORD_BCRYPT, array('cost' => 10))
                ));
                if (!$ok) { show_error('No se pudo crear el usuario.', 503); }
                redirect('usuarios');
            }
            $datos['error'] = $error;
            $this->output->set_status_header(422);
        }
        $this->load->view('usuarios/formulario', array(
            'usuario' => $this->usuario, 'roles' => $roles, 'datos' => $datos,
            'titulo' => 'Crear usuario', 'accion' => 'usuarios/crear', 'creando' => TRUE,
            'rol_editable' => TRUE
        ));
    }

    public function editar($id = NULL)
    {
        $this->requerir_permiso('usuarios.gestionar');
        $id = $this->id_valido($id);
        $cuenta = $this->usuarios_model->buscar_para_gestion($id);
        if ($cuenta === NULL) { show_404(); }
        $metodo = $this->input->method(TRUE);
        if (!in_array($metodo, array('GET', 'POST'), TRUE)) { $this->solo_metodo('GET'); }
        $roles = $this->usuarios_model->roles_disponibles();
        $datos = array('nombre' => $cuenta['nombre'], 'correo' => $cuenta['correo'],
            'rol_id' => (string) $cuenta['rol_id'], 'error' => '');
        if ($metodo === 'POST') {
            $datos['nombre'] = $this->texto_post('nombre');
            $datos['rol_id'] = $this->texto_post('rol_id');
            $error = $this->validar($datos, $roles, NULL, FALSE);
            if ($id === $this->usuario->id() && (int) $datos['rol_id'] !== (int) $cuenta['rol_id']) {
                $error = 'No puedes cambiar tu propio rol mientras utilizas la cuenta.';
            }
            if ($id === $this->usuario->id()) {
                $datos['rol_id'] = (string) $cuenta['rol_id'];
            }
            if ($error === '') {
                if (!$this->usuarios_model->actualizar_en_empresa($id, $datos['nombre'], $datos['rol_id'])) {
                    show_error('No se pudo actualizar el usuario.', 503);
                }
                redirect('usuarios');
            }
            $datos['error'] = $error;
            $this->output->set_status_header(422);
        }
        $this->load->view('usuarios/formulario', array(
            'usuario' => $this->usuario, 'roles' => $roles, 'datos' => $datos,
            'titulo' => 'Editar usuario', 'accion' => 'usuarios/'.$id.'/editar', 'creando' => FALSE,
            'rol_editable' => $id !== $this->usuario->id()
        ));
    }

    public function estado($id = NULL)
    {
        $this->solo_metodo('POST');
        $this->requerir_permiso('usuarios.gestionar');
        $id = $this->id_valido($id);
        $cuenta = $this->usuarios_model->buscar_para_gestion($id);
        if ($cuenta === NULL) { show_404(); }
        if ($id === $this->usuario->id()) {
            show_error('No puedes desactivar tu propia cuenta.', 409, 'Operación no permitida');
        }
        if (!$this->usuarios_model->cambiar_estado_en_empresa($id, !(bool) $cuenta['activo'])) {
            show_error('No se pudo cambiar el estado del usuario.', 503);
        }
        redirect('usuarios');
    }

    private function texto_post($campo)
    {
        $valor = $this->input->post($campo);
        return is_string($valor) ? trim($valor) : '';
    }

    private function id_valido($id)
    {
        if (!is_string($id) || !ctype_digit($id) || strlen($id) > 10) { show_404(); }
        return (int) $id;
    }

    private function validar($datos, $roles, $password, $validarCorreo)
    {
        if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) > 100) {
            return 'El nombre es obligatorio y admite hasta 100 caracteres.';
        }
        if ($validarCorreo && (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL) || strlen($datos['correo']) > 190)) {
            return 'Introduce un correo válido de hasta 190 caracteres.';
        }
        $ids = array_map('intval', array_column($roles, 'id'));
        if (!ctype_digit((string) $datos['rol_id']) || !in_array((int) $datos['rol_id'], $ids, TRUE)) {
            return 'Selecciona un rol válido.';
        }
        if ($validarCorreo && (!is_string($password) || strlen($password) < 8 || strlen($password) > 72)) {
            return 'La contraseña debe tener entre 8 y 72 bytes.';
        }
        return '';
    }
}
