document.addEventListener('DOMContentLoaded', function () {

    /*
     * Escapa un valor antes de insertarlo como HTML.
     */
    function esc(valor) {

        const div = document.createElement('div');

        div.textContent =
            valor === null || valor === undefined
                ? ''
                : String(valor);

        return div.innerHTML;
    }


    const counters = document.querySelectorAll(
        '.crm-bo-client-counter'
    );

    const selector = document.getElementById(
        'crm-bo-cliente-selector'
    );

    if (!counters.length || !selector) {
        return;
    }


    counters.forEach(function (counter) {

        counter.addEventListener('click', function () {

            const estatus = this.dataset.estatus;


            /*
             * Marcar contador activo
             */

            counters.forEach(function (item) {
                item.classList.remove('is-active');
            });

            this.classList.add('is-active');


            /*
             * Estado de carga
             */

            selector.disabled = true;

            selector.innerHTML =
                '<option value="">Cargando clientes...</option>';


            /*
             * Preparar AJAX
             */

            const formData = new FormData();

            formData.append(
                'action',
                'crm_v3_backoffice_clientes_estatus'
            );

            formData.append(
                'nonce',
                crmBackoffice.nonce
            );

            formData.append(
                'estatus',
                estatus
            );


            /*
             * Solicitud AJAX
             */

            fetch(
                crmBackoffice.ajaxUrl,
                {
                    method: 'POST',
                    body: formData
                }
            )

            .then(function (response) {
                return response.json();
            })

            .then(function (response) {

                if (!response.success) {
                    throw new Error(
                        response.data?.message ||
                        'Error al cargar clientes.'
                    );
                }


                /*
                 * Cargar clientes en selector
                 */

                selector.innerHTML =
                    response.data.options;

                selector.disabled = false;

            })

            .catch(function () {

                selector.innerHTML =
                    '<option value="">Error al cargar clientes</option>';

                selector.disabled = true;

            });

        });

    });


    /*
     * =========================================================
     * SELECCIONAR CLIENTE
     * =========================================================
     */

    selector.addEventListener('change', function () {

        const clienteId = this.value;

        if (!clienteId) {
            return;
        }

        const resumen = document.getElementById(
            'crm-bo-cliente-resumen'
        );

        resumen.innerHTML =
            '<div class="crm-bo-analysis-placeholder">' +
            'Cargando información del cliente...' +
            '</div>';

        const formData = new FormData();

        formData.append(
            'action',
            'crm_v3_backoffice_cliente_resumen'
        );

        formData.append(
            'nonce',
            crmBackoffice.nonce
        );

        formData.append(
            'cliente_id',
            clienteId
        );

        fetch(
            crmBackoffice.ajaxUrl,
            {
                method: 'POST',
                body: formData
            }
        )
        .then(function (response) {
            return response.json();
        })
        .then(function (response) {

            if (!response.success) {
                throw new Error(
                    response.data?.message ||
                    'Error al cargar el cliente.'
                );
            }

                            const cliente =
                    response.data.cliente;

                const propiedades =
                    response.data.propiedades || [];

                const operaciones =
                    response.data.operaciones || [];


                /*
                 * =================================================
                 * RESUMEN DEL CLIENTE
                 * =================================================
                 */

                let html = `
                    <div class="crm-bo-client-summary">

                        <div class="crm-bo-client-summary-header">
                            <strong>${esc(cliente.nombre)}</strong>
                            <span>${esc(cliente.tipo)}</span>
                        </div>

                        <div class="crm-bo-client-summary-data">

                            <div>
                                <small>Estatus</small>
                                <strong>${esc(cliente.estatus)}</strong>
                            </div>

                            <div>
                                <small>Prioridad</small>
                                <strong>${esc(cliente.prioridad)}</strong>
                            </div>

                            <div>
                                <small>Origen</small>
                                <strong>${esc(cliente.origen_lead)}</strong>
                            </div>

                            <div>
                                <small>Relación comercial</small>
                                <strong>${esc(cliente.relacion_comercial)}</strong>
                            </div>

                            <div>
                                <small>Origen de recursos</small>
                                <strong>${esc(cliente.origen_recursos)}</strong>
                            </div>

                            <div>
                                <small>Tipo de crédito</small>
                                <strong>${esc(cliente.tipo_credito)}</strong>
                            </div>

                            <div>
                                <small>Presupuesto</small>
                                <strong>${esc(cliente.presupuesto)}</strong>
                            </div>

                            <div>
                                <small>Adeudo hipoteca</small>
                                <strong>${esc(cliente.adeudo_hipoteca)}</strong>
                            </div>

                        </div>

                    </div>
                `;


                /*
                 * =================================================
                 * PROPIEDADES
                 * =================================================
                 */

                const tipoCliente =
                    String(cliente.tipo).toLowerCase();

                const esVendedor =
                    tipoCliente.includes('vendedor') ||
                    tipoCliente.includes('ambos');


                if (
                    esVendedor &&
                    propiedades.length
                ) {

                    html += `
                        <div class="crm-bo-client-section">

                            <div class="crm-bo-client-section-title">
                                Propiedades
                            </div>

                            <div class="crm-bo-client-properties">
                    `;

                    propiedades.forEach(function (propiedad) {

                        html += `
                            <div class="crm-bo-client-property">

                                <strong>
                                    ${esc(propiedad.nombre)}
                                </strong>

                                <span>
                                    ${esc(propiedad.tipo)}
                                </span>

                                <span>
                                    ${esc(propiedad.colonia)}
                                </span>

                                <span>
                                    ${esc(propiedad.ciudad)}
                                </span>

                                <span>
                                    ${esc(propiedad.precio_venta)}
                                </span>

                                <span>
                                    ${esc(propiedad.estatus)}
                                </span>

                            </div>
                        `;

                    });

                    html += `
                            </div>

                        </div>
                    `;
                }


                /*
                 * =================================================
                 * OPERACIONES
                 * =================================================
                 */

                if (operaciones.length) {

                    html += `
                        <div class="crm-bo-client-section">

                            <div class="crm-bo-client-section-title">
                                Operaciones
                            </div>

                            <div class="crm-bo-client-operations">
                    `;

                    operaciones.forEach(function (operacion) {

                        html += `
                            <div class="crm-bo-client-operation">

                                <strong>
                                    ${esc(operacion.nombre)}
                                </strong>

                                <span>
                                    Propiedad:
                                    ${esc(operacion.propiedad)}
                                </span>

                                <span>
                                    Comprador:
                                    ${esc(operacion.comprador)}
                                </span>

                                <span>
                                    Estatus:
                                    ${esc(operacion.estatus)}
                                </span>

                                <span>
                                    Valor mercado:
                                    ${esc(operacion.valor_mercado)}
                                </span>

                                <span>
                                    Precio cierre:
                                    ${esc(operacion.precio_cierre)}
                                </span>

                                <span>
                                    Fecha cierre:
                                    ${esc(operacion.fecha_cierre)}
                                </span>

                            </div>
                        `;

                    });

                    html += `
                            </div>

                        </div>
                    `;
                }


                resumen.innerHTML = html;


        })
        .catch(function () {

            resumen.innerHTML =
                '<div class="crm-bo-analysis-placeholder">' +
                'No fue posible cargar la información del cliente.' +
                '</div>';

        });

    });


    /*
     * =========================================================
     * ANÁLISIS DE MERCADO — TIPOS POR COLONIA
     * =========================================================
     */

    const mercadoColonia =
        document.getElementById(
            'crm-v3-mercado-colonia'
        );

    const mercadoTipo =
        document.getElementById(
            'crm-v3-mercado-tipo'
        );


    if (
        mercadoColonia &&
        mercadoTipo
    ) {

        mercadoColonia.addEventListener(
            'change',
            function () {

                const coloniaId = this.value;


                mercadoTipo.disabled = true;

                mercadoTipo.innerHTML =
                    '<option value="">Cargando tipos...</option>';


                if (!coloniaId) {

                    mercadoTipo.innerHTML =
                        '<option value="">Seleccionar tipo de propiedad</option>';

                    return;
                }


                const formData = new FormData();

                formData.append(
                    'action',
                    'crm_v3_backoffice_mercado_tipos'
                );

                formData.append(
                    'nonce',
                    crmBackoffice.nonce
                );

                formData.append(
                    'colonia_id',
                    coloniaId
                );


                fetch(
                    crmBackoffice.ajaxUrl,
                    {
                        method: 'POST',
                        body: formData
                    }
                )

                .then(function (response) {
                    return response.json();
                })

                .then(function (response) {

                    if (!response.success) {

                        throw new Error(
                            response.data?.message ||
                            'Error al cargar tipos.'
                        );

                    }


                    mercadoTipo.innerHTML =
                        '<option value="">Seleccionar tipo de propiedad</option>';


                    response.data.tipos.forEach(
                        function (tipo) {

                            const option =
                                document.createElement('option');

                            option.value =
                                tipo.id;

                            option.textContent =
                                tipo.name;

                            mercadoTipo.appendChild(
                                option
                            );

                        }
                    );


                    mercadoTipo.disabled = false;

                })

                .catch(function () {

                    mercadoTipo.innerHTML =
                        '<option value="">Error al cargar tipos</option>';

                    mercadoTipo.disabled = true;

                });

            }
        );

    }


/*
 * =========================================================
 * ANÁLISIS DE MERCADO — PROPIEDADES COMPARABLES
 * =========================================================
 */

const mercadoResultado =
    document.getElementById(
        'crm-v3-mercado-resultado'
    );

    const mercadoPrecioEvaluar =
    document.getElementById(
        'crm-v3-mercado-precio-evaluar'
    );

if (
    mercadoTipo &&
    mercadoResultado
) {

    mercadoTipo.addEventListener(
        'change',
        function () {

            const coloniaId =
                mercadoColonia.value;

            const tipoId =
                this.value;


            if (
                !coloniaId ||
                !tipoId
            ) {

                mercadoResultado.innerHTML =
                    '<div class="crm-v3-mercado-placeholder">' +
                    'Selecciona una colonia y un tipo de propiedad para iniciar el análisis.' +
                    '</div>';

                return;
            }


            mercadoResultado.innerHTML =
                '<div class="crm-v3-mercado-placeholder">' +
                'Analizando propiedades comparables...' +
                '</div>';


            const formData =
                new FormData();


            formData.append(
                'action',
                'crm_v3_backoffice_mercado_propiedades'
            );

            formData.append(
                'nonce',
                crmBackoffice.nonce
            );

            formData.append(
                'colonia_id',
                coloniaId
            );

            formData.append(
                'tipo_id',
                tipoId
            );


            fetch(
                crmBackoffice.ajaxUrl,
                {
                    method: 'POST',
                    body: formData
                }
            )

            .then(function (response) {
                return response.json();
            })

            .then(function (response) {

                if (!response.success) {

                    throw new Error(
                        response.data?.message ||
                        'Error al cargar propiedades.'
                    );

                }


                const propiedades =
                    response.data.propiedades || [];


                if (!propiedades.length) {

                    mercadoResultado.innerHTML =
                        '<div class="crm-v3-mercado-placeholder">' +
                        'No se encontraron propiedades comparables para los filtros seleccionados.' +
                        '</div>';

                    return;
                }


                let html =
                    '<div class="crm-v3-mercado-comparables">';

                html +=
                    '<div class="crm-v3-mercado-result-title">' +
                    'Propiedades comparables encontradas: ' +
                    propiedades.length +
                    '</div>';


                propiedades.forEach(
    function (propiedad) {

        html +=
            '<div class="crm-v3-mercado-comparable">';

        html +=
            '<div class="crm-v3-mercado-comparable-title">' +
            esc(propiedad.nombre) +
            '</div>';

        html +=
            '<div class="crm-v3-mercado-comparable-data">';

        html +=
            '<div>' +
                '<small>Fecha</small>' +
                '<strong>' +
                    esc(propiedad.fecha || '—') +
                '</strong>' +
            '</div>';

        html +=
            '<div>' +
                '<small>Terreno</small>' +
                '<strong>' +
                    esc(propiedad.m2_terreno || '—') +
                    ' m²' +
                '</strong>' +
            '</div>';

        html +=
            '<div>' +
                '<small>Construcción</small>' +
                '<strong>' +
                    esc(propiedad.m2_construccion || '—') +
                    ' m²' +
                '</strong>' +
            '</div>';

        html +=
            '<div>' +
                '<small>Valor catastral</small>' +
                '<strong>' +
                    esc(propiedad.valor_catastral || '—') +
                '</strong>' +
            '</div>';

        html +=
            '<div>' +
                '<small>Precio venta</small>' +
                '<strong>' +
                    esc(propiedad.precio_venta || '—') +
                '</strong>' +
            '</div>';

        html +=
            '<div>' +
                '<small>Valor mercado</small>' +
                '<strong>' +
                    esc(propiedad.valor_mercado || '—') +
                '</strong>' +
            '</div>';

        html +=
            '</div>';

        html +=
            '</div>';

    }
);


/* =========================================================
 * RESUMEN ESTADÍSTICO
 * ========================================================= */

const calcularPromedio = function (campo) {

    const valores = propiedades
        .map(function (propiedad) {
            return Number(propiedad[campo]);
        })
        .filter(function (valor) {
            return Number.isFinite(valor) && valor > 0;
        });

    if (!valores.length) {
        return null;
    }

    return valores.reduce(
        function (total, valor) {
            return total + valor;
        },
        0
    ) / valores.length;
};


const promedioPrecioVenta =
    calcularPromedio('precio_venta');

const promedioValorMercado =
    calcularPromedio('valor_mercado');

const promedioValorCatastral =
    calcularPromedio('valor_catastral');


const valoresM2Terreno = propiedades
    .map(function (propiedad) {

        const precio =
            Number(propiedad.precio_venta);

        const m2 =
            Number(propiedad.m2_terreno);

        if (
            !Number.isFinite(precio) ||
            !Number.isFinite(m2) ||
            precio <= 0 ||
            m2 <= 0
        ) {
            return null;
        }

        return precio / m2;

    })
    .filter(function (valor) {
        return valor !== null;
    });


const valoresM2Construccion = propiedades
    .map(function (propiedad) {

        const precio =
            Number(propiedad.precio_venta);

        const m2 =
            Number(propiedad.m2_construccion);

        if (
            !Number.isFinite(precio) ||
            !Number.isFinite(m2) ||
            precio <= 0 ||
            m2 <= 0
        ) {
            return null;
        }

        return precio / m2;

    })
    .filter(function (valor) {
        return valor !== null;
    });


const promedioM2Terreno =
    valoresM2Terreno.length
        ? valoresM2Terreno.reduce(
            function (total, valor) {
                return total + valor;
            },
            0
        ) / valoresM2Terreno.length
        : null;


const promedioM2Construccion =
    valoresM2Construccion.length
        ? valoresM2Construccion.reduce(
            function (total, valor) {
                return total + valor;
            },
            0
        ) / valoresM2Construccion.length
        : null;


const formatoMoneda = function (valor) {

    if (
        valor === null ||
        !Number.isFinite(valor)
    ) {
        return '—';
    }

    return new Intl.NumberFormat(
        'es-MX',
        {
            style: 'currency',
            currency: 'MXN',
            maximumFractionDigits: 0
        }
    ).format(valor);
};


const formatoNumero = function (valor) {

    if (
        valor === null ||
        !Number.isFinite(valor)
    ) {
        return '—';
    }

    return new Intl.NumberFormat(
        'es-MX',
        {
            maximumFractionDigits: 0
        }
    ).format(valor);
};


/* =========================================================
 * PRECIO A EVALUAR — DATOS PARA COMPARACIÓN
 * ========================================================= */

if (mercadoPrecioEvaluar) {

    mercadoPrecioEvaluar.dataset.promedioVenta =
        promedioPrecioVenta !== null
            ? promedioPrecioVenta
            : '';

    mercadoPrecioEvaluar.dataset.promedioMercado =
        promedioValorMercado !== null
            ? promedioValorMercado
            : '';

    mercadoPrecioEvaluar.dataset.promedioCatastral =
        promedioValorCatastral !== null
            ? promedioValorCatastral
            : '';

}



html +=
    '<div class="crm-v3-mercado-resumen">';

html +=
    '<div class="crm-v3-mercado-resumen-title">' +
    'Resumen del mercado' +
    '</div>';

html +=
    '<div class="crm-v3-mercado-resumen-grid">';

html +=
    '<div>' +
        '<small>Promedio precio venta</small>' +
        '<strong>' +
            formatoMoneda(promedioPrecioVenta) +
        '</strong>' +
    '</div>';

html +=
    '<div>' +
        '<small>Promedio valor mercado</small>' +
        '<strong>' +
            formatoMoneda(promedioValorMercado) +
        '</strong>' +
    '</div>';

html +=
    '<div>' +
        '<small>Promedio valor catastral</small>' +
        '<strong>' +
            formatoMoneda(promedioValorCatastral) +
        '</strong>' +
    '</div>';

html +=
    '<div>' +
        '<small>Promedio $/m² terreno</small>' +
        '<strong>' +
            formatoMoneda(promedioM2Terreno) +
        '</strong>' +
    '</div>';

html +=
    '<div>' +
        '<small>Promedio $/m² construcción</small>' +
        '<strong>' +
            formatoMoneda(promedioM2Construccion) +
        '</strong>' +
    '</div>';

html +=
    '</div>';

html +=
    '</div>';




                html +=
                    '</div>';


                mercadoResultado.innerHTML =
                    html;

            })

            .catch(function () {

                mercadoResultado.innerHTML =
                    '<div class="crm-v3-mercado-placeholder">' +
                    'No fue posible cargar las propiedades comparables.' +
                    '</div>';

            });

        }
    );

}


if (
    mercadoPrecioEvaluar &&
    !mercadoPrecioEvaluar.dataset.listener
) {

    mercadoPrecioEvaluar.addEventListener(
        'input',
        function () {

            const precio =
                Number(this.value);

            const promedioVenta =
                Number(
                    this.dataset.promedioVenta
                );

            const promedioMercado =
                Number(
                    this.dataset.promedioMercado
                );


            let evaluacion =
                document.getElementById(
                    'crm-v3-mercado-evaluacion'
                );


            if (!evaluacion) {

                evaluacion =
                    document.createElement('div');

                evaluacion.id =
                    'crm-v3-mercado-evaluacion';

                evaluacion.className =
                    'crm-v3-mercado-evaluacion';

                mercadoResultado.appendChild(
                    evaluacion
                );

            }


            if (
                !precio ||
                precio <= 0
            ) {

                evaluacion.innerHTML = '';

                return;
            }


            let clasificacion =
                '';

            let clase =
                '';


            if (
                Number.isFinite(promedioVenta) &&
                promedioVenta > 0
            ) {

                const diferenciaVenta =
                    precio - promedioVenta;

                const porcentajeVenta =
                    (
                        diferenciaVenta /
                        promedioVenta
                    ) * 100;


                if (porcentajeVenta <= -10) {

                    clasificacion =
                        'Buen precio';

                    clase =
                        'is-good';

                } else if (
                    porcentajeVenta <= 10
                ) {

                    clasificacion =
                        'Precio de mercado';

                    clase =
                        'is-market';

                } else {

                    clasificacion =
                        'Sobreprecio';

                    clase =
                        'is-high';

                }


                let html =
                    '<div class="crm-v3-mercado-evaluacion-title">' +
                    'Evaluación del precio' +
                    '</div>';


                html +=
                    '<div class="crm-v3-mercado-evaluacion-item">' +
                        '<small>Vs. promedio de venta</small>' +
                        '<strong>' +
                            (diferenciaVenta >= 0 ? '+' : '') +
                            porcentajeVenta.toFixed(1) +
                            '%' +
                        '</strong>' +
                    '</div>';


                if (
                    Number.isFinite(promedioMercado) &&
                    promedioMercado > 0
                ) {

                    const diferenciaMercado =
                        precio - promedioMercado;

                    const porcentajeMercado =
                        (
                            diferenciaMercado /
                            promedioMercado
                        ) * 100;


                    html +=
                        '<div class="crm-v3-mercado-evaluacion-item">' +
                            '<small>Vs. promedio de mercado</small>' +
                            '<strong>' +
                                (diferenciaMercado >= 0 ? '+' : '') +
                                porcentajeMercado.toFixed(1) +
                                '%' +
                            '</strong>' +
                        '</div>';

                }


                html +=
                    '<div class="crm-v3-mercado-evaluacion-status ' +
                    clase +
                    '">' +
                    clasificacion +
                    '</div>';


                evaluacion.innerHTML =
                    html;

            } else {

                evaluacion.innerHTML =
                    '<div class="crm-v3-mercado-evaluacion-title">' +
                    'Evaluación del precio' +
                    '</div>' +
                    '<div class="crm-v3-mercado-evaluacion-item">' +
                    '<small>Referencia</small>' +
                    '<strong>No hay promedio de venta disponible</strong>' +
                    '</div>';

            }

        }
    );


    mercadoPrecioEvaluar.dataset.listener =
        '1';

}



});