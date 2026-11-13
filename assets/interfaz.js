/* Navegación móvil accesible. Las operaciones y validaciones siguen en el servidor. */
(function () {
    'use strict';
    const pantallaPequena = window.matchMedia('(max-width: 640px)');
    document.querySelectorAll('.filtros-adicionales').forEach(detalle => {
        let abiertoEnMovil = Number(detalle.dataset.filtrosActivos) > 0;
        function ajustarFiltros() { detalle.open = !pantallaPequena.matches || abiertoEnMovil; }
        detalle.addEventListener('toggle', () => {
            if (pantallaPequena.matches) abiertoEnMovil = detalle.open;
        });
        pantallaPequena.addEventListener('change', ajustarFiltros);
        ajustarFiltros();
    });
    // Las tablas largas y anchas se pueden recorrer también con teclado.
    function prepararTablas() {
        document.querySelectorAll('.tabla').forEach(tabla => {
            const desborda = tabla.scrollWidth > tabla.clientWidth + 1;
            const vertical = tabla.scrollHeight > tabla.clientHeight + 1;
            const desplazable = desborda || vertical;
            tabla.classList.toggle('tabla-desbordable', desborda);
            if (desplazable && !tabla.hasAttribute('tabindex')) {
                tabla.setAttribute('tabindex', '0');
                tabla.dataset.focoTabla = 'true';
            } else if (!desplazable && tabla.dataset.focoTabla === 'true') {
                tabla.removeAttribute('tabindex');
                delete tabla.dataset.focoTabla;
            }
            if (desplazable && !tabla.hasAttribute('role')) {
                tabla.setAttribute('role', 'region');
                tabla.dataset.regionTabla = 'true';
            } else if (!desplazable && tabla.dataset.regionTabla === 'true') {
                tabla.removeAttribute('role');
                delete tabla.dataset.regionTabla;
            }
            if (desplazable && (!tabla.hasAttribute('aria-label') || tabla.dataset.etiquetaTabla === 'true')) {
                const direccion = desborda && vertical ? 'horizontal y vertical' : desborda ? 'horizontal' : 'vertical';
                tabla.setAttribute('aria-label', 'Tabla con desplazamiento ' + direccion);
                tabla.dataset.etiquetaTabla = 'true';
            } else if (!desplazable && tabla.dataset.etiquetaTabla === 'true') {
                tabla.removeAttribute('aria-label');
                delete tabla.dataset.etiquetaTabla;
            }
        });
    }
    prepararTablas();
    window.addEventListener('resize', prepararTablas);
    if ('ResizeObserver' in window) {
        const observarTablas = new ResizeObserver(prepararTablas);
        document.querySelectorAll('.tabla, .tabla table').forEach(tabla => observarTablas.observe(tabla));
    }
    document.querySelectorAll('details').forEach(detalle => detalle.addEventListener('toggle', prepararTablas));
    const menu = document.getElementById('menu-principal');
    const abrir = document.getElementById('abrir-menu');
    const velo = document.getElementById('velo-menu');
    if (!menu || !abrir || !velo) return;
    const contenido = document.getElementById('contenido');
    const pie = document.querySelector('body > footer');
    const movil = window.matchMedia('(max-width: 960px)');
    document.documentElement.classList.add('con-js');
    abrir.hidden = false;
    function cambiar(abierto, devolverFoco) {
        abierto = abierto && movil.matches;
        document.body.classList.toggle('menu-abierto', abierto);
        abrir.setAttribute('aria-expanded', String(abierto));
        abrir.setAttribute('aria-label', abierto ? 'Cerrar menú principal' : 'Abrir menú principal');
        velo.hidden = !abierto;
        menu.inert = movil.matches && !abierto;
        if (contenido) contenido.inert = abierto;
        if (pie) pie.inert = abierto;
        if (abierto) (menu.querySelector('.seleccionado') || menu.querySelector('a')).focus();
        else if (devolverFoco) abrir.focus();
    }
    abrir.addEventListener('click', () => cambiar(abrir.getAttribute('aria-expanded') !== 'true', true));
    velo.addEventListener('click', () => cambiar(false, true));
    document.addEventListener('keydown', evento => {
        if (evento.key === 'Escape' && abrir.getAttribute('aria-expanded') === 'true') cambiar(false, true);
        if (evento.key === 'Tab' && abrir.getAttribute('aria-expanded') === 'true') {
            const controles = [abrir, ...menu.querySelectorAll('a, button:not([disabled])')];
            const primero = controles[0], ultimo = controles[controles.length - 1];
            if (evento.shiftKey && document.activeElement === primero) {
                evento.preventDefault(); ultimo.focus();
            } else if (!evento.shiftKey && document.activeElement === ultimo) {
                evento.preventDefault(); primero.focus();
            }
        }
    });
    menu.addEventListener('click', evento => {
        if (evento.target.closest('a')) cambiar(false, false);
    });
    movil.addEventListener('change', () => cambiar(false, false));
    window.addEventListener('pageshow', () => cambiar(false, false));
    cambiar(false, false);
}());
