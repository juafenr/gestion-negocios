<?php
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
define('BASEPATH',__DIR__.'/../system/');
require __DIR__.'/../application/libraries/Validacion_negocio.php';
function exige($condicion,$mensaje) { if (!$condicion) { throw new RuntimeException($mensaje); } echo 'OK: '.$mensaje."\n"; }
function rechaza($accion,$mensaje) { $rechazo=FALSE;try { $accion(); } catch (InvalidArgumentException $e) { $rechazo=TRUE; }exige($rechazo,$mensaje); }
try {
    exige(Validacion_negocio::decimal('1.5','Cantidad')==='1.50','Normaliza decimales exactos');
    exige(Validacion_negocio::decimal('0','Cantidad')==='0.00','Permite cero cuando corresponde');
    foreach (array('-0.01','1.001','1e3','1,50',' 2','100000000','',NULL,array('3'),1.5) as $v) {
        rechaza(function() use ($v) { Validacion_negocio::decimal($v,'Cantidad'); },'Rechaza cantidad inválida: '.json_encode($v));
    }
    rechaza(function() { Validacion_negocio::decimal('0','Compra',FALSE); },'Compra debe ser mayor que cero');
    rechaza(function() { Validacion_negocio::texto('   ','Nombre',150); },'No acepta nombre vacío tras quitar espacios');
    rechaza(function() { Validacion_negocio::texto(array('x'),'Nombre',150); },'No acepta arreglos en campos de texto');
    rechaza(function() { Validacion_negocio::fecha('2026-02-30'); },'Rechaza días inexistentes');
    exige(Validacion_negocio::fecha('2028-02-29')==='2028-02-29','Acepta día bisiesto válido');
    foreach (array('0','-1','1.5','1e2','4294967296',array(1)) as $v) {
        rechaza(function() use ($v) { Validacion_negocio::id($v); },'Identificador inválido rechazado');
    }
    rechaza(function() { Validacion_negocio::insumo(array('nombre'=>'Prueba','stock_minimo'=>'0','unidad_medida'=>'kg'),TRUE); },'Cantidad inicial obligatoria al crear insumo');
    rechaza(function() { Validacion_negocio::insumo(array('nombre'=>'Prueba','cantidad_actual'=>'0','stock_minimo'=>'-1','unidad_medida'=>'kg'),TRUE); },'Stock mínimo negativo rechazado');
    foreach (array("\u{00A0}", "\u{2003}\t", "\xFF", "abc\x01") as $valor) {
        rechaza(function() use ($valor) { Validacion_negocio::texto($valor,'Nombre',150); },'Texto vacío Unicode o codificación/control inválido rechazado');
    }
    foreach (array('        ', "\u{00A0}\u{00A0}\u{00A0}\u{00A0}", "abcd\0efghi", 'corta', array('secreto'), str_repeat('x',73)) as $valor) {
        rechaza(function() use ($valor) { Validacion_negocio::password($valor); },'Contraseña inválida rechazada antes de bcrypt');
    }
    exige(Validacion_negocio::password(' clave válida ') === ' clave válida ', 'La contraseña válida conserva sus espacios');
    $u=array('nombre'=>' Ana ', 'nombre_usuario'=>'ana.prueba', 'correo'=>'ana@example.test', 'rol_id'=>'1');
    exige(Validacion_negocio::usuario($u)['nombre']==='Ana','Usuario normalizado');
    foreach (array('nombre','nombre_usuario','correo','rol_id') as $campo) {
        $invalido=$u;unset($invalido[$campo]);
        rechaza(function() use ($invalido) { Validacion_negocio::usuario($invalido); },'Usuario exige '.$campo);
    }
    rechaza(function() { Validacion_negocio::proveedor(array('nombre'=>'Proveedor','correo'=>array('x'))); },'Proveedor rechaza correo de tipo arreglo');
    rechaza(function() { Validacion_negocio::proveedor(array('nombre'=>'Proveedor','correo'=>'no-es-correo')); },'Proveedor exige formato de correo');
    exige(Validacion_negocio::proveedor(array('nombre'=>'Proveedor'))['correo']==='','Contactos del proveedor opcionales');
    rechaza(function() { Validacion_negocio::cantidades_por_id(array(1=>'1','01'=>'2'),'Recepción'); },'Recepción rechaza identificadores repetidos tras normalizar');
    rechaza(function() { Validacion_negocio::cantidades_por_id(array(1=>array('1')),'Recepción'); },'Recepción rechaza cantidades anidadas');
    exige(Validacion_negocio::cantidades_por_id(array(1=>'0',2=>'1.5'),'Recepción')===array(1=>'0.00',2=>'1.50'),'Recepción permite ceros y normaliza cantidades');
    echo "Validaciones completadas.\n";
} catch (Throwable $e) { fwrite(STDERR,'FALLO: '.$e->getMessage()."\n");exit(1); }
