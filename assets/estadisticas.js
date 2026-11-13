/* Interacción progresiva; las cifras y las tablas ya vienen renderizadas por el servidor. */
(function () {
    'use strict';
    document.querySelectorAll('.estadisticas .est-lienzo').forEach(function (lienzo) {
        const puntos = Array.from(lienzo.querySelectorAll('[data-est-dato]'));
        const lectura = lienzo.querySelector('[data-est-lectura]');
        if (!lectura || !puntos.length) return;
        function mostrar(punto) {
            puntos.forEach(function (otro) {
                otro.tabIndex = otro === punto ? 0 : -1;
                otro.toggleAttribute('data-activo', otro === punto);
            });
            lectura.textContent = punto.dataset.estDato;
        }
        puntos.forEach(function (punto, indice) {
            ['pointerenter', 'focus', 'click'].forEach(function (evento) {
                punto.addEventListener(evento, function () { mostrar(punto); });
            });
            punto.addEventListener('keydown', function (evento) {
                let siguiente;
                if (evento.key === 'ArrowRight') siguiente = Math.min(indice + 1, puntos.length - 1);
                if (evento.key === 'ArrowLeft') siguiente = Math.max(indice - 1, 0);
                if (evento.key === 'Home') siguiente = 0;
                if (evento.key === 'End') siguiente = puntos.length - 1;
                if (siguiente === undefined) return;
                evento.preventDefault();
                puntos[siguiente].focus();
            });
        });
        mostrar(puntos[puntos.length - 1]);
    });
}());
