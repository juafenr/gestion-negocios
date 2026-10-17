<?php
/**
 * Pruebas del modelo real con MySQL/MariaDB y datos aislados.
 * Crea una base temporal gn_pago_test_<azar> y elimina SOLO esa base al terminar.
 * Usa las credenciales de local.php. No escribe en la base del proyecto.
 * Requiere permiso CREATE/DROP DATABASE (root local de XAMPP lo tiene).
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('BASEPATH',__DIR__.'/../system/');
define('APPPATH',__DIR__.'/../application/');
require APPPATH.'libraries/Dinero.php';
require APPPATH.'libraries/UsuarioAutenticado.php';

// Adaptador mínimo para ejecutar las consultas del modelo fuera de HTTP.
class ResultadoPagoTest {
    private $r;
    public function __construct($r) { $this->r=$r; }
    public function row_array() { return $this->r->fetch_assoc(); }
    public function result_array() { return $this->r->fetch_all(MYSQLI_ASSOC); }
}
class DbPagoTest {
    public $db_debug=FALSE;
    private $db;
    private $ok=TRUE;
    public function __construct($db) { $this->db=$db; }
    public function query($sql,$datos=array()) {
        try {
            $s=$this->db->prepare($sql);
            if ($datos) { $s->bind_param(str_repeat('s',count($datos)),...$datos); }
            $s->execute(); $r=$s->get_result();
            return $r ? new ResultadoPagoTest($r) : TRUE;
        } catch (mysqli_sql_exception $e) { $this->ok=FALSE; return FALSE; }
    }
    public function trans_begin() { $this->ok=TRUE;return $this->db->begin_transaction(); }
    public function trans_commit() { return $this->db->commit(); }
    public function trans_rollback() { return $this->db->rollback(); }
    public function trans_status() { return $this->ok; }
    public function insert_id() { return $this->db->insert_id; }
}
class CargaPagoTest { public function library($x) {} public function database() {} }
class AuthPagoTest { public $usuario; public function usuario_actual() { return $this->usuario; } }
class CI_Model { public function __construct() {} public function __get($campo) { return get_instance()->$campo; } }
function &get_instance() { return $GLOBALS['ci_pago_test']; }
function show_error($m,$codigo=500) { throw new RuntimeException($m); }
function log_message($nivel,$m) { fwrite(STDERR,$nivel.': '.$m."\n"); }
require APPPATH.'core/MY_Model.php';
require APPPATH.'models/Pago_model.php';
function verificar($ok,$m) { if (!$ok) { throw new RuntimeException($m); } echo 'OK: '.$m."\n"; }
function identidad($id,$empresa,$rol='administrador') {
    return new UsuarioAutenticado(array('id'=>$id,'empresa_id'=>$empresa,'nombre'=>'Prueba','empresa_nombre'=>'Prueba','rol_codigo'=>$rol,'rol_nombre'=>$rol,'permisos'=>$rol==='cocinero' ? array('pedidos.ver') : array('ventas.cobrar','ventas.ver','pedidos.ver')));
}
function montos($efectivo,$credito='0.00',$debito='0.00',$recibido=NULL,$clave=NULL) {
    return array('efectivo'=>$efectivo,'credito'=>$credito,'debito'=>$debito,'recibido'=>$recibido ?? $efectivo,'clave'=>$clave ?? bin2hex(random_bytes(32)));
}
$db=NULL;$temporal=NULL;$creada=FALSE;$codigo=0;
try {
    if (!extension_loaded('mysqli')) { throw new RuntimeException('Usa PHP de XAMPP con mysqli.'); }
    $c=require APPPATH.'config/local.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db=new mysqli($c['db_host'],$c['db_user'],$c['db_pass'],'',(int)$c['db_port']);
    $db->set_charset('utf8mb4');
    $temporal='gn_pago_test_'.bin2hex(random_bytes(8));
    $db->query('CREATE DATABASE `'.$temporal.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$creada=TRUE;
    $db->select_db($temporal);
    $db->multi_query(file_get_contents(__DIR__.'/../database/schema.sql'));
    do { if ($r=$db->store_result()) { $r->free(); } } while ($db->more_results() && $db->next_result());
    $db->query("INSERT INTO empresas (id,nombre) VALUES (1,'A prueba'),(2,'B prueba')");
    $db->query("INSERT INTO usuarios (id,empresa_id,rol_id,nombre,correo,password_hash) SELECT 1,1,id,'Admin A','a@prueba.test','sin_login' FROM roles WHERE codigo='administrador'");
    $db->query("INSERT INTO usuarios (id,empresa_id,rol_id,nombre,correo,password_hash) SELECT 2,2,id,'Admin B','b@prueba.test','sin_login' FROM roles WHERE codigo='administrador'");
    $db->query("INSERT INTO usuarios (id,empresa_id,rol_id,nombre,correo,password_hash) SELECT 3,1,id,'Cocinero','c@prueba.test','sin_login' FROM roles WHERE codigo='cocinero'");
    $db->query("INSERT INTO productos (id,empresa_id,sku,nombre,precio) VALUES (1,1,'a','Producto A',60),(2,1,'b','Producto B',40),(3,2,'c','Producto C',100)");
    $db->query("INSERT INTO pedidos (id,empresa_id,numero,usuario_id,referencia,estado,total) VALUES (1,1,1,1,'Prueba','pendiente',100),(2,2,1,2,'Ajeno','pendiente',100),(3,1,2,1,'Borrador','borrador',100),(4,1,3,1,'Cancelado','cancelado',100),(5,1,4,1,'Efectivo','pendiente',100),(6,1,5,1,'Rollback','pendiente',100)");
    $db->query("INSERT INTO pedido_detalles (id,empresa_id,pedido_id,producto_id,producto_nombre,cantidad,precio_unitario) VALUES (1,1,1,1,'A',1,60),(2,1,1,2,'B',1,40),(3,2,2,3,'C',1,100),(4,1,3,1,'A',1,100),(5,1,4,1,'A',1,100),(6,1,5,1,'A',1,100),(7,1,6,1,'A',1,100)");
    $ci_pago_test=(object)array('load'=>new CargaPagoTest(),'db'=>new DbPagoTest($db),'autenticacion'=>new AuthPagoTest());
    $ci_pago_test->autenticacion->usuario=identidad(1,1);
    $m=new Pago_model();
    verificar($m->preparar(2)!==NULL,'Impide preparar un pedido de otra empresa');
    verificar($m->preparar(3)!==NULL,'Impide cobrar un borrador');
    verificar($m->preparar(4)!==NULL,'Impide cobrar un cancelado');
    verificar($m->preparar(1)===NULL,'Prepara el pedido confirmado');
    verificar($m->preparar(1)===NULL && count($m->resumen(1)['cuentas'])===1,'Preparar dos veces no duplica la cuenta');
    verificar($m->separar(1,'Juan',array(1=>'1'))===NULL,'Separa los productos de Juan');
    verificar($m->separar(1,'Ajeno',array(3=>'1'))!==NULL,'Rechaza un producto de otro pedido/empresa');
    verificar($m->separar(1,'Duplicado',array(1=>'1'))!==NULL,'Impide asignar dos veces el mismo consumo');
    $cuentas=$m->resumen(1)['cuentas'];$juan=0;$general=0;
    foreach ($cuentas as $cuenta) {
        if ($cuenta['nombre']==='Juan') { $juan=(int)$cuenta['id'];verificar($cuenta['propina']==='6.00','Propina de Juan: Q 6'); }
        else { $general=(int)$cuenta['id'];verificar($cuenta['propina']==='4.00','Propina restante: Q 4'); }
    }
    verificar($m->registrar(1,$juan,montos('67.00'))!==NULL,'Rechaza cobrar más de Q 66');
    verificar($m->registrar(1,$juan,montos('-1'))!==NULL,'Rechaza negativos');
    verificar($m->registrar(1,$juan,montos('1.001'))!==NULL,'Rechaza más de dos decimales');
    verificar($m->registrar(1,$juan,montos('10','0','0','5'))!==NULL,'Rechaza efectivo insuficiente');
    $pago=montos('20.00','10.00','0.00','50.00');
    verificar($m->registrar(1,$juan,$pago)===NULL,'Registra abono mixto de Q 30 con cambio Q 30');
    verificar($m->registrar(1,$juan,$pago)===NULL && count($m->resumen(1)['pagos'])===1,'Reintentar no duplica el cobro');
    $pago['credito']='11.00';
    verificar($m->registrar(1,$juan,$pago)!==NULL,'Rechaza reutilizar la clave con otro monto');
    verificar($db->query('SELECT estado_pago FROM pedidos WHERE id=1')->fetch_assoc()['estado_pago']==='parcial','El pedido queda parcialmente pagado');
    verificar($m->reiniciar(1)!==NULL && $m->separar(1,'Cambio',array(2=>'1'))!==NULL,'Después del primer pago no se redistribuyen cuentas');
    verificar($m->registrar(1,$juan,montos('0','0','36.00'))===NULL,'Completa Juan con débito, incluyendo propina');
    verificar($m->registrar(1,$general,montos('44.00','0','0','50.00'))===NULL,'Completa la otra cuenta con cambio Q 6');
    $res=$m->resumen(1);
    verificar($res['abonado']===11000,'Consumo más propina suman Q 110');
    verificar($db->query('SELECT estado_pago FROM pedidos WHERE id=1')->fetch_assoc()['estado_pago']==='pagado','Se marca pagado al saldar todas las cuentas');
    verificar($db->query('SELECT estado FROM pedidos WHERE id=1')->fetch_assoc()['estado']==='pendiente','Pagar no cambia el estado de preparación');
    verificar($db->query('SELECT SUM(propina) n FROM pagos WHERE pedido_id=1')->fetch_assoc()['n']==='10.00','Historial distingue Q 10 de propina');
    verificar($m->registrar(1,$juan,montos('1'))!==NULL,'No cobra otra vez una cuenta saldada');
    verificar($m->preparar(5)===NULL,'Prepara cuenta de efectivo');
    $ef=(int)$m->resumen(5)['cuentas'][0]['id'];
    verificar($m->registrar(5,$ef,montos('110','0','0','200'))===NULL,'Cobro completo en efectivo');
    verificar($m->resumen(5)['pagos'][0]['cambio']==='90.00','Cambio correcto Q 90');
    verificar($m->preparar(6)===NULL,'Prepara cuenta de rollback');
    verificar($m->separar(6,'Error',array(7=>'2'))!==NULL && count($m->resumen(6)['cuentas'])===1,'Un reparto inválido no deja cuentas incompletas');
    verificar($m->reiniciar(6)===NULL && !$m->resumen(6)['cuentas'],'Se puede reorganizar antes de cobrar');
    $ci_pago_test->autenticacion->usuario=identidad(2,2);
    verificar(!$m->resumen(1)['pagos'] && !$m->resumen(1)['cuentas'],'Empresa B no consulta el historial de A');
    verificar($m->preparar(2)===NULL,'Empresa B prepara su propia cuenta');
    verificar($m->registrar(2,$juan,montos('1'))!==NULL,'Cuenta de A rechazada en pedido de B');
    $ci_pago_test->autenticacion->usuario=identidad(3,1,'cocinero');
    verificar($m->preparar(6)!==NULL,'Cocinero no prepara cobros');
    verificar($m->registrar(1,$juan,montos('1'))!==NULL,'Cocinero no registra pagos');
    // La BD también impide referencias de pagos entre empresas.
    $rechazo=FALSE;
    try { $db->query("INSERT INTO pagos (empresa_id,pedido_id,cuenta_id,usuario_id,clave,total) VALUES (2,1,$juan,2,'".bin2hex(random_bytes(32))."',1)"); }
    catch (mysqli_sql_exception $e) { $rechazo=TRUE; }
    verificar($rechazo,'Clave foránea compuesta rechaza cruce entre empresas');
    echo "\nPruebas del modelo completadas. Comprueba también los formularios, CSRF y roles desde el navegador.\n";
} catch (Throwable $e) { fwrite(STDERR,'FALLO: '.$e->getMessage()."\n");$codigo=1; }
finally {
    if ($db && $creada && preg_match('/^gn_pago_test_[a-f0-9]{16}$/D',$temporal)) {
        $db->query('DROP DATABASE `'.$temporal.'`');
        echo "Base temporal de pruebas eliminada.\n";
    }
}
exit($codigo);
