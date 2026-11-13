<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function ui_listados()
{
    return array('inventario','productos','usuarios','proveedores','pedidos','compras','pagos','ventas','cocina');
}

function ui_ruta_retorno($modulo)
{
    if (!in_array($modulo,ui_listados(),TRUE)) { return 'inicio'; }
    $ci =& get_instance();
    $u = $ci->autenticacion->usuario_actual();
    $datos = $ci->session->userdata('ui_listados_'.$u->empresaId().'_'.$u->id());
    $query = is_array($datos) ? ($datos[$modulo] ?? '') : '';
    return $modulo.($query !== '' ? '?'.$query : '');
}

function ui_campo_edicion($tabla, $id)
{
    $ci =& get_instance();
    $ci->load->library('Edicion_segura');
    $campo = $ci->edicion_segura->campo($tabla,$id);
    if ($ci->edicion_segura->error !== '' && $ci->edicion_segura->status === 409) {
        $campo .= '<p class="ayuda-campo"><a href="'.html_escape(current_url()).'">Volver a cargar los datos actuales</a> para comparar antes de guardar de nuevo.</p>';
    }
    return $campo;
}

/** Vence pronto significa dentro de los siguientes 30 días. */
function ui_vencimiento($fecha, $hoy = NULL)
{
    if (!$fecha) { return '<span class="estado etiqueta-estado etiqueta-borrador">Sin vencimiento</span>'; }
    $hoy = $hoy ?? date('Y-m-d');
    $limite = (new DateTimeImmutable($hoy))->modify('+30 days')->format('Y-m-d');
    $texto = $fecha < $hoy ? 'Vencido' : ($fecha === $hoy ? 'Vence hoy' : ($fecha <= $limite ? 'Vence pronto' : 'Vigente'));
    $clase = $fecha < $hoy ? 'cancelado' : ($fecha <= $limite ? 'preparacion' : 'recibido');
    return '<span class="estado etiqueta-estado etiqueta-'.$clase.'">'.$texto.'</span><span class="fecha-vencimiento">'.html_escape($fecha).'</span>';
}

/** Las etiquetas traducen la presentación; los valores y transiciones del negocio no cambian. */
function ui_estado($valor, $contexto = 'pedido')
{
    $pedidos = array(
        'borrador'=>array('Borrador','borrador'), 'pendiente'=>array('Confirmado','confirmado'),
        'en_preparacion'=>array('En preparación','preparacion'), 'listo'=>array('Listo','listo'),
        'entregado'=>array('Entregado','recibido'), 'cancelado'=>array('Cancelado','cancelado')
    );
    $compras = array(
        'borrador'=>array('Borrador','borrador'), 'confirmada'=>array('Confirmada','confirmado'),
        'enviada'=>array('Enviada','enviado'), 'recibida'=>array('Recibida','recibido'),
        'cancelada'=>array('Cancelada','cancelado')
    );
    $pagos = array('pendiente'=>array('Sin pagos','preparacion'),
        'parcial'=>array('Pago parcial','confirmado'), 'pagado'=>array('Pagado','recibido'));
    $mapa = $contexto === 'compra' ? $compras : ($contexto === 'pago' ? $pagos : $pedidos);
    $etiqueta = $mapa[$valor] ?? array(ucfirst(str_replace('_',' ',(string)$valor)), 'borrador');
    return '<span class="estado etiqueta-estado etiqueta-'.$etiqueta[1].'">'.html_escape($etiqueta[0]).'</span>';
}

/** Filtra por permisos antes de ordenar los accesos habituales de cada rol. */
function ui_accesos_inicio($usuario)
{
    $opciones = array(
        'pedidos'=>array('Pedidos','Crea y consulta los pedidos de tu empresa.','pedidos.svg',array('pedidos.ver')),
        'pagos'=>array('Pagos','Cobra pedidos y revisa los saldos pendientes.','pagos.svg',array('ventas.ver','pedidos.ver')),
        'cocina'=>array('Preparación','Consulta la cola y actualiza los pedidos en cocina.','correcto.svg',array('pedidos.actualizar_cocina')),
        'inventario'=>array('Inventario','Revisa existencias y registra movimientos de insumos.','inventario.svg',array('inventario.ver')),
        'compras'=>array('Compras','Prepara y recibe las solicitudes a tus proveedores.','compras.svg',array('compras.ver')),
        'ventas'=>array('Ventas','Consulta las ventas completadas y sus totales.','ventas.svg',array('ventas.historial')),
        'productos'=>array('Productos','Consulta el catálogo, precios y disponibilidad.','productos.svg',array('productos.ver')),
        'usuarios'=>array('Usuarios','Administra las cuentas y roles de tu empresa.','usuario.svg',array('usuarios.ver')),
        'proveedores'=>array('Proveedores','Consulta proveedores y sus insumos asociados.','compras.svg',array('compras.ver'))
    );
    foreach ($opciones as $ruta=>$opcion) {
        foreach ($opcion[3] as $permiso) {
            if (!$usuario->puede($permiso)) { unset($opciones[$ruta]); break; }
        }
    }
    $por_rol = array('administrador'=>array('inventario','compras','ventas'),
        'cajero'=>array('pedidos','pagos'), 'cocinero'=>array('cocina'));
    $principales = array();
    foreach ($por_rol[$usuario->rol()] ?? array_keys($opciones) as $ruta) {
        if (isset($opciones[$ruta])) { $principales[$ruta]=$opciones[$ruta]; }
    }
    return array('principales'=>$principales, 'otros'=>array_diff_key($opciones,$principales));
}

function ui_recordar_listado()
{
    $ci =& get_instance();
    $u = $ci->autenticacion->usuario_actual();
    if (!$u) { return; }
        $modulo = strtolower($ci->router->class);
        if ($ci->input->method(TRUE) === 'GET' && $ci->router->method === 'index' && in_array($modulo,ui_listados(),TRUE)) {
            $query = array();
            foreach (array('busqueda','pagina','pagina_dias','estado','unidad','orden','activo','disponibilidad','rol','desde','hasta','proveedor_id','estado_pago','referencia') as $campo) {
                $valor = $ci->input->get($campo);
                if (is_string($valor) && strlen($valor) <= 600 && !preg_match('/[\x00-\x1f]/',$valor)) { $query[$campo]=$valor; }
            }
            $clave = 'ui_listados_'.$u->empresaId().'_'.$u->id();
            $listados = $ci->session->userdata($clave);
            $listados = is_array($listados) ? $listados : array();
            $listados[$modulo] = http_build_query($query,'','&',PHP_QUERY_RFC3986);
            $ci->session->set_userdata($clave,$listados);
        }
}
