/**
 * CRM CIBR — Catálogos
 * Ventana para editar una tarjeta.
 */
document.addEventListener('DOMContentLoaded', function () {

    var modal = document.getElementById('crm-catalogo-modal');

    if (!modal) {
        return;
    }

    var form = modal.querySelector('form');

    var tipos = {
        valuadores: 'valuador',
        bancos: 'banco',
        notarias: 'notaría',
        servicios: 'servicio'
    };

    var campos = ['titulo', 'nombre', 'direccion', 'telefono', 'email', 'web', 'comentarios'];

    function cerrar() {
        if (typeof modal.close === 'function') {
            modal.close();
        } else {
            modal.removeAttribute('open');
        }
    }

    // Lápiz de cada tarjeta: llena la ventana con sus datos y la abre.
    document.querySelectorAll('.crm-catalogo-editar').forEach(function (boton) {

        boton.addEventListener('click', function () {

            var registro;

            try {
                registro = JSON.parse(boton.getAttribute('data-registro'));
            } catch (error) {
                return;
            }

            form.elements.registro_id.value = registro.id;

            campos.forEach(function (campo) {
                form.elements[campo].value = registro[campo] || '';
            });

            modal.querySelector('.crm-catalogo-modal-tipo').textContent =
                tipos[registro.tipo] || '';

            if (typeof modal.showModal === 'function') {
                modal.showModal();
            } else {
                modal.setAttribute('open', 'open');
            }

            form.elements.titulo.focus();
        });
    });

    modal.querySelector('.crm-catalogo-modal-cerrar').addEventListener('click', cerrar);
    modal.querySelector('.crm-catalogo-modal-cancelar').addEventListener('click', cerrar);

    // Clic fuera de la ventana: cerrar.
    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) {
            cerrar();
        }
    });
});
