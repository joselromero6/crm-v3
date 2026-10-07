/**
 * CRM CIBR — Análisis financiero del comprador.
 * Actualiza el Total mientras se escriben los gastos.
 */
document.addEventListener('DOMContentLoaded', function () {

    var formato = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    document.querySelectorAll('.crm-v3-comprador').forEach(function (tarjeta) {

        var campos = tarjeta.querySelectorAll('.crm-v3-comprador-campo input');
        var total  = tarjeta.querySelector('.crm-v3-comprador-total strong');
        var base   = parseFloat(tarjeta.getAttribute('data-presupuesto')) || 0;

        function recalcular() {

            var suma = base;

            campos.forEach(function (campo) {
                var valor = parseFloat(campo.value);

                if (!isNaN(valor) && valor > 0) {
                    suma += valor;
                }
            });

            total.textContent = '$' + formato.format(suma);
        }

        campos.forEach(function (campo) {
            campo.addEventListener('input', recalcular);
        });
    });
});
