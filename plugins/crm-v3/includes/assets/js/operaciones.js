jQuery(function ($) {

    'use strict';

    const $filters = $('.crm-operaciones-filter select');
    const $tableWrap = $('.crm-operaciones-table-wrap');

    if (!$filters.length || !$tableWrap.length) {
        return;
    }

    function cargarOperaciones() {

        const data = {
            action: 'crm_v3_operaciones_filtrar',
            nonce: crmOperaciones.nonce,

            tipo_propiedad: $('#operacion-tipo-propiedad').val(),
            expediente: $('#operacion-expediente').val(),
            tipo_avaluo: $('#operacion-avaluo').val(),
            institucion: $('#operacion-institucion').val(),
            notaria: $('#operacion-notaria').val(),
            estatus: $('#operacion-estatus').val(),
            contador: $('.crm-operaciones-stat.is-active').data('filtro') || ''
        };

        $tableWrap.addClass('is-loading');

        $.ajax({
            url: crmOperaciones.ajaxUrl,
            type: 'POST',
            data: data,

            success: function (response) {

                if (
                    response.success &&
                    response.data &&
                    response.data.html
                ) {
                    $tableWrap.html(response.data.html);
                }
            },

            complete: function () {
                $tableWrap.removeClass('is-loading');
            }
        });
    }



$('.crm-operaciones-stat').on('click', function () {

    $('.crm-operaciones-stat').removeClass('is-active');

    $(this).addClass('is-active');

    const filtro = $(this).data('filtro');

    if (filtro === 'total') {

        $filters.val('');

    } else if (filtro === 'sin_expediente') {

        $('#operacion-expediente').val('0');
        $('#operacion-estatus').val('');

    } else {

        $('#operacion-estatus').val(filtro);
        $('#operacion-expediente').val('');
    }

    cargarOperaciones();
});

    $filters.on('change', function () {
        cargarOperaciones();
    });

});


