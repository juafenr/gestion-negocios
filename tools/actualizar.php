<?php
/**
 * CLI: diagnostica por defecto.
 * --aplicar modifica exclusivamente la base local configurada.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASEPATH', __DIR__ . '/../system/');

$root = dirname(__DIR__);
$aplicar = in_array('--aplicar', $argv, TRUE);

if (count(array_diff(array_slice($argv, 1), array('--aplicar')))) {
    fwrite(STDERR, "Uso: php tools/actualizar.php [--aplicar]\n");
    exit(1);
}

if (!extension_loaded('mysqli')) {
    fwrite(STDERR, "Usa PHP de XAMPP con mysqli.\n");
    exit(1);
}

if (!is_file($root . '/application/config/local.php')) {
    fwrite(
        STDERR,
        "Falta application/config/local.php. Copia local.example.php y configura tu conexión.\n"
    );
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class Actualizador
{
    private $db;
    private $aplicar;
    private $tablas = array();
    private $columnas = array();

    public $pendientes = 0;

    public function __construct($db, $aplicar)
    {
        $this->db = $db;
        $this->aplicar = $aplicar;

        $resultado = $db->query('SHOW TABLES');

        while ($fila = $resultado->fetch_row()) {
            $this->tablas[$fila[0]] = TRUE;
            $this->recargar($fila[0]);
        }
    }

    private function recargar($tabla)
    {
        $this->columnas[$tabla] = array();

        foreach ($this->db->query('SHOW COLUMNS FROM `' . $tabla . '`') as $columna) {
            $this->columnas[$tabla][$columna['Field']] = $columna;
        }
    }

    public function tabla($tabla)
    {
        return isset($this->tablas[$tabla]);
    }

    public function columna($tabla, $columna)
    {
        return isset($this->columnas[$tabla][$columna]);
    }

    public function tipo($tabla, $columna)
    {
        return $this->columnas[$tabla][$columna]['Type'] ?? '';
    }

    public function filas($sql)
    {
        return $this->db->query($sql);
    }

    public function operacion($titulo, $sql)
    {
        $this->pendientes++;

        echo ($this->aplicar ? 'Aplicar: ' : 'Pendiente: ')
            . $titulo . "\n";

        if ($this->aplicar) {
            $this->db->query($sql);
        }
    }

    public function crear($tabla, $sql)
    {
        if ($this->tabla($tabla)) {
            return;
        }

        $this->operacion('crear tabla ' . $tabla, $sql);

        if ($this->aplicar) {
            $this->tablas[$tabla] = TRUE;
            $this->recargar($tabla);
        }
    }

    public function agregar($tabla, $columna, $definicion)
    {
        if (!$this->tabla($tabla) || $this->columna($tabla, $columna)) {
            return;
        }

        $this->operacion(
            'agregar ' . $tabla . '.' . $columna,
            'ALTER TABLE `' . $tabla . '` ADD COLUMN `'
                . $columna . '` ' . $definicion
        );

        if ($this->aplicar) {
            $this->recargar($tabla);
        }
    }

    public function indice($tabla, $nombre, $columnas, $unico, $definicion)
    {
        if (!$this->tabla($tabla)) {
            return;
        }

        foreach ($columnas as $columna) {
            if (!$this->columna($tabla, $columna)) {
                return;
            }
        }

        $indices = array();

        foreach ($this->db->query('SHOW INDEX FROM `' . $tabla . '`') as $indice) {
            $indices[$indice['Key_name']]['cols'][(int) $indice['Seq_in_index']]
                = $indice['Column_name'];

            $indices[$indice['Key_name']]['unico']
                = !(int) $indice['Non_unique'];
        }

        foreach ($indices as $nombreActual => $indice) {
            ksort($indice['cols']);

            if (
                array_values($indice['cols']) === $columnas
                && (!$unico || $indice['unico'])
            ) {
                return;
            }

            if ($nombreActual === $nombre) {
                throw new RuntimeException(
                    'El índice ' . $nombre
                    . ' existe con otra definición. Revisión manual necesaria.'
                );
            }
        }

        if ($unico) {
            $cols = '`' . implode('`,`', $columnas) . '`';

            $where = implode(
                ' AND ',
                array_map(function ($columna) {
                    return '`' . $columna . '` IS NOT NULL';
                }, $columnas)
            );

            $duplicados = $this->db->query(
                'SELECT 1 FROM `' . $tabla . '` WHERE ' . $where
                . ' GROUP BY ' . $cols
                . ' HAVING COUNT(*) > 1 LIMIT 1'
            );

            if ($duplicados->num_rows) {
                throw new RuntimeException(
                    'Hay duplicados en ' . $tabla
                    . ' (' . implode(', ', $columnas) . '). '
                    . 'Resuélvelos antes de crear ' . $nombre
                    . '. No se borraron registros.'
                );
            }
        }

        $this->operacion(
            'índice ' . $tabla . '.' . $nombre,
            'ALTER TABLE `' . $tabla . '` ADD ' . $definicion
        );
    }

    public function relacion(
        $tabla,
        $nombre,
        $hijas,
        $padre,
        $padres,
        $definicion
    ) {
        if (!$this->tabla($tabla) || !$this->tabla($padre)) {
            return;
        }

        foreach ($hijas as $columna) {
            if (!$this->columna($tabla, $columna)) {
                return;
            }
        }

        foreach ($padres as $columna) {
            if (!$this->columna($padre, $columna)) {
                return;
            }
        }

        // Los nombres proceden únicamente del schema.sql del proyecto.
        $consulta = $this->db->prepare(
            'SELECT
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY ORDINAL_POSITION'
        );

        $consulta->bind_param('s', $tabla);
        $consulta->execute();

        $existentes = array();

        foreach ($consulta->get_result() as $fila) {
            $existentes[$fila['CONSTRAINT_NAME']][] = $fila;
        }

        foreach ($existentes as $nombreActual => $campos) {
            if (
                array_column($campos, 'COLUMN_NAME') === $hijas
                && array_column($campos, 'REFERENCED_COLUMN_NAME') === $padres
                && $campos[0]['REFERENCED_TABLE_NAME'] === $padre
            ) {
                return;
            }

            if ($nombreActual === $nombre) {
                throw new RuntimeException(
                    'La relación ' . $nombre
                    . ' tiene otra definición. Requiere revisión manual.'
                );
            }
        }

        $on = array();
        $where = array();

        foreach ($hijas as $indice => $columna) {
            $on[] = 'h.`' . $columna . '`=p.`' . $padres[$indice] . '`';
            $where[] = 'h.`' . $columna . '` IS NOT NULL';
        }

        $sinReferencia = $this->db->query(
            'SELECT 1 FROM `' . $tabla . '` h'
            . ' LEFT JOIN `' . $padre . '` p ON ' . implode(' AND ', $on)
            . ' WHERE ' . implode(' AND ', $where)
            . ' AND p.`' . $padres[0] . '` IS NULL LIMIT 1'
        );

        if ($sinReferencia->num_rows) {
            throw new RuntimeException(
                'Hay registros sin referencia válida para ' . $nombre
                . '. Corrige los datos antes de continuar.'
            );
        }

        $this->operacion(
            'relación ' . $nombre,
            'ALTER TABLE `' . $tabla . '` ADD ' . $definicion
        );
    }
}

try {
    $c = require $root . '/application/config/local.php';

    $db = new mysqli(
        $c['db_host'],
        $c['db_user'],
        $c['db_pass'],
        $c['db_name'],
        (int) $c['db_port']
    );

    $db->set_charset('utf8mb4');
    $db->query(
        "SET SESSION sql_mode='STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'"
    );

    if ($aplicar) {
        $lock = 'gn_migracion_' . md5($c['db_name']);
        $consulta = $db->prepare('SELECT GET_LOCK(?, 0) adquirido');
        $consulta->bind_param('s', $lock);
        $consulta->execute();

        if ((int) $consulta->get_result()->fetch_assoc()['adquirido'] !== 1) {
            throw new RuntimeException('Ya hay otra actualización en curso.');
        }
    }

    $a = new Actualizador($db, $aplicar);

    foreach (array('empresas', 'usuarios', 'productos') as $base) {
        if (!$a->tabla($base)) {
            throw new RuntimeException(
                'Falta la tabla base ' . $base
                . '. Para una base nueva usa tools/instalar.php.'
            );
        }
    }

    // No inventar cobros anteriores sin historial.
    if ($a->tabla('pedidos')) {
        if (
            !$a->tabla('pagos')
            && $db->query(
                "SELECT id FROM pedidos
                 WHERE estado_pago <> 'pendiente' LIMIT 1"
            )->num_rows
        ) {
            throw new RuntimeException(
                'Hay pedidos marcados con pagos anteriores sin historial. '
                . 'Revisa esos registros antes de migrar; no se inventarán '
                . 'cobros ni se reiniciarán sus estados.'
            );
        }

        if (
            $a->tabla('pagos')
            && !$a->columna('pagos', 'propina')
            && $db->query('SELECT id FROM pagos LIMIT 1')->num_rows
        ) {
            throw new RuntimeException(
                'Hay pagos de otra versión sin desglose de propina. '
                . 'Se necesita conciliarlos antes de actualizar.'
            );
        }
    }

    echo 'Base: ' . $c['db_name'] . "\n";
    echo $aplicar
        ? "Actualización en curso. Conserva tu respaldo hasta probar el sistema.\n"
        : "Diagnóstico: no modifica la base.\n";

    $schema = file_get_contents($root . '/database/schema.sql');

    preg_match_all(
        '/CREATE\s+TABLE\s+(\w+)\s*\([\s\S]+?;/i',
        $schema,
        $matches,
        PREG_SET_ORDER
    );

    $creaciones = array();

    foreach ($matches as $match) {
        $creaciones[$match[1]] = $match[0];
    }

    foreach ($db->query('SHOW TABLE STATUS') as $tabla) {
        if (
            isset($creaciones[$tabla['Name']])
            && strcasecmp((string) $tabla['Engine'], 'InnoDB') !== 0
        ) {
            throw new RuntimeException(
                'La tabla ' . $tabla['Name']
                . ' necesita InnoDB para proteger las transacciones. '
                . 'Revisa su conversión antes de continuar.'
            );
        }
    }

    // Catálogos básicos.
    foreach (array('roles', 'permisos', 'rol_permisos', 'login_intentos') as $tabla) {
        $a->crear($tabla, $creaciones[$tabla]);
    }

    $roles = array(
        'administrador' => 'Administrador',
        'cajero' => 'Cajero',
        'cocinero' => 'Cocinero'
    );

    $nuevosRoles = array();

    foreach ($roles as $codigo => $nombre) {
        if (!$a->tabla('roles')) {
            continue;
        }

        $consulta = $db->query(
            "SELECT id FROM roles WHERE codigo='" . $codigo . "'"
        );

        if (!$consulta->num_rows) {
            $a->operacion(
                'rol ' . $codigo,
                "INSERT INTO roles (codigo,nombre,descripcion)
                 VALUES ('$codigo','$nombre','Rol del sistema')"
            );

            $nuevosRoles[] = $codigo;
        }
    }

    // Usuarios.
    $a->agregar('usuarios', 'rol_id', 'INT UNSIGNED NULL');

    if ($a->columna('usuarios', 'rol_id')) {
        if (
            $db->query(
                'SELECT id FROM usuarios WHERE rol_id IS NULL LIMIT 1'
            )->num_rows
        ) {
            $a->operacion(
                'asignar administrador a usuarios previos a RBAC (regla de migración 001)',
                "UPDATE usuarios
                 SET rol_id = (
                     SELECT id FROM roles WHERE codigo='administrador'
                 )
                 WHERE rol_id IS NULL"
            );
        }

        $nullable = $db->query(
            "SHOW COLUMNS FROM usuarios LIKE 'rol_id'"
        )->fetch_assoc();

        if ($nullable['Null'] === 'YES') {
            $a->operacion(
                'usuarios.rol_id obligatorio',
                'ALTER TABLE usuarios MODIFY rol_id INT UNSIGNED NOT NULL'
            );
        }
    }

    $a->agregar(
        'usuarios',
        'nombre_usuario',
        'VARCHAR(40) NULL AFTER nombre'
    );

    if ($a->columna('usuarios', 'nombre_usuario')) {
        if (
            $db->query(
                'SELECT id FROM usuarios
                 WHERE nombre_usuario IS NULL LIMIT 1'
            )->num_rows
        ) {
            $a->operacion(
                'asignar nombres de usuario a cuentas existentes',
                "UPDATE usuarios
                 SET nombre_usuario=CONCAT('usuario',id)
                 WHERE nombre_usuario IS NULL"
            );
        }

        $nullable = $db->query(
            "SHOW COLUMNS FROM usuarios LIKE 'nombre_usuario'"
        )->fetch_assoc();

        if ($nullable['Null'] === 'YES') {
            $a->operacion(
                'usuarios.nombre_usuario obligatorio',
                'ALTER TABLE usuarios
                 MODIFY nombre_usuario VARCHAR(40) NOT NULL'
            );
        }
    }

    // Empresas, proveedores e inventario.
    $a->agregar(
        'empresas',
        'ultimo_numero_pedido',
        'INT UNSIGNED NOT NULL DEFAULT 0'
    );

    $a->agregar(
        'empresas',
        'ultimo_numero_compra',
        'INT UNSIGNED NOT NULL DEFAULT 0'
    );

    $a->crear('proveedores', $creaciones['proveedores']);
    $a->crear('insumos', $creaciones['insumos']);
    $a->agregar('insumos', 'fecha_vencimiento', 'DATE NULL');
    $a->agregar('insumos', 'proveedor_id', 'INT UNSIGNED NULL');

    // Catálogo: agregar únicamente columnas que todavía no existen.
    $a->agregar('productos', 'insumo_id', 'INT UNSIGNED NULL');

    $a->agregar(
        'productos',
        'descripcion',
        "VARCHAR(1000) NOT NULL DEFAULT ''"
    );

    $a->agregar(
        'productos',
        'disponible',
        'TINYINT UNSIGNED NOT NULL DEFAULT 1'
    );

    // Pedidos y numeración por empresa.
    $a->crear('pedidos', $creaciones['pedidos']);

    $a->agregar(
        'pedidos',
        'numero',
        'INT UNSIGNED NULL AFTER empresa_id'
    );

    if ($a->columna('pedidos', 'numero')) {
        $faltan = (int) $db->query(
            'SELECT COUNT(*) n FROM pedidos WHERE numero IS NULL'
        )->fetch_assoc()['n'];

        if ($faltan) {
            if (
                $db->query(
                    'SELECT id FROM pedidos WHERE numero IS NOT NULL LIMIT 1'
                )->num_rows
            ) {
                throw new RuntimeException(
                    'Numeración parcialmente asignada. Revisión manual '
                    . 'necesaria para conservar los números ya utilizados.'
                );
            }

            $a->operacion(
                'numerar pedidos por empresa (solo si ninguno tiene número)',
                'UPDATE pedidos p
                 JOIN (
                     SELECT x.id, COUNT(y.id) numero
                     FROM pedidos x
                     JOIN pedidos y
                       ON y.empresa_id=x.empresa_id AND y.id<=x.id
                     GROUP BY x.id
                 ) n ON n.id=p.id
                 SET p.numero=n.numero'
            );
        }

        $nullable = $db->query(
            "SHOW COLUMNS FROM pedidos LIKE 'numero'"
        )->fetch_assoc();

        if ($nullable['Null'] === 'YES') {
            $a->operacion(
                'pedido.numero obligatorio',
                'ALTER TABLE pedidos MODIFY numero INT UNSIGNED NOT NULL'
            );
        }

        if (
            $a->columna('empresas', 'ultimo_numero_pedido')
            && $db->query(
                'SELECT e.id
                 FROM empresas e
                 JOIN pedidos p ON p.empresa_id=e.id
                 WHERE p.numero>e.ultimo_numero_pedido
                 LIMIT 1'
            )->num_rows
        ) {
            $a->operacion(
                'actualizar contadores sin reducir los ya usados',
                'UPDATE empresas e
                 JOIN (
                     SELECT empresa_id, MAX(numero) ultimo
                     FROM pedidos
                     GROUP BY empresa_id
                 ) p ON p.empresa_id=e.id
                 SET e.ultimo_numero_pedido =
                     GREATEST(e.ultimo_numero_pedido,p.ultimo)'
            );
        }
    }

    // Preparar índices padres antes de crear tablas que los referencian.
    $lista = function ($texto) {
        return array_map(
            'trim',
            explode(',', str_replace('`', '', $texto))
        );
    };

    $indices = function ($tabla, $sql) use ($a, $lista) {
        preg_match_all(
            '/(UNIQUE\s+KEY|KEY|INDEX)\s+(\w+)\s*\(([^)]+)\)/i',
            $sql,
            $coincidencias,
            PREG_SET_ORDER
        );

        foreach ($coincidencias as $indice) {
            $a->indice(
                $tabla,
                $indice[2],
                $lista($indice[3]),
                stripos($indice[1], 'UNIQUE') === 0,
                $indice[0]
            );
        }
    };

    foreach (array('usuarios', 'insumos', 'productos', 'pedidos') as $tabla) {
        $indices($tabla, $creaciones[$tabla]);
    }

    // Auditoría de precios: sus índices padres ya están preparados.
    $a->crear(
        'producto_precio_historial',
        $creaciones['producto_precio_historial']
    );

    foreach (
        array(
            'pedido_detalles',
            'pedido_cancelaciones',
            'pedido_eventos',
            'movimientos_inventario'
        ) as $tabla
    ) {
        $a->crear($tabla, $creaciones[$tabla]);
    }

    // Historial de cocina.
    $a->agregar(
        'pedido_eventos',
        'rol_codigo',
        'VARCHAR(50) NULL AFTER usuario_id'
    );

    if (
        $a->tabla('pedido_eventos')
        && $a->columna('pedido_eventos', 'rol_codigo')
    ) {
        if (
            $db->query(
                'SELECT id FROM pedido_eventos
                 WHERE rol_codigo IS NULL LIMIT 1'
            )->num_rows
        ) {
            $a->operacion(
                'recuperar rol de eventos anteriores',
                'UPDATE pedido_eventos evento
                 JOIN usuarios usuario ON usuario.id=evento.usuario_id
                 JOIN roles rol ON rol.id=usuario.rol_id
                 SET evento.rol_codigo=rol.codigo
                 WHERE evento.rol_codigo IS NULL'
            );
        }

        if (
            $a->tipo('pedido_eventos', 'rol_codigo') === 'varchar(50)'
            && $db->query(
                "SHOW COLUMNS FROM pedido_eventos LIKE 'rol_codigo'"
            )->fetch_assoc()['Null'] === 'YES'
        ) {
            $a->operacion(
                'pedido_eventos.rol_codigo obligatorio',
                'ALTER TABLE pedido_eventos
                 MODIFY rol_codigo VARCHAR(50) NOT NULL'
            );
        }

        if (
            $db->query(
                "SELECT id FROM pedido_eventos
                 WHERE estado_anterior='preparacion'
                    OR estado_nuevo='preparacion'
                 LIMIT 1"
            )->num_rows
        ) {
            $a->operacion(
                'normalizar historial de cocina',
                "UPDATE pedido_eventos
                 SET estado_anterior=IF(
                         estado_anterior='preparacion',
                         'en_preparacion',
                         estado_anterior
                     ),
                     estado_nuevo=IF(
                         estado_nuevo='preparacion',
                         'en_preparacion',
                         estado_nuevo
                     )
                 WHERE estado_anterior='preparacion'
                    OR estado_nuevo='preparacion'"
            );
        }
    }

    if (
        $a->tabla('pedidos')
        && $db->query(
            "SELECT id FROM pedidos WHERE estado='preparacion' LIMIT 1"
        )->num_rows
    ) {
        $a->operacion(
            'normalizar estado de cocina',
            "UPDATE pedidos SET estado='en_preparacion'
             WHERE estado='preparacion'"
        );
    }

    // Movimientos de inventario.
    $a->agregar(
        'movimientos_inventario',
        'pedido_id',
        'INT UNSIGNED NULL AFTER usuario_id'
    );

    if (
        $a->tabla('movimientos_inventario')
        && $a->tipo('movimientos_inventario', 'tipo')
            !== "enum('entrada','salida','ajuste')"
    ) {
        if (
            $db->query(
                "SELECT id FROM movimientos_inventario
                 WHERE tipo NOT IN ('entrada','salida','ajuste')
                 LIMIT 1"
            )->num_rows
        ) {
            throw new RuntimeException(
                'Hay tipos de movimiento desconocidos. '
                . 'Revísalos antes de continuar.'
            );
        }

        $a->operacion(
            'permitir ajustes en inventario',
            "ALTER TABLE movimientos_inventario
             MODIFY tipo ENUM('entrada','salida','ajuste') NOT NULL"
        );
    }

    // Pagos.
    $indices('pedido_detalles', $creaciones['pedido_detalles']);

    foreach (array('pago_cuentas', 'pago_cuenta_detalles', 'pagos') as $tabla) {
        $a->crear($tabla, $creaciones[$tabla]);
    }

    foreach (array('pago_cuentas', 'pagos') as $tabla) {
        $a->agregar(
            $tabla,
            'propina',
            'DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0'
        );
    }

    // Ventas y compras.
    foreach (
        array(
            'ventas',
            'solicitudes_compra',
            'solicitud_compra_detalles',
            'solicitud_compra_eventos',
            'recepciones_compra',
            'recepcion_compra_detalles'
        ) as $tabla
    ) {
        $a->crear($tabla, $creaciones[$tabla]);
    }

    if (
        $a->tabla('solicitudes_compra')
        && $a->columna('empresas', 'ultimo_numero_compra')
        && $db->query(
            'SELECT e.id
             FROM empresas e
             JOIN solicitudes_compra s ON s.empresa_id=e.id
             WHERE s.numero>e.ultimo_numero_compra
             LIMIT 1'
        )->num_rows
    ) {
        $a->operacion(
            'actualizar contador de compras sin reducirlo',
            'UPDATE empresas e
             JOIN (
                 SELECT empresa_id, MAX(numero) ultimo
                 FROM solicitudes_compra
                 GROUP BY empresa_id
             ) s ON s.empresa_id=e.id
             SET e.ultimo_numero_compra =
                 GREATEST(e.ultimo_numero_compra,s.ultimo)'
        );
    }

    // Revisar índices, relaciones y columnas del esquema integrado.
    foreach ($creaciones as $tabla => $sql) {
        $indices($tabla, $sql);

        preg_match_all(
            '/CONSTRAINT\s+(\w+)\s+FOREIGN\s+KEY\s*\(([^)]+)\)'
            . '\s+REFERENCES\s+(\w+)\s*\(([^)]+)\)'
            . '(?:\s+ON\s+DELETE\s+CASCADE)?/i',
            $sql,
            $relaciones,
            PREG_SET_ORDER
        );

        foreach ($relaciones as $relacion) {
            $a->relacion(
                $tabla,
                $relacion[1],
                $lista($relacion[2]),
                $relacion[3],
                $lista($relacion[4]),
                $relacion[0]
            );
        }

        if (!$a->tabla($tabla)) {
            continue;
        }

        preg_match_all(
            '/^\s*(\w+)\s+(?:INT|BIGINT|VARCHAR|CHAR|TINYINT|DECIMAL|DATETIME|DATE|ENUM)\b/im',
            $sql,
            $columnas
        );

        foreach ($columnas[1] as $columna) {
            if (!$a->columna($tabla, $columna)) {
                if ($aplicar) {
                    throw new RuntimeException(
                        'Falta una columna no contemplada: '
                        . $tabla . '.' . $columna
                        . '. Requiere revisión manual.'
                    );
                }

                echo 'Comprobar después de aplicar: '
                    . $tabla . '.' . $columna . "\n";
            }
        }
    }

    // Recuperar permisos faltantes, sin restablecer permisos revocados.
    $permisos = array(
        'inicio.ver',
        'usuarios.ver',
        'usuarios.gestionar',
        'productos.ver',
        'productos.gestionar',
        'inventario.ver',
        'inventario.gestionar',
        'proveedores.ver',
        'proveedores.gestionar',
        'pedidos.ver',
        'pedidos.crear',
        'pedidos.confirmar',
        'pedidos.actualizar_cocina',
        'pedidos.cancelar',
        'ventas.ver',
        'ventas.cobrar',
        'ventas.historial',
        'compras.ver',
        'compras.gestionar',
        'compras.confirmar',
        'compras.exportar',
        'reportes.ver',
        'empresa.configurar'
    );

    $cajero = array(
        'inicio.ver',
        'productos.ver',
        'pedidos.ver',
        'pedidos.crear',
        'pedidos.confirmar',
        'ventas.ver',
        'ventas.cobrar'
    );

    $cocinero = array(
        'inicio.ver',
        'productos.ver',
        'pedidos.ver',
        'pedidos.actualizar_cocina'
    );

    if ($a->tabla('permisos') && $a->tabla('rol_permisos')) {
        foreach ($permisos as $codigo) {
            $nuevo = !$db->query(
                "SELECT id FROM permisos WHERE codigo='$codigo'"
            )->num_rows;

            if ($nuevo) {
                $a->operacion(
                    'permiso ' . $codigo,
                    "INSERT INTO permisos (codigo,nombre,descripcion)
                     VALUES ('$codigo','$codigo','Permiso del sistema')"
                );
            }

            foreach ($roles as $rol => $nombre) {
                $corresponde = $rol === 'administrador'
                    || (
                        $rol === 'cajero'
                        && in_array($codigo, $cajero, TRUE)
                    )
                    || (
                        $rol === 'cocinero'
                        && in_array($codigo, $cocinero, TRUE)
                    );

                if (
                    $corresponde
                    && ($nuevo || in_array($rol, $nuevosRoles, TRUE))
                ) {
                    $a->operacion(
                        'asignar nuevo permiso ' . $codigo . ' a ' . $rol,
                        "INSERT IGNORE INTO rol_permisos (rol_id,permiso_id)
                         SELECT r.id,p.id
                         FROM roles r
                         CROSS JOIN permisos p
                         WHERE r.codigo='$rol' AND p.codigo='$codigo'"
                    );
                }
            }
        }
    }

    // Conciliar ventas con cobros reales.
    if (
        $a->tabla('ventas')
        && $a->tabla('pagos')
        && $a->columna('pagos', 'propina')
    ) {
        $sql = trim(
            file_get_contents($root . '/database/conciliar_ventas.sql')
        );

        $inicio = strpos($sql, "\nFROM pedidos p");

        if ($inicio === FALSE) {
            throw new RuntimeException('Archivo de conciliación no válido.');
        }

        $conteo = 'SELECT COUNT(*) cantidad' . substr($sql, $inicio);
        $cantidad = (int) $db->query($conteo)->fetch_assoc()['cantidad'];

        if ($cantidad > 0) {
            $a->operacion(
                'recuperar ' . $cantidad . ' ventas de pedidos ya pagados',
                $sql
            );
        }

        $sinVenta = (int) $db->query(
            "SELECT COUNT(*) n
             FROM pedidos p
             LEFT JOIN ventas v
               ON v.empresa_id=p.empresa_id AND v.pedido_id=p.id
             WHERE p.estado_pago='pagado' AND v.id IS NULL"
        )->fetch_assoc()['n'];

        if (!$aplicar) {
            $sinVenta = max(0, $sinVenta - $cantidad);
        }

        if ($sinVenta > 0) {
            echo 'ATENCIÓN: ' . $sinVenta
                . ' pedidos marcados pagados no tienen cobros consistentes '
                . 'para crear su venta. Revisa sus datos; '
                . "no se inventaron importes ni métodos.\n";
        }
    }

    if ($aplicar) {
        echo "Actualización terminada. Ejecuta de nuevo sin --aplicar: "
            . "debe indicar Sin cambios pendientes.\n";
    } elseif ($a->pendientes) {
        echo "\nHay cambios pendientes. Respalda la base, detén Apache "
            . "(mantén MySQL activo) y ejecuta:\n"
            . "php tools/actualizar.php --aplicar\n";

        echo "El diagnóstico de una base antigua puede mostrar nuevas "
            . "dependencias al aplicar; se comprueban antes de cada cambio.\n";
    } else {
        echo "Sin cambios pendientes. Estructura compatible "
            . "con el esquema integrado.\n";
    }
} catch (Throwable $error) {
    fwrite(
        STDERR,
        'DETENIDO: ' . $error->getMessage()
        . "\nNo se eliminan datos. Si estabas aplicando cambios, "
        . 'algunos DDL pueden haber quedado realizados; '
        . 'no son una transacción global. '
        . "Corrige el problema y repite el diagnóstico.\n"
    );

    exit(1);
}