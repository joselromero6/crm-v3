<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * ============================================================
 * CRM V3 - CLIENTES
 * ============================================================
 *
 * Tabla principal del CRM.
 *
 * FUENTE DE DATOS:
 * - CPT clientes
 * - ACF clientes
 * - CPT operaciones
 * - ACF operaciones
 *
 * REGLA:
 * Los estatus visibles siempre provienen de ACF.
 *
 * ============================================================
 */


/**
 * ============================================================
 * PÁGINA PRINCIPAL
 * ============================================================
 */

function crm_v3_clientes_page() {

    if (!current_user_can('manage_options')) {
        return;
    }


    /*
     * --------------------------------------------------------
     * CLIENTES
     * --------------------------------------------------------
     */

        $clientes = new WP_Query(array(
            'post_type'      => 'clientes',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));

        $clientes->posts = crm_v3_ordenar_por_fecha(
            $clientes->posts,
            'fecha_de_registro'
        );


    /*
     * --------------------------------------------------------
     * RELACIONES Y OPERACIONES
     * --------------------------------------------------------
     */

    $crm_clientes_operaciones = array();

    $crm_clientes_cualquier_operacion = array();

    $crm_clientes_ultima_operacion = array();


    $crm_operaciones = get_posts(array(
        'post_type'      => 'operaciones',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC'
    ));

    $crm_operaciones = crm_v3_ordenar_por_fecha(
        $crm_operaciones,
        'fecha_de_operacion'
    );


    foreach ($crm_operaciones as $crm_operacion) {

        $crm_operacion_id = $crm_operacion->ID;


        /*
         * ----------------------------------------------------
         * ESTATUS DE LA OPERACIÓN
         * ----------------------------------------------------
         *
         * Campo ACF real:
         * estatus_de_operacion
         */

        $crm_estatus_operacion = get_field(
            'estatus_de_operacion',
            $crm_operacion_id
        );


        $crm_estatus_operacion = crm_v3_valor_normalizado(
            $crm_estatus_operacion
        );


        /*
         * ----------------------------------------------------
         * FUNCIÓN PARA REGISTRAR CLIENTE
         * ----------------------------------------------------
         */

        $crm_registrar_cliente_operacion = function (
            $cliente_id,
            $rol
        ) use (
            &$crm_clientes_operaciones,
            &$crm_clientes_cualquier_operacion,
            &$crm_clientes_ultima_operacion,
            $crm_operacion_id,
            $crm_estatus_operacion
        ) {

            if (!$cliente_id) {
                return;
            }


            /*
             * El cliente tiene una operación histórica.
             */

            $crm_clientes_cualquier_operacion[
                $cliente_id
            ] = true;


            /*
             * Guardar siempre la operación más reciente.
             *
             * Las operaciones vienen ordenadas DESC.
             */

            if (
                !isset(
                    $crm_clientes_ultima_operacion[
                        $cliente_id
                    ]
                )
            ) {

                $crm_clientes_ultima_operacion[
                    $cliente_id
                ] = array(

                    'operacion_id' =>
                        $crm_operacion_id,

                    'estatus' =>
                        $crm_estatus_operacion,

                    'rol' =>
                        $rol,

                );
            }


            /*
             * Las operaciones cerradas o perdidas
             * NO cuentan como relación activa.
             */

            if (
                    $crm_estatus_operacion === 'cerrada' ||
                    $crm_estatus_operacion === 'perdida'
            ) {

                return;
            }


            /*
             * Registrar operación activa.
             */

            if (
                !isset(
                    $crm_clientes_operaciones[
                        $cliente_id
                    ]
                )
            ) {

                $crm_clientes_operaciones[
                    $cliente_id
                ] = array(

                    'operacion_id' =>
                        $crm_operacion_id,

                    'estatus' =>
                        $crm_estatus_operacion,

                    'rol' =>
                        $rol,

                );
            }

        };


        /*
         * ----------------------------------------------------
         * COMPRADOR
         * ----------------------------------------------------
         */

        $crm_comprador = get_field(
            'comprador',
            $crm_operacion_id
        );


        $crm_comprador_id =
            crm_v3_get_related_id(
                $crm_comprador
            );


        $crm_registrar_cliente_operacion(
            $crm_comprador_id,
            'comprador'
        );


        /*
         * ----------------------------------------------------
         * VENDEDOR
         * ----------------------------------------------------
         */

        $crm_propiedad = get_field(
            'propiedad',
            $crm_operacion_id
        );


        $crm_propiedad_id =
            crm_v3_get_related_id(
                $crm_propiedad
            );


        if ($crm_propiedad_id) {

            $crm_vendedor = get_field(
                'cliente_propietario',
                $crm_propiedad_id
            );


            $crm_vendedor_id =
                crm_v3_get_related_id(
                    $crm_vendedor
                );


            $crm_registrar_cliente_operacion(
                $crm_vendedor_id,
                'vendedor'
            );
        }

    }


    ?>

    <div class="wrap crm-v3-clientes">


        <!-- ==================================================
             ENCABEZADO
             ================================================== -->

       <div class="crm-v3-module-header">

    <div class="crm-v3-module-header-info">

        <span>CRM CLIENTES</span>
        <p>CIBR Asesoría Análisis y Gestión Inmobiliaria</p>

    </div>

    <a
        href="<?php echo esc_url(
            admin_url(
                'admin.php?page=crm-nuevos-registros'
            )
        ); ?>"
        class="crm-v3-new-client"
    >
        + Nuevos registros
    </a>

</div>


        <!-- ==================================================
             CONTADORES
             ================================================== -->

        <div class="crm-v3-clientes-counters">


            <button
                type="button"
                class="crm-counter active"
                data-filter="todos"
            >
                <span>Totales</span>
                <strong id="crm-count-total">0</strong>
            </button>


            <button
                type="button"
                class="crm-counter"
                data-filter="comprador"
            >
                <span>Comprador</span>
                <strong id="crm-count-comprador">0</strong>
            </button>


            <button
                type="button"
                class="crm-counter"
                data-filter="vendedor"
            >
                <span>Vendedor</span>
                <strong id="crm-count-vendedor">0</strong>
            </button>

            <button type="button" class="crm-counter" data-filter="en_proceso">
                <span>En proceso</span>
                <strong id="crm-count-en-proceso">0</strong>
            </button>
        


            <button
                type="button"
                class="crm-counter"
                data-filter="relacionados"
            >
                <span>Relacionados</span>
                <strong id="crm-count-relacionados">0</strong>
            </button>

            <button
                type="button"
                class="crm-counter"
                data-filter="sin_relacion"
            >
                <span>Sin relación</span>
                <strong id="crm-count-sin-relacion">0</strong>
            </button>

            <button
                type="button"
                class="crm-counter"
                data-filter="perdido"
            >
                <span>Perdidos</span>
                <strong id="crm-count-perdidos">0</strong>
            </button>


        </div>


<!-- ==================================================
     FILTROS DESPLEGABLES
     ================================================== -->

<?php

/*
 * ==================================================
 * VALORES CAPTURADOS EN LOS CLIENTES
 * ==================================================
 *
 * ÚNICA FUENTE:
 * ACF de cada registro de cliente.
 *
 * Los filtros solamente muestran valores que
 * realmente existen en los clientes de la tabla.
 */

$crm_filtros = array(
    'origen_lead'        => array(),
    'tipo_cliente'       => array(),
    'relacion_comercial' => array(),
    'origen_recursos'    => array(),
    'tipo_credito'       => array(),
    'prioridad'          => array(),
);


if (
    isset($clientes->posts) &&
    is_array($clientes->posts)
) {

    foreach ($clientes->posts as $crm_cliente) {

        $crm_cliente_id =
            $crm_cliente->ID;


        /*
         * ==================================================
         * CAMPOS TAXONOMÍA ACF
         * ==================================================
         */

        $crm_campos_taxonomia = array(

            'origen_lead' => array(
                'campo'     => 'origen_lead',
                'taxonomia' => 'origen-lead',
            ),

            'tipo_cliente' => array(
                'campo'     => 'tipo_cliente',
                'taxonomia' => 'perfil-de-cliente',
            ),

            'relacion_comercial' => array(
                'campo'     => 'relacion_comercial',
                'taxonomia' => 'relacion-comercial',
            ),

            'origen_recursos' => array(
                'campo'     => 'origen_recursos',
                'taxonomia' => 'origen-de-recurso',
            ),

            'tipo_credito' => array(
                'campo'     => 'tipo_credito',
                'taxonomia' => 'tipo-de-creditos',
            ),

        );


        foreach (
            $crm_campos_taxonomia
            as $crm_filtro =>
            $crm_configuracion
        ) {

            $crm_valor =
                get_field(
                    $crm_configuracion['campo'],
                    $crm_cliente_id
                );


            /*
             * ACF puede devolver uno o varios valores.
             */

            $crm_valores = is_array(
                $crm_valor
            )
                ? $crm_valor
                : array($crm_valor);


            foreach (
                $crm_valores
                as $crm_valor_item
            ) {

                if (
                    $crm_valor_item === '' ||
                    $crm_valor_item === null ||
                    $crm_valor_item === false
                ) {
                    continue;
                }


                /*
                 * Si ACF devuelve objeto término.
                 */

                if (
                    is_object($crm_valor_item) &&
                    isset($crm_valor_item->term_id)
                ) {

                    $crm_term_id =
                        $crm_valor_item->term_id;

                } else {

                    $crm_term_id =
                        (int) $crm_valor_item;

                }


                if (!$crm_term_id) {
                    continue;
                }


                $crm_term =
                    get_term(
                        $crm_term_id,
                        $crm_configuracion['taxonomia']
                    );


                if (
                    !$crm_term ||
                    is_wp_error($crm_term)
                ) {
                    continue;
                }


                $crm_filtros[
                    $crm_filtro
                ][
                    $crm_term->term_id
                ] =
                    $crm_term->name;

            }

        }


        /*
         * ==================================================
         * PRIORIDAD
         * ==================================================
         *
         * ÚNICA FUENTE:
         * ACF clientes → prioridad
         */

        $crm_prioridad =
            get_field(
                'prioridad',
                $crm_cliente_id
            );


        if (
            is_string($crm_prioridad) &&
            $crm_prioridad !== ''
        ) {

            $crm_prioridad_label =
                $crm_prioridad;


            /*
             * Obtener etiqueta desde las opciones
             * definidas en ACF.
             */

            $crm_prioridad_field =
                get_field_object(
                    'prioridad',
                    $crm_cliente_id
                );


            if (
                $crm_prioridad_field &&
                !empty(
                    $crm_prioridad_field['choices']
                ) &&
                isset(
                    $crm_prioridad_field['choices'][
                        $crm_prioridad
                    ]
                )
            ) {

                $crm_prioridad_label =
                    $crm_prioridad_field['choices'][
                        $crm_prioridad
                    ];

            }


            $crm_filtros[
                'prioridad'
            ][
                $crm_prioridad
            ] =
                $crm_prioridad_label;

        }

    }

}


/*
 * ==================================================
 * ORDENAR OPCIONES
 * ==================================================
 */

foreach (
    $crm_filtros
    as &$crm_opciones
) {

    natcasesort(
        $crm_opciones
    );

}

unset($crm_opciones);

?>


<div class="crm-v3-clientes-filters">


    <!-- ORIGEN DEL LEAD -->

    <div class="crm-filter">

        <label>Origen del lead</label>

        <select
            data-filter-field="origen_lead"
        >

            <option value="">
                Todos
            </option>

            <?php foreach (
                $crm_filtros['origen_lead']
                as $crm_valor => $crm_etiqueta
            ) : ?>

                <option
                    value="<?php echo esc_attr(
                        $crm_valor
                    ); ?>"
                >
                    <?php echo esc_html(
                        $crm_etiqueta
                    ); ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <!-- TIPO DE CLIENTE -->

    <div class="crm-filter">

        <label>Tipo de cliente</label>

        <select
            data-filter-field="tipo_cliente"
        >

            <option value="">
                Todos
            </option>

            <?php foreach (
                $crm_filtros['tipo_cliente']
                as $crm_valor => $crm_etiqueta
            ) : ?>

                <option
                    value="<?php echo esc_attr(
                        $crm_valor
                    ); ?>"
                >
                    <?php echo esc_html(
                        $crm_etiqueta
                    ); ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <!-- RELACIÓN COMERCIAL -->

    <div class="crm-filter">

        <label>Relación comercial</label>

        <select
            data-filter-field="relacion_comercial"
        >

            <option value="">
                Todas
            </option>

            <?php foreach (
                $crm_filtros['relacion_comercial']
                as $crm_valor => $crm_etiqueta
            ) : ?>

                <option
                    value="<?php echo esc_attr(
                        $crm_valor
                    ); ?>"
                >
                    <?php echo esc_html(
                        $crm_etiqueta
                    ); ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <!-- ORIGEN DE RECURSOS -->

    <div class="crm-filter">

        <label>Origen de recursos</label>

        <select
            data-filter-field="origen_recursos"
        >

            <option value="">
                Todos
            </option>

            <?php foreach (
                $crm_filtros['origen_recursos']
                as $crm_valor => $crm_etiqueta
            ) : ?>

                <option
                    value="<?php echo esc_attr(
                        $crm_valor
                    ); ?>"
                >
                    <?php echo esc_html(
                        $crm_etiqueta
                    ); ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <!-- TIPO DE CRÉDITO -->

    <div class="crm-filter">

        <label>Tipo de crédito</label>

        <select
            data-filter-field="tipo_credito"
        >

            <option value="">
                Todos
            </option>

            <?php foreach (
                $crm_filtros['tipo_credito']
                as $crm_valor => $crm_etiqueta
            ) : ?>

                <option
                    value="<?php echo esc_attr(
                        $crm_valor
                    ); ?>"
                >
                    <?php echo esc_html(
                        $crm_etiqueta
                    ); ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <!-- PRIORIDAD -->

    <div class="crm-filter">

        <label>Prioridad</label>

        <select
            data-filter-field="prioridad"
        >

            <option value="">
                Todas
            </option>

            <?php foreach (
                $crm_filtros['prioridad']
                as $crm_valor => $crm_etiqueta
            ) : ?>

                <option
                    value="<?php echo esc_attr(
                        $crm_valor
                    ); ?>"
                >
                    <?php echo esc_html(
                        $crm_etiqueta
                    ); ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>


</div>





        <!-- ==================================================
             TABLA
             ================================================== -->

        <div class="crm-v3-clientes-table-wrapper">


            <table
                class="widefat fixed striped crm-v3-clientes-table"
            >


                <thead>

                    <tr>

                        <th>Cliente</th>
                        <th>Origen lead</th>
                        <th>Tipo cliente</th>
                        <th>Rel. comercial</th>
                        <th>Procedencia</th>
                        <th>Tipo crédito</th>
                        <th>Presupuesto</th>
                        <th>Hipoteca</th>
                        <th>Estatus</th>
                        <th>Estatus OP</th>

                    </tr>

                </thead>


                <tbody>


                <?php if ($clientes->have_posts()) : ?>


                    <?php while (
                        $clientes->have_posts()
                    ) : ?>


                        <?php

                        $clientes->the_post();

                        $cliente_id =
                            get_the_ID();


                        /*
                         * --------------------------------------
                         * DATOS ACF
                         * --------------------------------------
                         */

                        $origen_lead =
                            crm_v3_cliente_taxonomia(
                                $cliente_id,
                                'origen_lead',
                                'origen-lead'
                            );


                        $tipo_cliente =
                            crm_v3_cliente_taxonomia(
                                $cliente_id,
                                'tipo_cliente',
                                'perfil-de-cliente'
                            );


                        $relacion_comercial =
                            crm_v3_cliente_taxonomia(
                                $cliente_id,
                                'relacion_comercial',
                                'relacion-comercial'
                            );


                        $origen_recursos =
                            crm_v3_cliente_taxonomia(
                                $cliente_id,
                                'origen_recursos',
                                'origen-de-recurso'
                            );


                        $tipo_credito =
                            crm_v3_cliente_taxonomia(
                                $cliente_id,
                                'tipo_credito',
                                'tipo-de-creditos'
                            );


                        $presupuesto =
                            get_field(
                                'presupuesto',
                                $cliente_id
                            );


                        $adeudo_hipoteca =
                            get_field(
                                'adeudo_hipoteca',
                                $cliente_id
                            );


                        /*
                         * ESTATUS CLIENTE
                         *
                         * ÚNICA FUENTE:
                         * ACF clientes → estatus
                         */

                        $estatus =
                            get_field(
                                'estatus',
                                $cliente_id
                            );


                        /*
                         * PRIORIDAD
                         */

                        $prioridad =
                            get_field(
                                'prioridad',
                                $cliente_id
                            );


                        /*
                         * IDs PARA FILTROS
                         */

                        $origen_lead_id =
                            get_field(
                                'origen_lead',
                                $cliente_id
                            );


                        if (
                            is_array(
                                $origen_lead_id
                            )
                        ) {

                            $origen_lead_id =
                                reset(
                                    $origen_lead_id
                                );
                        }


                        $tipo_cliente_id =
                            get_field(
                                'tipo_cliente',
                                $cliente_id
                            );


                        $relacion_comercial_id =
                            get_field(
                                'relacion_comercial',
                                $cliente_id
                            );


                        $origen_recursos_id =
                            get_field(
                                'origen_recursos',
                                $cliente_id
                            );


                        $tipo_credito_id =
                            get_field(
                                'tipo_credito',
                                $cliente_id
                            );


                        /*
                         * --------------------------------------
                         * ETIQUETA ACF DEL CLIENTE
                         * --------------------------------------
                         */

                        $crm_estatus_cliente_label =
                            crm_v3_cliente_select_label(
                                $cliente_id,
                                'estatus',
                                $estatus
                            );


                        /*
                         * --------------------------------------
                         * RELACIÓN / OPERACIÓN
                         * --------------------------------------
                         */

                        $crm_tiene_operacion =
                            isset(
                                $crm_clientes_cualquier_operacion[
                                    $cliente_id
                                ]
                            );


                        $crm_tiene_operacion_activa =
                            isset(
                                $crm_clientes_operaciones[
                                    $cliente_id
                                ]
                            );


                        /*
                         * --------------------------------------
                         * ESTATUS OP
                         * --------------------------------------
                         *
                         * Siempre muestra la última operación.
                         *
                         * El valor y la etiqueta provienen
                         * directamente de ACF.
                         */

                        $crm_estatus_op = '—';

                        $crm_estatus_op_raw = '';

                        $crm_estatus_op_class = '';


                        if (
                            isset(
                                $crm_clientes_ultima_operacion[
                                    $cliente_id
                                ]
                            )
                        ) {

                            $crm_operacion_id =
                                $crm_clientes_ultima_operacion[
                                    $cliente_id
                                ]['operacion_id'];


                            $crm_estatus_op_raw =
                                get_field(
                                    'estatus_de_operacion',
                                    $crm_operacion_id
                                );


                            if (
                                is_array(
                                    $crm_estatus_op_raw
                                )
                            ) {

                                $crm_estatus_op_raw =
                                    reset(
                                        $crm_estatus_op_raw
                                    );
                            }


                            if (
                                is_object(
                                    $crm_estatus_op_raw
                                ) &&
                                isset(
                                    $crm_estatus_op_raw->value
                                )
                            ) {

                                $crm_estatus_op_raw =
                                    $crm_estatus_op_raw->value;
                            }


                            if (
                                $crm_estatus_op_raw !== '' &&
                                $crm_estatus_op_raw !== null
                            ) {

                                /*
                                 * Obtener etiqueta directamente
                                 * de ACF.
                                 */

                                $crm_estatus_op =
                                    crm_v3_cliente_select_label(
                                        $crm_operacion_id,
                                        'estatus_de_operacion',
                                        $crm_estatus_op_raw
                                    );


                                $crm_estatus_op_class =
                                    sanitize_html_class(
                                        $crm_estatus_op_raw
                                    );
                            }

                        }


                        /*
                         * --------------------------------------
                         * CLASE DEL ESTATUS CLIENTE
                         * --------------------------------------
                         */

                        $crm_estatus_cliente_class =
                            sanitize_html_class(
                                $estatus
                            );


                        /*
                         * --------------------------------------
                         * CONTADORES
                         * --------------------------------------
                         *
                         * Relacionados:
                         * operación activa.
                         *
                         * Sin relación:
                         * estatus ACF = para_relacionar
                         * y NO existe ninguna operación.
                         */

                        $crm_es_relacionado =
                            $crm_tiene_operacion_activa;


                        $crm_es_sin_relacion =
                            (
                                $estatus ===
                                'para_relacionar'
                                &&
                                !$crm_tiene_operacion
                            );

                        ?>


                        <tr
                            data-tipo="<?php echo esc_attr(
                                strtolower(
                                    $tipo_cliente
                                )
                            ); ?>"

                            data-estatus="<?php echo esc_attr(
                                $estatus
                            ); ?>"

                            data-origen-lead="<?php echo esc_attr(
                                $origen_lead_id
                            ); ?>"

                            data-tipo-cliente="<?php echo esc_attr(
                                $tipo_cliente_id
                            ); ?>"

                            data-relacion-comercial="<?php echo esc_attr(
                                $relacion_comercial_id
                            ); ?>"

                            data-origen-recursos="<?php echo esc_attr(
                                $origen_recursos_id
                            ); ?>"

                            data-tipo-credito="<?php echo esc_attr(
                                $tipo_credito_id
                            ); ?>"

                            data-prioridad="<?php echo esc_attr(
                                $prioridad
                            ); ?>"

                            data-relacionado="<?php echo (
                                $crm_es_relacionado
                            )
                                ? '1'
                                : '0';
                            ?>"

                            data-sin-relacion="<?php echo (
                                $crm_es_sin_relacion
                            )
                                ? '1'
                                : '0';
                            ?>"
                        >


                            <!-- CLIENTE -->

                            <td class="crm-cliente-nombre">

                                <a
                                    href="<?php echo esc_url(
                                        admin_url(
                                            'admin.php?page=crm-ficha-cliente&cliente_id=' .
                                            $cliente_id
                                        )
                                    ); ?>"
                                >

                                    <?php echo esc_html(
                                        get_the_title(
                                            $cliente_id
                                        )
                                    ); ?>

                                </a>

                            </td>


                            <!-- ORIGEN LEAD -->

                            <td>
                                <?php echo esc_html(
                                    $origen_lead
                                ); ?>
                            </td>


                            <!-- TIPO CLIENTE -->

                            <td>
                                <?php echo esc_html(
                                    $tipo_cliente
                                ); ?>
                            </td>


                            <!-- RELACIÓN COMERCIAL -->

                            <td>
                                <?php echo esc_html(
                                    $relacion_comercial
                                ); ?>
                            </td>


                            <!-- ORIGEN RECURSOS -->

                            <td>
                                <?php echo esc_html(
                                    $origen_recursos
                                ); ?>
                            </td>


                            <!-- TIPO CRÉDITO -->

                            <td>
                                <?php echo esc_html(
                                    $tipo_credito
                                ); ?>
                            </td>


                            <!-- PRESUPUESTO -->

                            <td class="crm-monto">

                                <?php

                                echo esc_html(
                                    crm_v3_format_money($presupuesto)
                                );

                                ?>

                            </td>


                            <!-- ADEUDO HIPOTECA -->

                            <td class="crm-monto">

                                <?php

                                echo esc_html(
                                    crm_v3_format_money($adeudo_hipoteca)
                                );

                                ?>

                            </td>


                            <!-- =================================================
                                 ESTATUS CLIENTE
                                 ================================================= -->

                            <td>

                                <?php if (
                                    $crm_estatus_cliente_label !== '—'
                                ) : ?>

                                    <span
                                        class="crm-estatus crm-estatus-<?php echo esc_attr(
                                            $crm_estatus_cliente_class
                                        ); ?>"
                                    >

                                        <?php echo esc_html(
                                            $crm_estatus_cliente_label
                                        ); ?>

                                    </span>

                                <?php else : ?>

                                    —

                                <?php endif; ?>

                            </td>

<!-- =================================================
                  ESTATUS OPERACIÓN
================================================= -->

                            <td>

                                <?php if (
                                    $crm_estatus_op !== '—'
                                ) : ?>

                                    <span
                                        class="crm-estatus crm-estatus-op-<?php echo esc_attr(
                                            $crm_estatus_op_class
                                        ); ?>"
                                    >

                                        <?php echo esc_html(
                                            $crm_estatus_op
                                        ); ?>

                                    </span>

                                <?php else : ?>

                                    —

                                <?php endif; ?>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else : ?>


                    <tr>

                        <td colspan="10">
                            No hay clientes registrados.
                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>


    <?php

    wp_reset_postdata();


    crm_v3_clientes_counters();
}


/**
 * ============================================================
 * TAXONOMÍAS ACF
 * ============================================================
 */

function crm_v3_cliente_taxonomia(
    $post_id,
    $campo,
    $taxonomia
) {

    return crm_v3_nombres_terminos(
        get_field($campo, $post_id),
        $taxonomia
    );
}


/**
 * ============================================================
 * CAMPOS SELECT ACF
 * ============================================================
 *
 * Devuelve exclusivamente la etiqueta definida
 * en las choices de ACF.
 *
 * No crea etiquetas.
 * No modifica etiquetas.
 * ============================================================
 */

function crm_v3_cliente_select_label(
    $post_id,
    $campo,
    $valor
) {

    if (
        $valor === '' ||
        $valor === null ||
        $valor === false
    ) {

        return '—';
    }

    $etiqueta = crm_v3_opcion_acf($campo, $post_id, $valor);

    /*
     * Si ACF no tiene la opción, devolvemos el valor
     * almacenado. No inventamos una etiqueta.
     */
    return $etiqueta !== null
        ? $etiqueta
        : (string) $valor;
}


/**
 * ============================================================
 * CONTADORES Y FILTROS
 * ============================================================
 */

function crm_v3_clientes_counters() {
    ?>

    <script>

    document.addEventListener(
        'DOMContentLoaded',
        function () {


            const rows =
                document.querySelectorAll(
                    '.crm-v3-clientes-table tbody tr[data-tipo]'
                );


            const counters =
                document.querySelectorAll(
                    '.crm-counter'
                );


            const filters =
                document.querySelectorAll(
                    '.crm-filter select'
                );


            /*
             * ==================================================
             * CONTADORES
             * ==================================================
             */

            let total = 0;
            let comprador = 0;
            let vendedor = 0;
            let enProceso = 0;
            let relacionados = 0;
            let sinRelacion = 0;
            let perdidos = 0;

            rows.forEach(
                function (row) {


                    total++;


                    const tipo =
                        row.dataset.tipo;


                    const estatus =
                        row.dataset.estatus;


                    /*
                     * TIPO CLIENTE
                     */

                    if (
                        tipo === 'comprador'
                    ) {

                        comprador++;
                    }


                    if (
                        tipo === 'vendedor'
                    ) {

                        vendedor++;
                    }

                    /*
                    * EN PROCESO
                    *
                    * Directamente desde ACF cliente → estatus.
                    */

                    if (
                    estatus === 'en_proceso'
                    ) {

                    enProceso++;
                    }

                    /*
                     * RELACIONADOS
                     *
                     * Solo operación activa.
                     */

                    if (
                        row.dataset.relacionado === '1'
                    ) {

                        relacionados++;
                    }


                    /*
                     * SIN RELACIÓN
                     *
                     * ACF cliente:
                     * para_relacionar
                     *
                     * Y ninguna operación histórica.
                     */

                    if (
                        row.dataset.sinRelacion === '1'
                    ) {

                        sinRelacion++;
                    }


                    /*
                     * PERDIDOS
                     *
                     * Directamente desde ACF cliente.
                     */

                    if (
                        estatus === 'perdido'
                    ) {

                        perdidos++;
                    }

                }
            );


            /*
             * ==================================================
             * MOSTRAR CONTADORES
             * ==================================================
             */

            document.getElementById(
                'crm-count-total'
            ).textContent = total;


            document.getElementById(
                'crm-count-comprador'
            ).textContent = comprador;


            document.getElementById(
                'crm-count-vendedor'
            ).textContent = vendedor;

            document.getElementById(
                'crm-count-en-proceso'
            ).textContent = enProceso;
            


            document.getElementById(
                'crm-count-relacionados'
            ).textContent = relacionados;


            document.getElementById(
                'crm-count-sin-relacion'
            ).textContent = sinRelacion;


            document.getElementById(
                'crm-count-perdidos'
            ).textContent = perdidos;


            /*
             * ==================================================
             * FILTROS POR CONTADOR
             * ==================================================
             */

            counters.forEach(
                function (counter) {


                    counter.addEventListener(
                        'click',
                        function () {


                            const filter =
                                this.dataset.filter;


                            counters.forEach(
                                function (item) {

                                    item.classList.remove(
                                        'active'
                                    );

                                }
                            );


                            this.classList.add(
                                'active'
                            );


                            rows.forEach(
                                function (row) {


                                    const tipo =
                                        row.dataset.tipo;


                                    const estatus =
                                        row.dataset.estatus;


                                    let mostrar =
                                        false;


                                    /*
                                     * TODOS
                                     */

                                    if (
                                        filter ===
                                        'todos'
                                    ) {

                                        mostrar =
                                            true;
                                    }


                                    /*
                                     * COMPRADOR
                                     */

                                    if (
                                        filter ===
                                        'comprador' &&
                                        tipo ===
                                        'comprador'
                                    ) {

                                        mostrar =
                                            true;
                                    }


                                    /*
                                     * VENDEDOR
                                     */

                                    if (
                                        filter ===
                                        'vendedor' &&
                                        tipo ===
                                        'vendedor'
                                    ) {

                                        mostrar =
                                            true;
                                    }

                                   /*
                                    * EN PROCESO
                                    */

                                    if (
                                        filter ===
                                        'en_proceso' &&
                                        estatus ===
                                        'en_proceso'
                                    ) {

                                        mostrar =
                                            true;
                                    }


                                    /*
                                    * RELACIONADOS
                                    */

                                    if (
                                        filter ===
                                        'relacionados' &&
                                        row.dataset.relacionado ===
                                        '1'
                                    ) {

                                        mostrar =
                                            true;
                                    }


                                    /*
                                     * SIN RELACIÓN
                                     */

                                    if (
                                        filter ===
                                        'sin_relacion' &&
                                        row.dataset.sinRelacion ===
                                        '1'
                                    ) {

                                        mostrar =
                                            true;
                                    }


                                    /*
                                     * PERDIDOS
                                     */

                                    if (
                                        filter ===
                                        'perdido' &&
                                        estatus ===
                                        'perdido'
                                    ) {

                                        mostrar =
                                            true;
                                    }


                                    row.style.display =
                                        mostrar
                                            ? ''
                                            : 'none';

                                }
                            );

                        }
                    );

                }
            );


            /*
             * ==================================================
             * FILTROS DESPLEGABLES
             * ==================================================
             */

            filters.forEach(
                function (filter) {


                    filter.addEventListener(
                        'change',
                        function () {


                            rows.forEach(
                                function (row) {


                                    let mostrar =
                                        true;


                                    filters.forEach(
                                        function (filtro) {


                                            const campo =
                                                filtro.dataset.filterField;


                                            const valor =
                                                filtro.value;


                                            if (!valor) {
                                                return;
                                            }


                                            const atributo =
                                                'data-' +
                                                campo.replace(
                                                    /_/g,
                                                    '-'
                                                );


                                            const valorFila =
                                                row.getAttribute(
                                                    atributo
                                                );


                                            if (
                                                String(
                                                    valorFila
                                                ) !==
                                                String(
                                                    valor
                                                )
                                            ) {

                                                mostrar =
                                                    false;

                                            }

                                        }
                                    );


                                    row.style.display =
                                        mostrar
                                            ? ''
                                            : 'none';

                                }
                            );

                        }
                    );

                }
            );


        }
    );

    </script>

    <?php
}