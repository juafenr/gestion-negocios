/* Ayuda visual. El servidor vuelve a validar todos los montos y saldos. */
(function () {
    'use strict';
    const form = document.getElementById('formulario-pago');
    if (!form) return;
    const campo = nombre => document.getElementById(nombre);
    function centavos(valor) {
        if (!/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/.test(valor)) return null;
        const partes = valor.split('.');
        return Number(partes[0]) * 100 + Number((partes[1] || '').padEnd(2, '0'));
    }
    function moneda(valor) { return 'Q ' + Math.floor(valor / 100) + '.' + String(valor % 100).padStart(2, '0'); }
    function actualizar() {
        const montos = ['efectivo', 'credito', 'debito', 'recibido'].map(nombre => centavos(campo(nombre).value));
        if (montos.includes(null)) { campo('resumen-cobro').textContent = 'Completa los montos con hasta dos decimales.'; return; }
        const [efectivo, credito, debito, recibido] = montos;
        const total = efectivo + credito + debito;
        const saldo = Number(campo('cuenta_id').selectedOptions[0].dataset.saldo);
        if (recibido < efectivo || (efectivo === 0 && recibido !== 0)) { campo('resumen-cobro').textContent = 'Revisa el efectivo recibido y el efectivo aplicado.'; return; }
        if (total > saldo) { campo('resumen-cobro').textContent = 'El pago supera el saldo de la cuenta.'; return; }
        campo('resumen-cobro').textContent = 'Se aplicará: ' + moneda(total) + ' · Cambio: ' + moneda(recibido - efectivo) + ' · Saldo después del pago: ' + moneda(saldo - total);
    }
    form.addEventListener('input', actualizar);
    form.addEventListener('change', actualizar);
    form.addEventListener('submit', () => { campo('registrar-pago').disabled = true; });
    window.addEventListener('pageshow', () => { campo('registrar-pago').disabled = false; });
    actualizar();
}());
