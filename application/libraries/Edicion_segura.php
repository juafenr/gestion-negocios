<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Compara únicamente los campos editables; los movimientos de stock no invalidan una ficha. */
class Edicion_segura
{
    private $ci;
    private $campos = array(
        'insumos'=>array('nombre','stock_minimo','unidad_medida','fecha_vencimiento','activo'),
        'productos'=>array('nombre','sku','descripcion','precio','disponible','insumo_id'),
        'usuarios'=>array('nombre','nombre_usuario','correo','rol_id','activo'),
        'proveedores'=>array('nombre','contacto','telefono','correo','direccion','activo')
    );
    public $error = '';
    public $status = 422;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
    }

    private function usuario() { return $this->ci->autenticacion->usuario_actual(); }

    private function fila($tabla, $id, $bloquear = FALSE)
    {
        if (!isset($this->campos[$tabla])) { throw new InvalidArgumentException('Registro no permitido.'); }
        $sql = 'SELECT '.implode(', ', $this->campos[$tabla]).' FROM '.$tabla.' WHERE id=? AND empresa_id=?';
        $r = $this->ci->db->query($sql.($bloquear ? ' FOR UPDATE' : ''), array((int)$id,$this->usuario()->empresaId()));
        if (!$r) { throw new RuntimeException('No se pudo consultar el registro.'); }
        return $r->row_array();
    }

    private function firma($tabla, $id, array $fila)
    {
        $clave = $this->ci->session->userdata('clave_edicion');
        if (!is_string($clave)) {
            $clave = bin2hex(random_bytes(32));
            $this->ci->session->set_userdata('clave_edicion', $clave);
        }
        return hash_hmac('sha256', json_encode(array($this->usuario()->empresaId(),$tabla,(int)$id,$fila)), $clave);
    }

    public function campo($tabla, $id)
    {
        $token = $this->ci->input->post('version_edicion');
        // Un formulario rechazado conserva su versión original: nunca autoriza sobrescribir silenciosamente.
        if ($this->ci->input->method(TRUE) === 'GET') {
            $fila = $this->fila($tabla,$id);
            $token = $fila ? $this->firma($tabla,$id,$fila) : '';
        }
        return '<input type="hidden" name="version_edicion" value="'.html_escape(is_string($token) ? $token : '').'">';
    }

    /** El bloqueo, la escritura y la auditoría se confirman en una única transacción. */
    public function guardar($tabla, $id, callable $operacion, $comprobar = TRUE)
    {
        $db = $this->ci->db;
        $debug = $db->db_debug;
        $db->db_debug = FALSE;
        $this->error = '';
        $this->status = 422;
        $iniciada = FALSE;
        try {
            if (!$db->trans_begin()) { throw new RuntimeException('No se pudo iniciar el guardado.'); }
            $iniciada = TRUE;
            $antes = $this->fila($tabla,$id,TRUE);
            $token = $this->ci->input->post('version_edicion');
            if (!$antes || ($comprobar && (!is_string($token) || !hash_equals($this->firma($tabla,$id,$antes),$token)))) {
                $this->status = 409;
                throw new InvalidArgumentException('Otra persona modificó este registro o el formulario ya no está vigente. Tus cambios no se guardaron. Conserva lo escrito y vuelve a abrir la edición para comparar los datos actuales.');
            }
            $resultado = $operacion();
            if ($resultado === FALSE || (is_array($resultado) && !empty($resultado['error']))) {
                $db->trans_rollback();
                $iniciada = FALSE;
                return $resultado;
            }
            $despues = $this->fila($tabla,$id);
            $auditables = $tabla === 'insumos' ? array('fecha_vencimiento') : ($tabla === 'productos' ? array('precio','disponible') : array());
            foreach ($auditables as $campo) {
                if ($antes[$campo] === $despues[$campo]) { continue; }
                if (!$db->insert('registro_cambios',array('empresa_id'=>$this->usuario()->empresaId(),
                    'usuario_id'=>$this->usuario()->id(),'entidad'=>$tabla,'registro_id'=>(int)$id,'campo'=>$campo,
                    'valor_anterior'=>$antes[$campo],'valor_nuevo'=>$despues[$campo]))) {
                    throw new RuntimeException('No se pudo registrar el historial.');
                }
            }
            if (!$db->trans_status() || !$db->trans_commit()) { throw new RuntimeException('No se pudo confirmar el guardado.'); }
            $iniciada = FALSE;
            return $resultado;
        } catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();
        } catch (Throwable $e) {
            $this->status = 503;
            $this->error = 'No se pudieron guardar los cambios y su historial. Intenta nuevamente.';
            log_message('error','Falló una edición protegida: '.$tabla.'.');
        } finally {
            if ($iniciada) { $db->trans_rollback(); }
            $db->db_debug = $debug;
        }
        return FALSE;
    }

    public function historial($tabla, $id, $pagina = 1)
    {
        if (!isset($this->campos[$tabla])) { return array(); }
        $r = $this->ci->db->select('h.*, u.nombre AS responsable')->from('registro_cambios h')
            ->join('usuarios u','u.id=h.usuario_id AND u.empresa_id=h.empresa_id','left')
            ->where('h.empresa_id',$this->usuario()->empresaId())->where('h.entidad',$tabla)->where('h.registro_id',(int)$id)
            ->order_by('h.id','DESC')->limit(21, (max(1,min(100000,(int)$pagina))-1)*20)->get();
        if (!$r) { show_error('No se pudo consultar el historial de cambios.',503); }
        return $r->result_array();
    }
}
