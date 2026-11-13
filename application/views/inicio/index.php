<?php $this->load->view('plantilla/cabecera', array('titulo' => 'Inicio', 'seccion' => 'inicio', 'usuario' => $usuario)); ?>
<p class="sobretitulo">TU NEGOCIO</p>
<h1>Panel de tu negocio</h1>
<section class="recuadro bienvenida bienvenida-inicio">
    <div>
        <h2>Hola, <?= html_escape($usuario->nombre()) ?></h2>
        <p>Bienvenido a <strong><?= html_escape($usuario->empresaNombre()) ?></strong>.</p>
    </div>
    <span class="bienvenida-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/negocio.svg')) ?>" alt="" width="22" height="22"></span>
</section>
<?php if ($aviso_resumen !== ''): ?>
    <p class="aviso" role="status"><?= html_escape($aviso_resumen) ?></p>
<?php endif; ?>
<?php if ($resumen): ?>
<section aria-labelledby="titulo-resumen">
    <h2 id="titulo-resumen">Resumen de tu empresa</h2>
    <div class="resumen-pagos">
        <?php if (isset($resumen['ventas'])): ?>
        <div class="recuadro indicador-inicio">
            <span class="indicador-icono" aria-hidden="true"><img class="icono" src="<?= html_escape(base_url('assets/iconos/ventas.svg')) ?>" alt=""></span>
            <span>Ventas de hoy sin propina</span>
            <strong><?= negocio_moneda($resumen['ventas']['consumo']) ?></strong>
            <p><?= (int) $resumen['ventas']['cantidad'] ?> <?= (int) $resumen['ventas']['cantidad'] === 1 ? 'pedido pagado' : 'pedidos pagados' ?> · Propinas: <?= negocio_moneda($resumen['ventas']['propina']) ?></p>
            <a href="<?= html_escape(site_url('ventas').'?'.http_build_query(array('desde'=>date('Y-m-d'), 'hasta'=>date('Y-m-d')))) ?>">Consultar ventas de hoy</a>
        </div>
        <?php endif; ?>
        <?php if (isset($resumen['cocina'])): ?>
        <div class="recuadro indicador-inicio"><span class="indicador-icono" aria-hidden="true"><img class="icono" src="<?= html_escape(base_url('assets/iconos/pedidos.svg')) ?>" alt=""></span><span>Pedidos en cocina</span><strong><?= $resumen['cocina'] ?></strong><p>Pendientes, en preparación o listos.</p><a href="<?= html_escape(site_url('cocina')) ?>">Consultar cocina</a></div>
        <?php endif; ?>
        <?php if (isset($resumen['por_cobrar'])): ?>
        <div class="recuadro indicador-inicio"><span class="indicador-icono" aria-hidden="true"><img class="icono" src="<?= html_escape(base_url('assets/iconos/pagos.svg')) ?>" alt=""></span><span>Pedidos por cobrar</span><strong><?= $resumen['por_cobrar'] ?></strong><p>Confirmados con pago pendiente o parcial.</p><a href="<?= html_escape(site_url('pagos')) ?>">Consultar pagos</a></div>
        <?php endif; ?>
        <?php if (isset($resumen['reponer'])): ?>
        <div class="recuadro indicador-inicio"><span class="indicador-icono" aria-hidden="true"><img class="icono" src="<?= html_escape(base_url('assets/iconos/inventario.svg')) ?>" alt=""></span><span>Insumos por reponer</span><strong><?= $resumen['reponer'] ?></strong><p>Activos, agotados o en el mínimo de existencias.</p><a href="<?= html_escape(site_url('inventario')) ?>">Revisar inventario</a></div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
<?php if (isset($resumen['ultimos_dias'])):
    $serie = $resumen['ultimos_dias'];
    // Solo la geometría se representa en decimales; los montos visibles conservan su formato exacto.
    $maximo = max(1, max(array_map(function ($dia) { return (float) $dia['consumo']; }, $serie)));
    $puntos = array();
    foreach ($serie as $i => $dia) {
        $puntos[] = (60 + $i * 83).','.round(190 - ((float) $dia['consumo'] / $maximo) * 150, 2);
    }
    $trazo = implode(' ', $puntos);
?>
<div class="panel-inicio">
    <section class="recuadro" aria-labelledby="titulo-actividad">
        <div class="panel-encabezado"><div><h2 id="titulo-actividad">Actividad de ventas</h2><p>Ventas completadas, sin incluir propinas.</p></div><span class="etiqueta-periodo">Últimos 7 días</span></div>
        <svg class="grafico-ventas" viewBox="0 0 590 235" role="img" aria-labelledby="grafico-titulo grafico-descripcion">
            <title id="grafico-titulo">Ventas de los últimos siete días</title>
            <desc id="grafico-descripcion">Importes diarios de tu empresa. La tabla debajo de la gráfica contiene los valores exactos.</desc>
            <defs><linearGradient id="relleno-ventas" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#f47b36" stop-opacity=".18"/><stop offset="100%" stop-color="#f47b36" stop-opacity="0"/></linearGradient></defs>
            <?php foreach (array(40,115,190) as $y): ?><line class="rejilla" x1="60" y1="<?= $y ?>" x2="558" y2="<?= $y ?>"/><?php endforeach; ?>
            <text x="48" y="44" text-anchor="end"><?= number_format($maximo, $maximo < 10 ? 1 : 0) ?></text>
            <text x="48" y="119" text-anchor="end"><?= number_format($maximo / 2, $maximo < 10 ? 1 : 0) ?></text>
            <text x="48" y="194" text-anchor="end">0</text>
            <polygon points="60,190 <?= $trazo ?> 558,190" fill="url(#relleno-ventas)"/>
            <polyline class="linea" points="<?= $trazo ?>"/>
            <?php foreach ($serie as $i => $dia): $xy = explode(',', $puntos[$i]); ?>
            <circle cx="<?= $xy[0] ?>" cy="<?= $xy[1] ?>" r="4"><title><?= html_escape($dia['dia'].' · '.negocio_moneda($dia['consumo'])) ?></title></circle>
            <text x="<?= $xy[0] ?>" y="220" text-anchor="middle"><?= html_escape(substr($dia['dia'], 8, 2).'/'.substr($dia['dia'], 5, 2)) ?></text>
            <?php endforeach; ?>
        </svg>
        <p class="grafico-leyenda">Consumo vendido en quetzales</p>
        <?php if (!array_filter(array_column($serie, 'consumo'), function ($valor) { return (float) $valor > 0; })): ?><p class="nota">Todavía no hay ventas completadas en estos siete días.</p><?php endif; ?>
        <details><summary>Ver importes por día</summary><div class="tabla"><table><thead><tr><th>Fecha</th><th class="tabla-numero">Ventas sin propina</th></tr></thead><tbody>
        <?php foreach ($serie as $dia): ?><tr><td><?= html_escape($dia['dia']) ?></td><td class="tabla-numero"><?= negocio_moneda($dia['consumo']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></details>
    </section>
    <section class="recuadro" aria-labelledby="titulo-pendientes"><div class="panel-encabezado"><div><h2 id="titulo-pendientes">Para revisar hoy</h2><p>Un vistazo a tu operación actual.</p></div></div>
        <ul class="pendientes-inicio">
            <?php if (isset($resumen['cocina'])): ?><li><a href="<?= html_escape(site_url('cocina')) ?>"><span>Pedidos en cocina<small>Pendientes, en preparación y listos</small></span><strong><?= $resumen['cocina'] ?></strong></a></li><?php endif; ?>
            <?php if (isset($resumen['por_cobrar'])): ?><li><a href="<?= html_escape(site_url('pagos')) ?>"><span>Pedidos por cobrar<small>Con saldo pendiente o parcial</small></span><strong><?= $resumen['por_cobrar'] ?></strong></a></li><?php endif; ?>
            <?php if (isset($resumen['reponer'])): ?><li><a href="<?= html_escape(site_url('inventario')) ?>"><span>Insumos por reponer<small>En el mínimo o sin existencias</small></span><strong><?= $resumen['reponer'] ?></strong></a></li><?php endif; ?>
            <?php if (!isset($resumen['cocina']) && !isset($resumen['por_cobrar']) && !isset($resumen['reponer'])): ?><li><p class="nota">Consulta el historial para revisar tus ventas completadas.</p><a href="<?= html_escape(site_url('ventas')) ?>">Consultar ventas <span aria-hidden="true">↗</span></a></li><?php endif; ?>
        </ul>
    </section>
</div>
<?php endif; ?>
<h2>Accesos rápidos</h2>
<div class="accesos">
    <?php if ($usuario->puede('pedidos.actualizar_cocina')): ?>
        <a class="acceso" href="<?= html_escape(site_url('cocina')) ?>">
            <span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/correcto.svg')) ?>" alt="" width="22" height="22"></span>
            <h3>Cocina</h3><p>Consulta los pedidos confirmados y sus productos.</p>
            <span class="acceso-abrir">Abrir cocina <img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22"></span>
        </a>
    <?php endif; ?>
    <?php if ($usuario->puede('ventas.historial')): ?>
    <a class="acceso" href="<?= html_escape(site_url('ventas')) ?>"><span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/ventas.svg')) ?>" alt=""></span><h3>Ventas</h3><p>Consulta las ventas completadas y el resumen por fecha.</p><span class="acceso-abrir">Abrir ventas</span></a>
    <?php endif; ?>

    <?php if ($usuario->puede('compras.ver')): ?>
    <a class="acceso" href="<?= html_escape(site_url('compras')) ?>"><span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/compras.svg')) ?>" alt=""></span><h3>Compras</h3><p>Prepara, revisa y exporta solicitudes para proveedores.</p><span class="acceso-abrir">Abrir compras</span></a>
    <?php endif; ?>

    <?php if ($usuario->puede('ventas.ver') && $usuario->puede('pedidos.ver')): ?>
        <a class="acceso" href="<?= html_escape(site_url('pagos')) ?>">
            <span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/pagos.svg')) ?>" alt="" width="22" height="22"></span>
            <h3>Pagos</h3><p>Cobra pedidos, separa cuentas y consulta los saldos.</p>
            <span class="acceso-abrir">Abrir pagos <img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22"></span>
        </a>
    <?php endif; ?>
    <?php if ($usuario->puede('inventario.ver')): ?>
        <a class="acceso" href="<?= html_escape(site_url('inventario')) ?>">
            <span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/inventario.svg')) ?>" alt="" width="22" height="22"></span>
            <h3>Inventario</h3><p>Revisa insumos, cantidades y stock mínimo.</p>
            <span class="acceso-abrir">Abrir inventario <img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22"></span>
        </a>
    <?php endif; ?>
    <?php if ($usuario->puede('pedidos.ver')): ?>
        <a class="acceso" href="<?= html_escape(site_url('pedidos')) ?>">
            <span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/pedidos.svg')) ?>" alt="" width="22" height="22"></span>
            <h3>Pedidos</h3><p>Consulta los pedidos y sus productos.</p>
            <span class="acceso-abrir">Abrir pedidos <img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22"></span>
        </a>
    <?php endif; ?>
    <?php if ($usuario->puede('productos.ver')): ?>
        <a class="acceso" href="<?= html_escape(site_url('productos')) ?>">
            <span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/productos.svg')) ?>" alt="" width="22" height="22"></span>
            <h3>Productos</h3><p>Consulta el catálogo y los precios de tu empresa.</p>
            <span class="acceso-abrir">Ver productos <img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22"></span>
        </a>
    <?php endif; ?>
    <?php if ($usuario->puede('usuarios.ver')): ?>
        <a class="acceso" href="<?= html_escape(site_url('usuarios')) ?>">
            <span class="acceso-icono"><img class="icono" src="<?= html_escape(base_url('assets/iconos/usuario.svg')) ?>" alt="" width="22" height="22"></span>
            <h3>Usuarios</h3><p>Administra las cuentas y roles de tu empresa.</p>
            <span class="acceso-abrir">Ver usuarios <img class="icono" src="<?= html_escape(base_url('assets/iconos/flecha.svg')) ?>" alt="" width="22" height="22"></span>
        </a>
    <?php endif; ?>
</div>
<?php if (!$usuario->puede('inventario.ver') && !$usuario->puede('pedidos.ver') && !$usuario->puede('productos.ver') && !$usuario->puede('usuarios.ver')): ?>
    <p class="recuadro aviso">Tu cuenta todavía no tiene módulos asignados. Consulta al administrador de tu empresa.</p>
<?php endif; ?>
<?php $this->load->view('plantilla/pie'); ?>
