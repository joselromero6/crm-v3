/**
 * CRM CIBR — Alertas del análisis financiero.
 *
 * Al pasar el mouse por una tarjeta con alerta (o al tocarla o
 * enfocarla con el teclado) muestra su nota explicativa.
 */
document.addEventListener('DOMContentLoaded', function () {

    var tarjetas = document.querySelectorAll('.crm-v3-alerta');

    if (!tarjetas.length) {
        return;
    }

    var flotante = document.createElement('div');

    flotante.className = 'crm-v3-alerta-flotante';
    flotante.setAttribute('role', 'tooltip');
    flotante.hidden = true;

    document.body.appendChild(flotante);

    var activa = null;

    function mostrar(tarjeta) {

        var nota = tarjeta.querySelector('.crm-v3-alerta-nota');

        if (!nota) {
            return;
        }

        activa = tarjeta;

        flotante.innerHTML = nota.innerHTML;
        flotante.classList.toggle('is-ambar', tarjeta.classList.contains('crm-v3-alerta-ambar'));
        flotante.hidden = false;

        // Debajo de la tarjeta; si no cabe, arriba.
        var caja   = tarjeta.getBoundingClientRect();
        var alto   = flotante.offsetHeight;
        var ancho  = flotante.offsetWidth;
        var margen = 8;

        var arriba = caja.bottom + margen;

        if (arriba + alto > window.innerHeight - margen) {
            arriba = Math.max(margen, caja.top - alto - margen);
        }

        var izquierda = caja.left + (caja.width / 2) - (ancho / 2);

        izquierda = Math.min(
            Math.max(margen, izquierda),
            window.innerWidth - ancho - margen
        );

        flotante.style.top  = arriba + 'px';
        flotante.style.left = izquierda + 'px';
    }

    function ocultar() {
        activa = null;
        flotante.hidden = true;
    }

    tarjetas.forEach(function (tarjeta) {

        // El texto del atributo "title" es solo un respaldo sin JavaScript;
        // se quita para que no aparezca dos veces.
        tarjeta.removeAttribute('title');

        tarjeta.addEventListener('mouseenter', function () { mostrar(tarjeta); });
        tarjeta.addEventListener('mouseleave', ocultar);
        tarjeta.addEventListener('focus', function () { mostrar(tarjeta); });
        tarjeta.addEventListener('blur', ocultar);

        // En pantallas táctiles: tocar muestra u oculta.
        tarjeta.addEventListener('click', function () {
            if (activa === tarjeta && !tarjeta.matches(':hover')) {
                ocultar();
            } else {
                mostrar(tarjeta);
            }
        });
    });

    window.addEventListener('scroll', ocultar, true);

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            ocultar();
        }
    });
});
