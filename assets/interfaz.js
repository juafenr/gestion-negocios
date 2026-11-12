/* Navegación móvil accesible. Las operaciones y validaciones siguen en el servidor. */
(function () {
    'use strict';
    // Las tablas anchas conservan sus columnas dentro de una región desplazable con teclado.
    function prepararTablas() {
        document.querySelectorAll('.tabla').forEach(tabla => {
            const desborda = tabla.scrollWidth > tabla.clientWidth + 1;
            tabla.classList.toggle('tabla-desbordable', desborda);
            if (desborda && !tabla.hasAttribute('tabindex')) {
                tabla.setAttribute('tabindex', '0');
                tabla.dataset.focoTabla = 'true';
            } else if (!desborda && tabla.dataset.focoTabla === 'true') {
                tabla.removeAttribute('tabindex');
                delete tabla.dataset.focoTabla;
            }
            if (desborda && !tabla.hasAttribute('role')) tabla.setAttribute('role', 'region');
            if (desborda && !tabla.hasAttribute('aria-label')) tabla.setAttribute('aria-label', 'Tabla con desplazamiento horizontal');
        });
    }
    prepararTablas();
    window.addEventListener('resize', prepararTablas);
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
