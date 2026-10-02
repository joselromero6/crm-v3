jQuery(function ($) {

    'use strict';

    const $filters = $('.crm-v3-propiedades-filters select');
    const $tableWrap = $('.crm-v3-propiedades-table-wrapper');

    if (!$filters.length || !$tableWrap.length) {
        return;
    }

    function cargarPropiedades() {

        const data = {
            action: 'crm_v3_propiedades_filtrar',
            nonce: crmPropiedades.nonce,

            colonia: $('#propiedad-colonia').val(),
            ciudad: $('#propiedad-ciudad').val(),
            tipo_propiedad: $('#propiedad-tipo-propiedad').val(),
            tipo_operacion: $('#propiedad-tipo-operacion').val(),
            estado_inmueble: $('#propiedad-estado-inmueble').val(),
            estado_comercial: $('#propiedad-estado-comercial').val(),
            documentacion: $('#propiedad-documentacion').val(),

            contador: $('.crm-prop-counter.is-active').data('filtro') || ''
        };

        $tableWrap.addClass('is-loading');

        $.ajax({
            url: crmPropiedades.ajaxUrl,
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


    /* ========================================================
       FILTROS
       ======================================================== */

    $filters.on('change', function () {
        cargarPropiedades();
    });


    /* ========================================================
       CONTADORES
       ======================================================== */

    $('.crm-prop-counter').on('click', function () {

        $('.crm-prop-counter').removeClass('is-active');

        $(this).addClass('is-active');

        cargarPropiedades();
    });

});