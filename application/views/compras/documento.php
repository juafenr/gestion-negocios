<?php $this->load->view('plantilla/cabecera',array('titulo'=>'Documento de compra','seccion'=>'compras','usuario'=>$usuario)); ?>
<p><a class="boton boton-secundario" href="<?= html_escape(site_url('compras/'.(int)$solicitud['id'])) ?>">Volver a la solicitud</a></p>
<section class="recuadro"><h1>Documento para el proveedor</h1>
<p>Puedes copiar el contenido o descargarlo como archivo de texto para enviarlo por el medio que prefieras.</p>
<label for="documento-compra">Solicitud #<?= (int)$solicitud['numero'] ?></label>
<textarea id="documento-compra" class="texto-compra documento-compra" rows="18" readonly><?= html_escape($texto) ?></textarea>
<div class="acciones-documento"><button type="button" id="copiar-documento" hidden>Copiar texto</button>
<a class="boton boton-secundario" href="<?= html_escape(site_url('compras/'.(int)$solicitud['id'].'/exportar')) ?>">Descargar TXT</a></div>
<p id="resultado-copia" role="status" aria-live="polite"></p></section>
<script src="<?= html_escape(base_url('assets/compras.js')) ?>" defer></script>
<?php $this->load->view('plantilla/pie'); ?>
