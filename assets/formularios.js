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
            const plegado = evento.target.closest('details');
            if (plegado) plegado.open = true;
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
    // Solo se comparan valores en memoria; no se almacenan borradores ni contraseñas.
    const originales = new Map();
    const recuperados = new Set();
    let salidaPermitida = false;
    function valores(form) {
        return JSON.stringify([...form.elements].filter(campo =>
            campo.name && campo.matches('input, select, textarea') && !campo.disabled && !campo.readOnly &&
            !['hidden','submit','button','reset'].includes(campo.type)
        ).map(campo => [campo.name, campo.type === 'file'
            ? [...campo.files].map(archivo => [archivo.name, archivo.size, archivo.lastModified])
            : ['checkbox','radio'].includes(campo.type) ? [campo.checked, campo.value]
            : campo.multiple ? [...campo.selectedOptions].map(opcion => opcion.value) : campo.value]));
    }
    document.querySelectorAll('main form').forEach(form => {
        if (form.method.toLowerCase() !== 'post' || valores(form) === '[]') return;
        originales.set(form, valores(form));
        if (form.matches('[data-ui-form]') && document.querySelector('main .error[role="alert"]')) recuperados.add(form);
        form.addEventListener('reset', () => setTimeout(() => {
            originales.set(form, valores(form)); recuperados.delete(form);
        }, 0));
    });
    function hayCambios(excepto) {
        return [...originales].some(([form, original]) => form !== excepto && form.isConnected &&
            (recuperados.has(form) || valores(form) !== original));
    }
    const advertencia = 'Tienes cambios sin guardar. ¿Quieres salir y descartarlos?';
    let dialogo;
    let resolverDialogo;
    function confirmar(texto, salida = false) {
        if (!window.HTMLDialogElement) return Promise.resolve(window.confirm(texto));
        if (resolverDialogo) return Promise.resolve(false);
        if (!dialogo) {
            dialogo = document.createElement('dialog');
            dialogo.className = 'dialogo-confirmacion';
            dialogo.setAttribute('aria-labelledby', 'titulo-confirmacion');
            dialogo.setAttribute('aria-describedby', 'texto-confirmacion');
            const titulo = document.createElement('h2'); titulo.id = 'titulo-confirmacion';
            const descripcion = document.createElement('p'); descripcion.id = 'texto-confirmacion';
            const acciones = document.createElement('div'); acciones.className = 'acciones-confirmacion';
            const cancelar = document.createElement('button'); cancelar.type = 'button'; cancelar.className = 'boton-secundario';
            const aceptar = document.createElement('button'); aceptar.type = 'button';
            function cerrar(respuesta) { dialogo.close(); const resolver = resolverDialogo; resolverDialogo = null; if (resolver) resolver(respuesta); }
            cancelar.addEventListener('click', () => cerrar(false));
            aceptar.addEventListener('click', () => cerrar(true));
            dialogo.addEventListener('cancel', evento => { evento.preventDefault(); cerrar(false); });
            acciones.append(cancelar, aceptar); dialogo.append(titulo, descripcion, acciones); document.body.append(dialogo);
        }
        dialogo.querySelector('h2').textContent = salida ? 'Cambios sin guardar' : 'Confirmar operación';
        dialogo.querySelector('p').textContent = texto;
        const botones = dialogo.querySelectorAll('button');
        botones[0].textContent = salida ? 'Seguir editando' : 'Cancelar';
        botones[1].textContent = salida ? 'Salir sin guardar' : 'Confirmar';
        return new Promise(resolver => { resolverDialogo = resolver; dialogo.showModal(); botones[0].focus(); });
    }
    document.addEventListener('click', async evento => {
        const enlace = evento.target.closest('a[href]');
        if (!enlace || evento.defaultPrevented || evento.button !== 0 || evento.ctrlKey || evento.metaKey || evento.shiftKey || evento.altKey ||
            enlace.hasAttribute('download') || (enlace.target && enlace.target !== '_self')) return;
        const destino = new URL(enlace.href, location.href);
        if (destino.origin === location.origin && destino.pathname === location.pathname && destino.search === location.search && destino.hash) return;
        if (hayCambios()) {
            evento.preventDefault();
            if (await confirmar(advertencia, true)) { salidaPermitida = true; location.assign(destino.href); }
        }
    });
    const guardando = new Map();
    function bloquearGuardado(form) {
        if (form.method.toLowerCase() !== 'post') return;
        guardando.set(form, []);
        form.setAttribute('aria-busy', 'true');
        // El navegador construye primero los datos del envío, incluido el botón pulsado.
        setTimeout(() => {
            if (!guardando.has(form)) return;
            form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]').forEach(boton => {
                guardando.get(form).push([boton, boton.disabled, boton.innerHTML, boton.value]);
                boton.disabled = true; boton.setAttribute('aria-disabled', 'true');
                if (boton.tagName === 'INPUT') boton.value = 'Guardando…'; else boton.textContent = 'Guardando…';
            });
        }, 0);
    }
    function restaurarGuardados() {
        guardando.forEach((botones, form) => {
            form.removeAttribute('aria-busy');
            botones.forEach(([boton, deshabilitado, html, valor]) => {
                boton.disabled = deshabilitado; boton.removeAttribute('aria-disabled');
                if (boton.tagName === 'INPUT') boton.value = valor; else boton.innerHTML = html;
            });
        });
        guardando.clear();
    }
    const aprobados = new WeakSet();
    const pendientes = new WeakSet();
    async function prepararToken(form) {
        if (form.method.toLowerCase() !== 'post') return;
        const url = document.querySelector('main[data-token-url]')?.dataset.tokenUrl;
        // El login usa el token de su propio formulario; este servicio requiere una sesión ya iniciada.
        if (!url) return;
        if (new URL(url, location.href).origin !== location.origin) throw new Error('token');
        const respuesta = await fetch(url, {credentials: 'same-origin', cache: 'no-store', headers: {'Accept': 'application/json'}});
        if (!respuesta.ok || respuesta.redirected) throw new Error('sesion');
        const datos = await respuesta.json();
        const campo = form.elements.namedItem(datos.nombre);
        if (!campo || typeof datos.token !== 'string' || !/^[a-f0-9]{32,128}$/.test(datos.token)) throw new Error('token');
        campo.value = datos.token;
    }
    document.addEventListener('submit', async evento => {
        if (evento.defaultPrevented) return;
        const form = evento.target;
        if (guardando.has(form)) { evento.preventDefault(); return; }
        if (aprobados.delete(form)) { salidaPermitida = true; bloquearGuardado(form); return; }
        const otrosCambios = hayCambios(form);
        if (form.method.toLowerCase() !== 'post' && !form.dataset.confirmar && !otrosCambios) { salidaPermitida = true; return; }
        evento.preventDefault();
        if (pendientes.has(form)) return;
        pendientes.add(form);
        const texto = [form.dataset.confirmar, otrosCambios ? advertencia : ''].filter(Boolean).join(' ');
        try {
            if (texto && !await confirmar(texto, !form.dataset.confirmar)) return;
            await prepararToken(form);
            // Reenviar después de que termine el evento original, también cuando no hubo petición de red (login).
            await new Promise(resolver => setTimeout(resolver, 0));
            aprobados.add(form);
            if (evento.submitter) form.requestSubmit(evento.submitter); else form.requestSubmit();
            queueMicrotask(() => aprobados.delete(form));
        } catch (error) {
            let aviso = form.querySelector('[data-error-guardado]');
            if (!aviso) {
                aviso = document.createElement('div'); aviso.className = 'error'; aviso.dataset.errorGuardado = '';
                aviso.setAttribute('role','alert'); aviso.setAttribute('tabindex','-1'); form.prepend(aviso);
            }
            aviso.textContent = 'No se pudo preparar el guardado. Lo escrito se conserva. Revisa tu conexión y vuelve a intentarlo; si tu sesión terminó, inicia sesión en otra pestaña.';
            aviso.focus();
        } finally { pendientes.delete(form); }
    });
    window.addEventListener('beforeunload', evento => {
        if (!salidaPermitida && hayCambios()) { evento.preventDefault(); evento.returnValue = ''; }
    });
    window.addEventListener('pageshow', () => { salidaPermitida = false; restaurarGuardados(); });
    // Los errores del servidor se muestran en el formulario y reciben el foco al volver.
    const errorServidor = document.querySelector('main .error[role="alert"]');
    if (errorServidor && errorServidor.textContent.trim()) {
        errorServidor.setAttribute('tabindex', '-1');
        errorServidor.focus();
    }
}());
