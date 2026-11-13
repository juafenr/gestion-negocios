<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$hay_graficos = isset($panel['ventas']) || isset($panel['cobros']) || isset($panel['inventario']) || isset($panel['pedidos']) || isset($panel['despachos']);
?>
<?php if ($hay_graficos): ?>
<section class="estadisticas" aria-labelledby="titulo-estadisticas">
    <div class="est-encabezado">
        <div><p class="sobretitulo">DECISIONES CON DATOS</p><h2 id="titulo-estadisticas">Así va tu negocio</h2><p class="nota">Descubre qué se vende, cómo te pagan y cómo avanza tu equipo.</p></div>
        <?php if (isset($panel['ventas']) || isset($panel['cobros']) || isset($panel['despachos'])): ?>
        <nav class="est-periodos" aria-label="Período de ventas, cobros y despachos">
            <?php foreach (array(7,30,90) as $periodo): ?>
            <a href="<?= html_escape(site_url('inicio').'?dias='.$periodo.'#titulo-estadisticas') ?>" <?= $panel['dias']===$periodo ? 'aria-current="true"' : '' ?>><?= $periodo ?> días</a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
    </div>
    <?php if (isset($panel['ventas']) || isset($panel['cobros']) || isset($panel['despachos'])): ?>
    <p class="est-fechas"><?= html_escape($panel['desde'].' — '.$panel['hasta']) ?> · Incluye hoy, todavía en curso.</p>
    <?php endif; ?>
    <div class="est-grid">
    <?php if (isset($panel['ventas'])):
        $serie = $panel['serie'];
        $maximo = max(1, max(array_map(function ($dia) { return (float) $dia['consumo']; }, $serie)));
        $puntos = array();
        foreach ($serie as $i=>$dia) {
            $puntos[] = array(72 + $i * 590 / (count($serie)-1), 214 - (float) $dia['consumo'] / $maximo * 172);
        }
        $trazo = implode(' ', array_map(function ($p) { return estadistica_numero($p[0]).','.estadistica_numero($p[1]); }, $puntos));
    ?>
        <article class="est-tarjeta est-ventas" aria-labelledby="est-titulo-ventas">
            <div class="est-titulo"><div><h3 id="est-titulo-ventas">El ritmo de tus ventas</h3><p>Pedidos completamente pagados · sin propinas</p></div><span class="est-insignia">QUETZALES</span></div>
            <div class="est-metricas">
                <div><span>Vendido en el período</span><strong><?= negocio_moneda($panel['ventas']['consumo']) ?></strong></div>
                <div><span>Pedidos pagados</span><strong><?= number_format((int) $panel['ventas']['cantidad']) ?></strong></div>
                <div><span>Promedio por pedido</span><strong><?= negocio_moneda($panel['ventas']['promedio']) ?></strong></div>
            </div>
            <div class="est-lienzo">
                <svg class="est-linea" viewBox="0 0 700 254" role="group" aria-labelledby="est-svg-ventas" aria-describedby="est-ayuda-ventas">
                    <title id="est-svg-ventas">Ventas diarias, últimos <?= (int) $panel['dias'] ?> días</title>
                    <defs><linearGradient id="est-relleno" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#ce530c" stop-opacity=".22"/><stop offset="100%" stop-color="#ce530c" stop-opacity=".01"/></linearGradient></defs>
                    <?php foreach (array(0,1,2,3,4) as $paso): $y=214-$paso*43; ?>
                    <line class="est-rejilla" x1="72" x2="662" y1="<?= $y ?>" y2="<?= $y ?>"/>
                    <text x="61" y="<?= $y+4 ?>" text-anchor="end"><?= html_escape(estadistica_escala($maximo*$paso/4)) ?></text>
                    <?php endforeach; ?>
                    <polygon points="72,214 <?= $trazo ?> 662,214" fill="url(#est-relleno)"/>
                    <polyline class="est-trazo" points="<?= $trazo ?>"/>
                    <?php foreach ($serie as $i=>$dia): $texto=estadistica_fecha($dia['dia']).' · '.negocio_moneda($dia['consumo']).' · '.(int) $dia['cantidad'].' pedidos'; ?>
                    <circle class="est-punto" cx="<?= estadistica_numero($puntos[$i][0]) ?>" cy="<?= estadistica_numero($puntos[$i][1]) ?>" r="<?= count($serie)>30 ? 3 : 4 ?>" tabindex="<?= $i===count($serie)-1 ? 0 : -1 ?>" role="img" aria-label="<?= html_escape($texto) ?>" data-est-dato="<?= html_escape($texto) ?>"><title><?= html_escape($texto) ?></title></circle>
                    <?php if ($i===0 || $i===count($serie)-1 || $i % (int) ceil((count($serie)-1)/6)===0): ?>
                    <text x="<?= estadistica_numero($puntos[$i][0]) ?>" y="242" text-anchor="middle"><?= html_escape(estadistica_fecha($dia['dia'])) ?></text>
                    <?php endif; endforeach; ?>
                </svg>
                <p class="est-dato" data-est-lectura aria-live="polite">Selecciona un punto para consultar el importe del día.</p>
            </div>
            <p id="est-ayuda-ventas" class="nota">Toca un punto o usa las flechas del teclado sobre la gráfica. Propinas del período: <?= negocio_moneda($panel['ventas']['propina']) ?>.</p>
            <?php if ((int) $panel['ventas']['cantidad']===0): ?><p class="est-vacio">Aún no hay ventas completadas en este período. El gráfico se actualizará al completar el cobro de un pedido.</p><?php endif; ?>
            <details class="est-detalles"><summary>Ver datos por día</summary><div class="tabla"><table><caption>Ventas completadas por día, sin propinas</caption><thead><tr><th scope="col">Fecha</th><th scope="col">Pedidos</th><th scope="col">Ventas</th></tr></thead><tbody>
            <?php foreach ($serie as $dia): ?><tr><td><?= html_escape($dia['dia']) ?></td><td><?= (int) $dia['cantidad'] ?></td><td><?= negocio_moneda($dia['consumo']) ?></td></tr><?php endforeach; ?>
            </tbody></table></div></details>
        </article>
        <article class="est-tarjeta est-productos" aria-labelledby="est-titulo-productos">
            <div class="est-titulo"><div><h3 id="est-titulo-productos">Productos más vendidos</h3><p>Productos o platillos · más unidades vendidas</p></div><span class="est-insignia">TOP 5</span></div>
            <?php if (!$panel['productos']): ?>
                <div class="est-vacio est-vacio-amplio"><img src="<?= html_escape(base_url('assets/iconos/productos.svg')) ?>" width="32" height="32" alt=""><strong>Tu próximo favorito está por descubrirse</strong><p>Aquí aparecerán los productos de los pedidos completamente pagados.</p></div>
            <?php else: $max_productos=max(1, (float) $panel['productos'][0]['cantidad']); ?>
                <ol class="est-ranking">
                <?php foreach ($panel['productos'] as $i=>$producto): ?>
                    <li><span class="est-puesto"><?= $i+1 ?></span><div><div class="est-fila"><span><?= html_escape($producto['nombre']) ?></span><strong><?= number_format((int) $producto['cantidad']) ?> <small>ud.</small></strong></div><svg class="est-barra" viewBox="0 0 300 8" preserveAspectRatio="none" aria-hidden="true"><rect width="300" height="8" rx="4" class="est-pista"/><rect width="<?= estadistica_numero((float) $producto['cantidad']/$max_productos*300) ?>" height="8" rx="4" class="est-color-acento"/></svg></div></li>
                <?php endforeach; ?>
                </ol>
            <?php endif; ?>
            <p class="nota est-pie">Mismo período que las ventas. Ordenados por cantidad, no por precio.</p>
        </article>
    <?php endif; ?>
    <?php if (isset($panel['menos_vendidos'])): ?>
        <article class="est-tarjeta" aria-labelledby="est-titulo-menos">
            <div class="est-titulo"><div><h3 id="est-titulo-menos">Productos menos vendidos</h3><p>Productos o platillos · incluye los que no se vendieron</p></div></div>
            <?php if (!$panel['menos_vendidos']): ?>
                <p class="est-vacio">Todavía no hay productos disponibles en tu catálogo.</p>
            <?php else: $max_menos=max(1,max(array_map(function ($p) { return (float)$p['cantidad']; },$panel['menos_vendidos']))); ?>
                <ol class="est-ranking">
                <?php foreach ($panel['menos_vendidos'] as $i=>$producto): ?>
                    <li><span class="est-puesto"><?= $i+1 ?></span><div><div class="est-fila"><span><?= html_escape($producto['nombre']) ?></span><strong><?= number_format((int)$producto['cantidad']) ?> <small>ud.</small></strong></div><svg class="est-barra" viewBox="0 0 300 8" preserveAspectRatio="none" aria-hidden="true"><rect width="300" height="8" rx="4" class="est-pista"/><rect width="<?= estadistica_numero((float)$producto['cantidad']/$max_menos*300) ?>" height="8" rx="4" class="est-color-ambar"/></svg><?php if ((int)$producto['cantidad']===0): ?><small>Sin ventas en este período</small><?php endif; ?></div></li>
                <?php endforeach; ?>
                </ol>
                <p class="est-consejo">Una oportunidad para revisar la oferta, la presentación o las promociones.</p>
            <?php endif; ?>
            <p class="nota est-pie">Los 5 con menos unidades en ventas completadas. Considera el catálogo disponible hoy, aunque no haya tenido ventas en el período.</p>
        </article>
    <?php endif; ?>
    <?php if (isset($panel['despachos'])): ?>
        <article class="est-tarjeta" aria-labelledby="est-titulo-despachos">
            <div class="est-titulo"><div><h3 id="est-titulo-despachos">Quién despacha más</h3><p>Usuarios con más pedidos entregados</p></div></div>
            <?php if (!$panel['despachos']): ?>
                <p class="est-vacio">No hay entregas registradas en este período. Aparecerán cuando un usuario cambie un pedido de listo a entregado.</p>
            <?php else: $max_despachos=max(1,(int)$panel['despachos'][0]['cantidad']); ?>
                <ol class="est-ranking">
                <?php foreach ($panel['despachos'] as $i=>$despacho): ?>
                    <li><span class="est-puesto"><?= $i+1 ?></span><div><div class="est-fila"><span><?= html_escape($despacho['nombre']) ?></span><strong><?= number_format((int)$despacho['cantidad']) ?> <small>pedidos</small></strong></div><svg class="est-barra" viewBox="0 0 300 8" preserveAspectRatio="none" aria-hidden="true"><rect width="300" height="8" rx="4" class="est-pista"/><rect width="<?= estadistica_numero((int)$despacho['cantidad']/$max_despachos*300) ?>" height="8" rx="4" class="est-color-azul"/></svg></div></li>
                <?php endforeach; ?>
                </ol>
            <?php endif; ?>
            <p class="nota est-pie">Top 5 por entregas registradas en el período, estén pagadas o no. Cuenta a quien marcó el pedido como entregado; no a quien lo creó o cobró. Cada pedido cuenta una sola vez.</p>
        </article>
    <?php endif; ?>
    <?php if (isset($panel['cobros'])):
        $cobros=$panel['cobros']; $total=(float) $cobros['total'];
        $metodos=array('efectivo'=>'Efectivo', 'credito'=>'Tarjeta de crédito', 'debito'=>'Tarjeta de débito');
        $colores=array('efectivo'=>'acento', 'credito'=>'verde', 'debito'=>'azul');
        $desplazamiento=0;
    ?>
        <article class="est-tarjeta" aria-labelledby="est-titulo-cobros">
            <div class="est-titulo"><div><h3 id="est-titulo-cobros">¿Cómo te pagan?</h3><p>Distribución de los cobros del período</p></div></div>
            <div class="est-dona-contenedor"><svg class="est-dona" viewBox="0 0 200 200" role="img" aria-label="Distribución de cobros por método; importes en la lista inferior">
                <circle cx="100" cy="100" r="76" class="est-anillo-base"/>
                <?php if ($total>0): foreach ($metodos as $clave=>$nombre): $porcentaje=(float) $cobros[$clave]/$total*100; if ($porcentaje>0): ?>
                <circle cx="100" cy="100" r="76" pathLength="100" class="est-anillo est-trazo-<?= $colores[$clave] ?>" stroke-dasharray="<?= estadistica_numero($porcentaje) ?> <?= estadistica_numero(100-$porcentaje) ?>" stroke-dashoffset="<?= estadistica_numero(-$desplazamiento) ?>"><title><?= html_escape($nombre.': '.negocio_moneda($cobros[$clave])) ?></title></circle>
                <?php endif; $desplazamiento+=$porcentaje; endforeach; endif; ?>
                <text x="100" y="97" text-anchor="middle" class="est-dona-numero"><?= $total>0 ? count(array_filter(array_keys($metodos), function ($k) use ($cobros) { return (float) $cobros[$k]>0; })) : 0 ?></text><text x="100" y="120" text-anchor="middle">métodos usados</text>
            </svg><div class="est-total"><span>Total cobrado</span><strong><?= negocio_moneda($cobros['total']) ?></strong></div></div>
            <ul class="est-leyenda">
            <?php foreach ($metodos as $clave=>$nombre): ?><li><span><i class="est-color-<?= $colores[$clave] ?>" aria-hidden="true"></i><?= $nombre ?></span><strong><?= negocio_moneda($cobros[$clave]) ?> <small><?= $total>0 ? number_format((float) $cobros[$clave]/$total*100,1).'%' : '—' ?></small></strong></li><?php endforeach; ?>
            </ul>
            <?php if ($total<=0): ?><p class="est-vacio">Todavía no hay cobros registrados en este período.</p><?php endif; ?>
            <p class="nota est-pie">Incluye abonos y propinas. El efectivo es el aplicado al pago; no incluye el cambio entregado.</p>
        </article>
    <?php endif; ?>
    <?php if (isset($panel['inventario'])):
        $inventario=$panel['inventario']; $max_insumos=max(1,(int)$inventario['total']);
        $estados=array('normal'=>array('Stock suficiente','verde'), 'bajo'=>array('Por reponer','ambar'), 'agotado'=>array('Sin existencias','rojo'));
    ?>
        <article class="est-tarjeta est-operacion" aria-labelledby="est-titulo-stock">
            <div class="est-titulo"><div><h3 id="est-titulo-stock">La salud de tu inventario</h3><p>Insumos activos, agrupados por existencias</p></div><span class="est-insignia est-ahora">AHORA</span></div>
            <div class="est-total est-total-stock"><strong><?= (int) $inventario['total'] ?></strong><span>insumos activos</span></div>
            <div class="est-barras">
            <?php foreach ($estados as $clave=>$estado): ?>
                <div><div class="est-fila"><span><?= $estado[0] ?></span><strong><?= (int) $inventario[$clave] ?></strong></div><svg class="est-barra est-barra-gruesa" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true"><rect width="300" height="12" rx="6" class="est-pista"/><rect width="<?= estadistica_numero((int) $inventario[$clave]/$max_insumos*300) ?>" height="12" rx="6" class="est-color-<?= $estado[1] ?>"/></svg></div>
            <?php endforeach; ?>
            </div>
            <?php if ((int) $inventario['total']===0): ?><p class="est-vacio">No tienes insumos activos todavía.</p><?php elseif ((int)$inventario['bajo']+(int)$inventario['agotado']>0): ?><p class="est-consejo"><?= (int)$inventario['bajo']+(int)$inventario['agotado'] ?> insumos necesitan atención. Revisa las existencias antes de tu próxima compra.</p><?php else: ?><p class="est-consejo est-consejo-bien">Todos los insumos activos están por encima del mínimo.</p><?php endif; ?>
            <p class="nota est-pie">Estado actual, independiente del período. “Por reponer” significa que aún hay existencias, pero están en el mínimo o por debajo.</p>
            <a class="est-enlace" href="<?= html_escape(site_url('inventario')) ?>">Revisar inventario <span aria-hidden="true">→</span></a>
        </article>
    <?php endif; ?>
    <?php if (isset($panel['pedidos'])): $max_pedidos=max(1,max(array_column($panel['pedidos'],'cantidad'))); ?>
        <article class="est-tarjeta est-operacion" aria-labelledby="est-titulo-pedidos">
            <div class="est-titulo"><div><h3 id="est-titulo-pedidos">Pedidos en marcha</h3><p>La carga de trabajo en este momento</p></div><span class="est-insignia est-ahora">AHORA</span></div>
            <div class="est-barras">
            <?php foreach ($panel['pedidos'] as $i=>$estado): ?>
                <div><div class="est-fila"><span><?= html_escape($estado['nombre']) ?></span><strong><?= (int) $estado['cantidad'] ?></strong></div><svg class="est-barra est-barra-gruesa" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true"><rect width="300" height="12" rx="6" class="est-pista"/><rect width="<?= estadistica_numero($estado['cantidad']/$max_pedidos*300) ?>" height="12" rx="6" class="est-color-<?= array('ambar','azul','verde')[$i] ?>"/></svg></div>
            <?php endforeach; ?>
            </div>
            <?php if (array_sum(array_column($panel['pedidos'],'cantidad'))===0): ?><p class="est-vacio">Todo al día: no hay pedidos pendientes, en preparación ni listos por entregar.</p><?php endif; ?>
            <p class="nota est-pie">Estado actual, independiente del período. Excluye borradores, entregados y cancelados.</p>
            <a class="est-enlace" href="<?= html_escape(site_url('pedidos')) ?>">Consultar pedidos <span aria-hidden="true">→</span></a>
        </article>
    <?php endif; ?>
    </div>
</section>
<?php endif; ?>
