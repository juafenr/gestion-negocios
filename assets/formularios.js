/* Ayuda progresiva: las validaciones y permisos del servidor siguen siendo obligatorios. */
(function () {
    'use strict';
    let secuencia = 0;
    document.querySelectorAll('form[data-ui-form], main form[method="post"]').forEach(form => {
        const errores = new Map();
        let pendiente = false;
        let resumen;
        function mensaje(campo) {
            const v = campo.validity;
            if (v.customError) return campo.validationMessage;
            if (v.valueMissing) return 'Completa este campo.';
            if (v.typeMismatch && campo.type === 'email') return 'Introduce un correo electrónico válido.';
            if (v.rangeUnderflow) return 'Usa una cantidad igual o mayor que ' + campo.min + '.';
            if (v.rangeOverflow) return 'Usa una cantidad igual o menor que ' + campo.max + '.';
            if (v.stepMismatch) return 'Revisa la cantidad y los decimales permitidos.';
            if (v.tooShort) return 'Usa al menos ' + campo.minLength + ' caracteres.';
            if (v.tooLong) return 'Usa como máximo ' + campo.maxLength + ' caracteres.';
            if (v.patternMismatch) return 'Revisa el formato indicado en la ayuda de este campo.';
            return 'Revisa el valor de este campo.';
        }
        function actualizarResumen() {
            if (!resumen) return;
            resumen.hidden = errores.size === 0;
            resumen.textContent = errores.size === 1 ? 'Revisa el campo señalado para continuar.' : 'Revisa los ' + errores.size + ' campos señalados para continuar.';
        }
        function marcar(campo) {
            let aviso = errores.get(campo);
            if (!aviso) {
                aviso = document.createElement('span');
                aviso.id = 'error-formulario-' + (++secuencia);
                aviso.className = 'error-campo';
                const etiqueta = ['checkbox','radio'].includes(campo.type) ? campo.closest('label') : null;
                (campo.closest('.campo-password') || etiqueta || campo).insertAdjacentElement('afterend', aviso);
                errores.set(campo, aviso);
                const ids = (campo.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
                campo.setAttribute('aria-describedby', [...ids, aviso.id].join(' '));
            }
            campo.setAttribute('aria-invalid', 'true');
            aviso.textContent = mensaje(campo);
            if (!resumen) {
                resumen = document.createElement('div');
                resumen.className = 'error resumen-errores';
                resumen.setAttribute('role', 'alert');
                form.prepend(resumen);
            }
            actualizarResumen();
        }
        function limpiar(campo) {
            const aviso = errores.get(campo);
            if (!aviso) return;
            const ids = (campo.getAttribute('aria-describedby') || '').split(/\s+/).filter(id => id && id !== aviso.id);
            if (ids.length) campo.setAttribute('aria-describedby', ids.join(' '));
            else campo.removeAttribute('aria-describedby');
            aviso.remove(); errores.delete(campo); campo.removeAttribute('aria-invalid'); actualizarResumen();
        }
        const desde = form.querySelector('[name="desde"]');
        const hasta = form.querySelector('[name="hasta"]');
        function revisarFechas() {
            if (desde && hasta) hasta.setCustomValidity(desde.value && hasta.value && desde.value > hasta.value ? 'La fecha final debe ser igual o posterior a la inicial.' : '');
        }
        revisarFechas();
        form.addEventListener('invalid', evento => {
            evento.preventDefault(); marcar(evento.target);
            if (!pendiente) {
                pendiente = true;
                requestAnimationFrame(() => {
                    pendiente = false;
                    const primero = [...form.elements].find(campo => campo.willValidate && !campo.validity.valid);
                    if (primero) primero.focus();
                });
            }
        }, true);
        function corregir(evento) {
            revisarFechas();
            if (errores.has(evento.target)) evento.target.validity.valid ? limpiar(evento.target) : marcar(evento.target);
            if (hasta && errores.has(hasta) && hasta.validity.valid) limpiar(hasta);
        }
        form.addEventListener('input', corregir);
        form.addEventListener('change', corregir);
        new MutationObserver(() => {
            let eliminados = false;
            errores.forEach((aviso, campo) => {
                if (!form.contains(campo)) { aviso.remove(); errores.delete(campo); eliminados = true; }
            });
            if (eliminados) actualizarResumen();
        }).observe(form, {childList:true, subtree:true});
    });
    // Los errores del servidor se muestran en el formulario y reciben el foco al volver.
    const errorServidor = document.querySelector('main .error[role="alert"]');
    if (errorServidor && errorServidor.textContent.trim()) {
        errorServidor.setAttribute('tabindex', '-1');
        errorServidor.focus();
    }
}());
