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
<details class="actividad-secundaria"><summary>Ver actividad de ventas de los últimos 7 días</summary>
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
</details>
<?php endif; ?>
