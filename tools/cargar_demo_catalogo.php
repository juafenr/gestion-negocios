<?php
/** Carga cuatro artículos de ejemplo en una empresa indicada. No altera los existentes. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
define('BASEPATH', $root.'/system/');
if (count($argv) !== 2 && count($argv) !== 3) {
    fwrite(STDERR, "Uso: php tools/cargar_demo_catalogo.php ID_EMPRESA [--aplicar]\n"); exit(1);
}
if (!ctype_digit($argv[1]) || (int)$argv[1] < 1 || (count($argv) === 3 && $argv[2] !== '--aplicar')) {
    fwrite(STDERR, "Indica un ID de empresa válido y, opcionalmente, --aplicar.\n"); exit(1);
}
$aplicar = count($argv) === 3;
$config = require $root.'/application/config/local.php';
$empresa = (int)$argv[1];
$db = NULL;
$copiados = array();
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name'],(int)$config['db_port']);
    $db->set_charset('utf8mb4');
    $q = $db->prepare('SELECT nombre FROM empresas WHERE id=?');
    $q->bind_param('i',$empresa); $q->execute();
    $negocio = $q->get_result()->fetch_assoc();
    if (!$negocio) { throw new RuntimeException('La empresa no existe.'); }
    foreach (array('insumos','productos') as $tabla) {
        $r = $db->query("SHOW COLUMNS FROM `$tabla` LIKE 'imagen'");
        if (!$r->num_rows) { throw new RuntimeException('Primero ejecuta tools/actualizar.php --aplicar.'); }
    }
    echo ($aplicar ? 'Cargando' : 'Vista previa para').' empresa '.$empresa.' ('.$negocio['nombre']. "):\n";
    echo "- Producto: Shuco clásico (foto, Q 25.00)\n- Producto: Shuco vegetariano (sin foto, Q 22.00)\n";
    echo "- Insumo: Pan para shuco (foto, 30 unidades)\n- Insumo: Repollo (sin foto, 2 kg)\n";
    if (!$aplicar) { echo "Sin cambios. Usa --aplicar para cargar los ejemplos.\n"; exit(0); }
    $destino = $root.'/application/uploads/catalogo/';
    if (!is_dir($destino) && !mkdir($destino,0700,TRUE)) { throw new RuntimeException('No se pudo crear el directorio de fotos.'); }
    $foto = function ($archivo) use ($root,$destino,&$copiados) {
        $origen = $root.'/assets/demo/'.$archivo;
        $info = getimagesize($origen);
        if (!$info || $info[2] !== IMAGETYPE_PNG || filesize($origen)>3*1024*1024) {
            throw new RuntimeException('Foto de ejemplo inválida: '.$archivo);
        }
        $nombre = bin2hex(random_bytes(16)).'.png';
        if (!copy($origen,$destino.$nombre)) { throw new RuntimeException('No se pudo copiar la foto '.$archivo); }
        $copiados[] = $destino.$nombre;
        return $nombre;
    };
    $db->begin_transaction();
    $q = $db->prepare('SELECT id FROM insumos WHERE empresa_id=? AND nombre=?');
    $nombre = 'Pan para shuco (demo)'; $q->bind_param('is',$empresa,$nombre); $q->execute();
    if (!$q->get_result()->fetch_assoc()) {
        $imagen = $foto('pan-shuco.png');
        $s = $db->prepare('INSERT INTO insumos (empresa_id,nombre,imagen,cantidad_actual,stock_minimo,unidad_medida) VALUES (?,?,?,30,5,\'unidad\')');
        $s->bind_param('iss',$empresa,$nombre,$imagen); $s->execute();
    }
    $nombre = 'Repollo (demo)'; $q->bind_param('is',$empresa,$nombre); $q->execute();
    if (!$q->get_result()->fetch_assoc()) {
        $s = $db->prepare('INSERT INTO insumos (empresa_id,nombre,cantidad_actual,stock_minimo,unidad_medida) VALUES (?,?,2,0.5,\'kg\')');
        $s->bind_param('is',$empresa,$nombre); $s->execute();
    }
    $q = $db->prepare('SELECT id FROM productos WHERE empresa_id=? AND sku=?');
    $sku = 'DEMO-SHUCO-01'; $q->bind_param('is',$empresa,$sku); $q->execute();
    if (!$q->get_result()->fetch_assoc()) {
        $imagen = $foto('shuco-clasico.png');
        $s = $db->prepare('INSERT INTO productos (empresa_id,sku,nombre,imagen,descripcion,precio) VALUES (?,?,?,?,?,25.00)');
        $nombre = 'Shuco clásico (demo)'; $descripcion = 'Ejemplo de catálogo con imagen; precio ilustrativo.';
        $s->bind_param('issss',$empresa,$sku,$nombre,$imagen,$descripcion); $s->execute();
    }
    $sku = 'DEMO-SHUCO-02'; $q->bind_param('is',$empresa,$sku); $q->execute();
    if (!$q->get_result()->fetch_assoc()) {
        $s = $db->prepare('INSERT INTO productos (empresa_id,sku,nombre,descripcion,precio) VALUES (?,?,?,?,22.00)');
        $nombre = 'Shuco vegetariano (demo)'; $descripcion = 'Ejemplo sin imagen para mostrar el marcador.';
        $s->bind_param('isss',$empresa,$sku,$nombre,$descripcion); $s->execute();
    }
    $db->commit();
    echo "Ejemplos cargados. Vuelve a ejecutar con --aplicar sin duplicarlos.\n";
} catch (Throwable $e) {
    if ($db) { try { $db->rollback(); } catch (Throwable $ignorado) {} }
    foreach ($copiados as $archivo) { @unlink($archivo); }
    fwrite(STDERR,'No se cargaron ejemplos: '.$e->getMessage()."\n"); exit(1);
}
