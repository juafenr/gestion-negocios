<?php
$this->load->view('plantilla/cabecera', array(
    'titulo' => 'Historial de pedidos',
    'seccion' => 'pedidos',
    'usuario' => $usuario
));

// Construir enlaces que conservan los filtros al cambiar de página.
$url_pagina = function ($numero) use ($filtros) {
    $parametros = $filtros;
    $parametros['pagina'] = (int) $numero;

    return site_url('pedidos') . '?' . http_build_query($parametros);
};

$hay_filtros = FALSE;

foreach ($filtros as $valor) {
    if ($valor !== '') {
        $hay_filtros = TRUE;
        break;
    }
}
?>

<div class="titulo-acciones">
    <div>
        <p class="sobretitulo">REGISTRO DE PEDIDOS</p>
        <h1>Pedidos</h1>
        <p class="descripcion">
            Consulta el historial por referencia, número,
            estado y fecha de creación.
        </p>
    </div>

    <?php if ($usuario->puede('pedidos.crear')): ?>
        <?php if ($usuario->puede('pedidos.confirmar') && $usuario->puede('ventas.cobrar')): ?><p><a class="boton" href="<?= html_escape(site_url('pedidos/rapido')) ?>">Pedido rápido</a></p><?php endif; ?>
        <p>
            <a
                class="boton"
                href="<?= html_escape(site_url('pedidos/crear')) ?>">
                <img
                    class="icono"
                    src="<?= html_escape(
                        base_url('assets/iconos/agregar.svg')
                    ) ?>"
                    alt=""
                    width="22"
                    height="22">
                <span>Nuevo pedido</span>
            </a>
        </p>
    <?php endif; ?>
</div>

<?php if (!empty($mensaje)): ?>
    <p role="status">
        <?= html_escape($mensaje) ?>
    </p>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="error" role="alert">
        <?= html_escape($error) ?>
    </div>
<?php endif; ?>

<?php $this->load->view('plantilla/filtros', array('filtro_ruta'=>'pedidos', 'filtro_campos'=>array(
'busqueda'=>array('tipo'=>'search','etiqueta'=>'Referencia o número','ejemplo'=>'Ej. Mesa 4 o 4'),
'estado'=>array('etiqueta'=>'Estado del pedido','opciones'=>array(''=>'Todos los estados')+$estados),
'estado_pago'=>array('etiqueta'=>'Estado de pago','opciones'=>array(''=>'Todos los estados de pago')+$estados_pago),
'desde'=>array('tipo'=>'date','etiqueta'=>'Creado desde'),'hasta'=>array('tipo'=>'date','etiqueta'=>'Creado hasta')
), 'filtro_valores'=>$filtros, 'filtro_extras'=>array())); ?>

<?php $this->load->view('plantilla/resultados', array('resultado_total'=>$total, 'resultado_mostrados'=>count($pedidos), 'resultado_pagina'=>$pagina)); ?>


<div
    class="tabla"
    role="region"
    aria-label="Historial de pedidos"
    tabindex="0">

    <table>
        <thead>
            <tr>
                <th scope="col">Número</th>
                <th scope="col">Referencia</th>
                <th scope="col">Fecha de creación</th>
                <th scope="col">Estado</th>
                <th scope="col">Pago</th>
                <th class="tabla-numero" scope="col">Consumo</th>
                <th scope="col">Detalle</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($pedidos as $pedido): ?>
                <tr>
                    <td>
                        #<?= (int) $pedido['numero'] ?>
                    </td>

                    <td>
                        <?= html_escape($pedido['referencia']) ?>
                    </td>

                    <td>
                        <?= html_escape($pedido['creado_en']) ?>
                    </td>

                    <td>
                        <?= html_escape(
                            $estados[$pedido['estado']]
                            ?? $pedido['estado']
                        ) ?>
                    </td>

                    <td>
                        <?= html_escape(
                            $estados_pago[$pedido['estado_pago']]
                            ?? $pedido['estado_pago']
                        ) ?>
                    </td>

                    <td class="tabla-numero">
                        <?= html_escape(
                            Dinero::mostrar(
                                Dinero::centavos($pedido['total'])
                            )
                        ) ?>
                    </td>

                    <td>
                        <a
                            class="boton boton-secundario boton-compacto"
                            href="<?= html_escape(
                                site_url(
                                    'pedidos/' . (int) $pedido['id']
                                )
                            ) ?>">
                            <img
                                class="icono"
                                src="<?= html_escape(
                                    base_url('assets/iconos/ver.svg')
                                ) ?>"
                                alt=""
                                width="22"
                                height="22">
                            <span>Ver detalle</span>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($pedidos)): ?>
                <tr>
                    <td colspan="7"><?php $this->load->view('plantilla/vacio',array('vacio_titulo'=>$hay_filtros?'No se encontraron pedidos con esos filtros.':'Todavía no hay pedidos registrados.','vacio_descripcion'=>'Revisa la referencia, las fechas y los estados seleccionados.','vacio_url'=>$hay_filtros?site_url('pedidos'):($usuario->puede('pedidos.crear')?site_url('pedidos/crear'):NULL),'vacio_accion'=>$hay_filtros?'Limpiar filtros':'Nuevo pedido')); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $this->load->view('plantilla/paginacion', array('ruta_paginacion'=>'pedidos','parametros_paginacion'=>$filtros,'pagina_actual'=>$pagina,'cantidad_paginas'=>$paginas,'hay_siguiente'=>FALSE)); ?>

<?php $this->load->view('plantilla/pie'); ?>
