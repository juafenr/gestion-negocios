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

<section class="recuadro">
    <h2>Buscar pedidos</h2>

    <form
        action="<?= html_escape(site_url('pedidos')) ?>"
        method="get"
        class="formulario">

        <label for="busqueda">Referencia o número de pedido</label>

        <input
            type="search"
            id="busqueda"
            name="busqueda"
            maxlength="150"
            placeholder="Ejemplo: Mesa 4 o 4"
            value="<?= html_escape($filtros['busqueda']) ?>">

        <label for="estado">Estado del pedido</label>

        <select id="estado" name="estado">
            <option value="">Todos los estados</option>

            <?php foreach ($estados as $valor => $etiqueta): ?>
                <option
                    value="<?= html_escape($valor) ?>"
                    <?= $filtros['estado'] === $valor
                        ? 'selected'
                        : '' ?>>
                    <?= html_escape($etiqueta) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="estado_pago">Estado de pago</label>

        <select id="estado_pago" name="estado_pago">
            <option value="">Todos los estados de pago</option>

            <?php foreach ($estados_pago as $valor => $etiqueta): ?>
                <option
                    value="<?= html_escape($valor) ?>"
                    <?= $filtros['estado_pago'] === $valor
                        ? 'selected'
                        : '' ?>>
                    <?= html_escape($etiqueta) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="desde">Creado desde</label>

        <input
            type="date"
            id="desde"
            name="desde"
            min="1000-01-01"
            max="9999-12-31"
            value="<?= html_escape($filtros['desde']) ?>">

        <label for="hasta">Creado hasta</label>

        <input
            type="date"
            id="hasta"
            name="hasta"
            min="1000-01-01"
            max="9999-12-31"
            value="<?= html_escape($filtros['hasta']) ?>">

        <button type="submit">
            <img
                class="icono"
                src="<?= html_escape(
                    base_url('assets/iconos/buscar.svg')
                ) ?>"
                alt=""
                width="22"
                height="22">
            <span>Filtrar</span>
        </button>

        <a
            class="boton boton-secundario"
            href="<?= html_escape(site_url('pedidos')) ?>">
            Limpiar filtros
        </a>
    </form>
</section>

<p role="status">
    <strong><?= (int) $total ?></strong>
    <?= (int) $total === 1
        ? 'pedido encontrado.'
        : 'pedidos encontrados.' ?>

    Página <?= (int) $pagina ?> de <?= (int) $paginas ?>.
</p>

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
                <th scope="col">Consumo</th>
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

                    <td>
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
                    <td colspan="7">
                        <?= $hay_filtros
                            ? 'No se encontraron pedidos con esos filtros.'
                            : 'No hay pedidos registrados.' ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($paginas > 1): ?>
    <?php
    $primera_visible = max(1, $pagina - 2);
    $ultima_visible = min($paginas, $pagina + 2);
    ?>

    <nav
        class="paginacion-inventario"
        aria-label="Páginas del historial de pedidos">

        <?php if ($pagina > 1): ?>
            <a
                class="boton boton-secundario boton-compacto"
                href="<?= html_escape($url_pagina(1)) ?>">
                Primera
            </a>

            <a
                class="boton boton-secundario boton-compacto"
                href="<?= html_escape($url_pagina($pagina - 1)) ?>">
                Anterior
            </a>
        <?php endif; ?>

        <?php for ($i = $primera_visible; $i <= $ultima_visible; $i++): ?>
            <?php if ($i === (int) $pagina): ?>
                <span class="boton boton-compacto" aria-current="page">
                    <?= (int) $i ?>
                </span>
            <?php else: ?>
                <a
                    class="boton boton-secundario boton-compacto"
                    href="<?= html_escape($url_pagina($i)) ?>"
                    aria-label="Ir a la página <?= (int) $i ?>">
                    <?= (int) $i ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($pagina < $paginas): ?>
            <a
                class="boton boton-secundario boton-compacto"
                href="<?= html_escape($url_pagina($pagina + 1)) ?>">
                Siguiente
            </a>

            <a
                class="boton boton-secundario boton-compacto"
                href="<?= html_escape($url_pagina($paginas)) ?>">
                Última
            </a>
        <?php endif; ?>
    </nav>
<?php endif; ?>

<?php $this->load->view('plantilla/pie'); ?>