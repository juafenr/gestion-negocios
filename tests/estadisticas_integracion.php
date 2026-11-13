<?php
/**
 * Ejecutar: php tests/estadisticas_integracion.php
 * Prueba el modelo real en una base NUEVA y desechable; nunca escribe en gestion_negocios.
 * Requiere local.php, mysqli y permiso CREATE/DROP DATABASE (XAMPP local).
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('BASEPATH', __DIR__.'/../system/');
define('APPPATH', __DIR__.'/../application/');
require APPPATH.'libraries/UsuarioAutenticado.php';
require APPPATH.'libraries/Calendario_negocio.php';
class ResultadoEstTest {
    private $resultado;
    public function __construct($r) { $this->resultado=$r; }
    public function result_array() { return $this->resultado->fetch_all(MYSQLI_ASSOC); }
}
class DbEstTest {
    public $consultas=0;
    private $db;
    public function __construct($db) { $this->db=$db; }
    public function query($sql,$valores=array()) {
        $this->consultas++;
        $s=$this->db->prepare($sql);
        if ($valores) { $s->bind_param(str_repeat('s',count($valores)),...$valores); }
        $s->execute();
        return new ResultadoEstTest($s->get_result());
    }
}
class ConfigEstTest { public function item($nombre) { return $nombre==='zona_horaria_negocio' ? 'America/Guatemala' : NULL; } }
class CargaEstTest { public function database() {} public function library($nombre) {} }
class AuthEstTest { public $usuario; public function usuario_actual() { return $this->usuario; } }
class CI_Model { public function __construct() {} public function __get($nombre) { return get_instance()->$nombre; } }
function &get_instance() { return $GLOBALS['ci_est_test']; }
function show_error($mensaje,$codigo=500) { throw new RuntimeException($mensaje,$codigo); }
require APPPATH.'core/MY_Model.php';
require APPPATH.'models/Estadistica_model.php';
function comprobar_est($condicion,$mensaje) {
    if (!$condicion) { throw new RuntimeException($mensaje); }
    echo 'OK: '.$mensaje.PHP_EOL;
}
function usuario_est($empresa,$permisos) {
    return new UsuarioAutenticado(array('id'=>$empresa,'empresa_id'=>$empresa,'nombre'=>'Prueba',
        'empresa_nombre'=>'Empresa '.$empresa,'rol_codigo'=>'prueba','rol_nombre'=>'Prueba','permisos'=>$permisos));
}
$db=NULL; $temporal=NULL; $creada=FALSE; $salida=0;
try {
    if (!extension_loaded('mysqli')) { throw new RuntimeException('Se necesita mysqli de PHP/XAMPP.'); }
    if (!is_file(APPPATH.'config/local.php')) { throw new RuntimeException('Configura primero application/config/local.php.'); }
    $c=require APPPATH.'config/local.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db=new mysqli($c['db_host'],$c['db_user'],$c['db_pass'],'',(int)$c['db_port']);
    $db->set_charset('utf8mb4');
    $temporal='gn_est_test_'.bin2hex(random_bytes(8));
    $db->query('CREATE DATABASE `'.$temporal.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $creada=TRUE; $db->select_db($temporal);
    $db->multi_query(file_get_contents(__DIR__.'/../database/schema.sql'));
    do { if ($r=$db->store_result()) { $r->free(); } } while ($db->more_results() && $db->next_result());
    $db->query("INSERT INTO empresas (id,nombre) VALUES (1,'A'),(2,'B'),(3,'Sin datos')");
    $db->query("INSERT INTO usuarios (id,empresa_id,rol_id,nombre,nombre_usuario,correo,password_hash)
        SELECT 1,1,id,'A','prueba_a','a@example.test','sin_login' FROM roles WHERE codigo='administrador'");
    $db->query("INSERT INTO usuarios (id,empresa_id,rol_id,nombre,nombre_usuario,correo,password_hash)
        SELECT 2,2,id,'B','prueba_b','b@example.test','sin_login' FROM roles WHERE codigo='administrador'");
    $db->query("INSERT INTO usuarios (id,empresa_id,rol_id,nombre,nombre_usuario,correo,password_hash)
        SELECT 4,1,id,'Despachador A','despacho_a','despacho@example.test','sin_login' FROM roles WHERE codigo='cocinero'");
    $db->query("INSERT INTO productos (id,empresa_id,sku,nombre,precio) VALUES
        (1,1,'a','Producto A',20),(2,1,'b','Producto B',60),(3,2,'c','Exclusivo B',999),
        (4,1,'d','Disponible sin ventas',10),(5,1,'e','No disponible',10)");
    $db->query("UPDATE productos SET disponible=0 WHERE id=5");
    $db->query("INSERT INTO pedidos (id,empresa_id,numero,usuario_id,referencia,estado,estado_pago,total) VALUES
        (1,1,1,1,'Pagado A','pendiente','pagado',100),
        (2,1,2,1,'Segundo A','entregado','pagado',100),
        (3,2,1,2,'Pagado B','listo','pagado',999),
        (4,1,3,1,'Solo abono','en_preparacion','parcial',100),
        (5,1,4,1,'Cancelado','cancelado','pendiente',900),
        (6,1,5,1,'Fuera del período','entregado','pagado',100),
        (7,1,6,1,'Límite futuro','entregado','pagado',100),
        (8,1,7,1,'Entrega sin cobro','entregado','pendiente',0)");
    $db->query("INSERT INTO pedido_detalles (empresa_id,pedido_id,producto_id,producto_nombre,cantidad,precio_unitario) VALUES
        (1,1,1,'Producto A',2,20),(1,1,2,'Producto B',1,60),(1,2,1,'Producto A',5,20),
        (2,3,3,'Exclusivo B',1,999),(1,4,1,'Producto A',5,20),(1,5,1,'Producto A',45,20),
        (1,6,1,'Producto A',5,20),(1,7,1,'Producto A',5,20)");
    $instante=new DateTimeImmutable('2026-11-13T01:30:00Z');
    $hoy=Calendario_negocio::hoy($instante);
    $desde=$hoy->modify('-6 days')->format('Y-m-d').' 00:00:00';
    $hoy_texto=$hoy->format('Y-m-d').' 12:00:00';
    $fuera=$hoy->modify('-7 days')->format('Y-m-d').' 23:59:59';
    $manana=$hoy->modify('+1 day')->format('Y-m-d').' 00:00:00';
    $db->query("INSERT INTO ventas (empresa_id,pedido_id,pedido_numero,referencia,usuario_id,subtotal,propina,total,efectivo,credito,debito,fecha) VALUES
        (1,1,1,'A',1,100,10,110,50,60,0,'$desde'),
        (1,2,2,'A',1,100,10,110,0,0,110,'$hoy_texto'),
        (2,3,1,'B',2,999,99.90,1098.90,1098.90,0,0,'$hoy_texto'),
        (1,6,5,'Fuera',1,100,10,110,110,0,0,'$fuera'),
        (1,7,6,'Futuro',1,100,10,110,110,0,0,'$manana')");
    $db->query("INSERT INTO pedido_eventos (empresa_id,pedido_id,usuario_id,rol_codigo,estado_anterior,estado_nuevo,creado_en) VALUES
        (1,2,1,'administrador','listo','entregado','$hoy_texto'),
        (1,2,4,'cocinero','listo','entregado','$hoy_texto'),
        (2,3,2,'administrador','listo','entregado','$hoy_texto'),
        (1,6,4,'cocinero','listo','entregado','$fuera'),
        (1,6,4,'cocinero','listo','entregado','$hoy_texto'),
        (1,7,4,'cocinero','listo','entregado','$hoy_texto'),
        (1,1,4,'cocinero','en_preparacion','listo','$hoy_texto'),
        (1,8,1,'administrador','listo','entregado','$desde')");
    $db->query("INSERT INTO pago_cuentas (id,empresa_id,pedido_id,nombre,total) VALUES (1,1,1,'A',110),(2,1,4,'Abono',110),(3,2,3,'B',1098.90)");
    $db->query("INSERT INTO pagos (empresa_id,pedido_id,cuenta_id,usuario_id,clave,efectivo,credito,debito,recibido,cambio,propina,total,creado_en) VALUES
        (1,1,1,1,REPEAT('a',64),20,30,0,100,80,5,50,'$hoy_texto'),
        (1,1,1,1,REPEAT('b',64),30,30,0,30,0,5,60,'$hoy_texto'),
        (1,4,2,1,REPEAT('c',64),0,0,15,0,0,1.36,15,'$hoy_texto'),
        (2,3,3,2,REPEAT('d',64),999,0,0,999,0,0,999,'$hoy_texto'),
        (1,4,2,1,REPEAT('e',64),1,0,0,1,0,0,1,'$fuera')");
    $db->query("INSERT INTO insumos (empresa_id,nombre,cantidad_actual,stock_minimo,unidad_medida,activo) VALUES
        (1,'Normal',10,2,'unidad',1),(1,'Mínimo',2,2,'unidad',1),(1,'Bajo',1,2,'unidad',1),
        (1,'Agotado',0,0,'unidad',1),(1,'Inactivo',0,5,'unidad',0),(2,'Ajeno',0,5,'unidad',1)");
    $ci=new stdClass(); $ci->load=new CargaEstTest(); $ci->db=new DbEstTest($db); $ci->autenticacion=new AuthEstTest();
    $GLOBALS['ci_est_test']=$ci;
    $todos=array('ventas.historial','ventas.ver','pedidos.ver','inventario.ver','reportes.ver');
    $ci->autenticacion->usuario=usuario_est(1,$todos);
    $ci->config=new ConfigEstTest();
    $modelo=new Estadistica_model(); $a=$modelo->panel(7,$instante);
    comprobar_est($a['hasta']==='12/11/2026' && $a['serie'][6]['dia']==='2026-11-12',
        'El período termina en Guatemala y no en el día siguiente de Berlín');
    foreach(array(30,90) as $dias_prueba){
        $rango=$modelo->panel($dias_prueba,$instante);
        comprobar_est(count($rango['serie'])===$dias_prueba && $rango['serie'][0]['dia']===$hoy->modify('-'.($dias_prueba-1).' days')->format('Y-m-d')
            && $rango['serie'][$dias_prueba-1]['dia']==='2026-11-12' && $rango['ventas']['consumo']==='300.00',
            'El rango de '.$dias_prueba.' días incluye ventas anteriores sin incluir mañana');
    }
    comprobar_est((int)$a['ventas']['cantidad']===2 && $a['ventas']['consumo']==='200.00','Ventas sin mezclar empresa B, abonos, cancelaciones ni fechas externas');
    comprobar_est($a['ventas']['propina']==='20.00' && $a['ventas']['promedio']==='100.00','Propinas separadas y promedio exacto');
    comprobar_est(count($a['serie'])===7 && $a['serie'][0]['consumo']==='100.00' && $a['serie'][6]['consumo']==='100.00','Incluye límites correctos y orden cronológico');
    comprobar_est($a['serie'][1]['consumo']==='0.00','Días sin ventas conservan cero');
    comprobar_est(count($a['productos'])===2 && (int)$a['productos'][0]['cantidad']===7 && $a['productos'][0]['nombre']==='Producto A','Ranking sin duplicar unidades por pagos múltiples');
    comprobar_est(count($a['menos_vendidos'])===3 && $a['menos_vendidos'][0]['nombre']==='Disponible sin ventas' && (int)$a['menos_vendidos'][0]['cantidad']===0,'Menos vendidos incluye ceros y excluye catálogo ajeno o no disponible');
    comprobar_est(array_column($a['menos_vendidos'],'nombre')===array('Disponible sin ventas','Producto B','Producto A'),'Menos vendidos ordena las unidades de menor a mayor');
    comprobar_est(count($a['despachos'])===2 && $a['despachos'][0]['nombre']==='A' && (int)$a['despachos'][0]['cantidad']===2 && $a['despachos'][1]['nombre']==='Despachador A' && (int)$a['despachos'][1]['cantidad']===1,'Despachos por actor de entrega: sin duplicados, otras empresas, otros estados ni entregas anteriores al período');
    comprobar_est($a['cobros']['total']==='125.00' && $a['cobros']['efectivo']==='50.00' && $a['cobros']['debito']==='15.00','Cobros incluyen abonos y excluyen cambio, otras fechas y otra empresa');
    comprobar_est((int)$a['inventario']['total']===4 && (int)$a['inventario']['normal']===1 && (int)$a['inventario']['bajo']===2 && (int)$a['inventario']['agotado']===1,'Stock: mínimo incluido, agotados separados e inactivos excluidos');
    comprobar_est(array_column($a['pedidos'],'cantidad')===array(1,1,0),'Pedidos actuales excluyen entregados, cancelados y otra empresa');
    $ci->autenticacion->usuario=usuario_est(2,$todos); $b=$modelo->panel(7,$instante);
    comprobar_est($b['ventas']['consumo']==='999.00' && $b['productos'][0]['nombre']==='Exclusivo B' && (int)$b['inventario']['total']===1 && $b['despachos'][0]['nombre']==='B' && count($b['menos_vendidos'])===1,'Empresa B solo ve sus propios datos');
    $ci->autenticacion->usuario=usuario_est(3,$todos); $vacio=$modelo->panel(90,$instante);
    comprobar_est((int)$vacio['ventas']['cantidad']===0 && count($vacio['serie'])===90 && !$vacio['productos'] && !$vacio['menos_vendidos'] && !$vacio['despachos'] && (int)$vacio['inventario']['total']===0,'Empresa vacía y período de 90 días sin divisiones por cero');
    $ci->autenticacion->usuario=usuario_est(1,array('pedidos.ver','pedidos.actualizar_cocina')); $antes=$ci->db->consultas; $cocina=$modelo->panel(30,$instante);
    comprobar_est(array_keys($cocina)===array('dias','desde','hasta','pedidos') && $ci->db->consultas-$antes===1,'Cocina no consulta ni recibe estadísticas financieras o inventario');
    $ci->autenticacion->usuario=usuario_est(1,array('ventas.ver','pedidos.ver')); $caja=$modelo->panel(7,$instante);
    comprobar_est(isset($caja['cobros'],$caja['pedidos']) && !isset($caja['ventas']) && !isset($caja['productos']) && !isset($caja['inventario']) && !isset($caja['despachos']) && !isset($caja['menos_vendidos']),'Cajero ve cobros y pedidos, no historial de ventas ni stock');
    $ci->autenticacion->usuario=usuario_est(1,array('reportes.ver','pedidos.ver')); $reporte=$modelo->panel(7,$instante);
    comprobar_est(isset($reporte['despachos'],$reporte['pedidos']) && !isset($reporte['ventas']) && !isset($reporte['cobros']),'Reporte de despachos respeta sus permisos sin abrir montos financieros');
    $ci->autenticacion->usuario=usuario_est(1,array('ventas.ver')); $antes=$ci->db->consultas; $solo=$modelo->panel(7,$instante);
    comprobar_est(count($solo)===3 && $ci->db->consultas===$antes,'Permisos incompletos no ejecutan consultas de cobros');
    foreach (array(NULL,array('7'),'1','7 OR 1=1','90.0',90) as $invalido) {
        comprobar_est(Estadistica_model::dias_validos($invalido)===30,'Entrada no permitida vuelve al período predeterminado');
    }
    comprobar_est(Estadistica_model::dias_validos('7')===7 && Estadistica_model::dias_validos('90')===90,'Acepta períodos permitidos');
    $rechazo=FALSE; try { $modelo->panel(999,$instante); } catch (InvalidArgumentException $e) { $rechazo=TRUE; }
    comprobar_est($rechazo,'El modelo también limita los períodos');
    $ci->autenticacion->usuario=NULL; $rechazo=FALSE;
    try { $modelo->panel(7,$instante); } catch (RuntimeException $e) { $rechazo=TRUE; }
    comprobar_est($rechazo,'Sin identidad autenticada no hay estadísticas');
    echo 'APROBADO: estadísticas, separación por empresa, permisos y límites.'.PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR,'FALLO: '.$e->getMessage().PHP_EOL); $salida=1;
} finally {
    if ($db && $creada && preg_match('/^gn_est_test_[a-f0-9]{16}$/D',$temporal)) {
        try { $db->query('DROP DATABASE `'.$temporal.'`'); }
        catch (Throwable $e) { fwrite(STDERR,'No se pudo eliminar la base temporal '.$temporal.PHP_EOL); $salida=1; }
    }
    if ($db) { $db->close(); }
}
exit($salida);
