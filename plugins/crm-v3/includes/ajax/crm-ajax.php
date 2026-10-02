<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * ============================================================
 * CRM V3 — AJAX OPERACIONES
 * ============================================================
 */

add_action(
    'wp_ajax_crm_v3_operaciones_filtrar',
    'crm_v3_ajax_operaciones_filtrar'
);


function crm_v3_ajax_operaciones_filtrar() {

    check_ajax_referer(
        'crm_v3_operaciones_nonce',
        'nonce'
    );

    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Sin permisos.'
        ]);
    }

    $filtros = [
        'tipo_propiedad' => isset($_POST['tipo_propiedad'])
            ? sanitize_text_field($_POST['tipo_propiedad'])
            : '',

        'expediente' => isset($_POST['expediente'])
            ? sanitize_key($_POST['expediente'])
            : '',

        'tipo_avaluo' => isset($_POST['tipo_avaluo'])
            ? sanitize_key($_POST['tipo_avaluo'])
            : '',

        'institucion' => isset($_POST['institucion'])
            ? absint($_POST['institucion'])
            : 0,

        'notaria' => isset($_POST['notaria'])
            ? absint($_POST['notaria'])
            : 0,

        'estatus' => isset($_POST['estatus'])
            ? sanitize_key($_POST['estatus'])
            : '',

        'contador' => isset($_POST['contador'])
            ? sanitize_key($_POST['contador'])
            : '',    
    ];

    $operaciones = crm_v3_operaciones_obtener_filtradas(
        $filtros
    );

    if (!function_exists('crm_v3_operaciones_render_table')) {
        wp_send_json_error([
            'message' => 'El renderizador de operaciones no está disponible.'
        ]);
    }

    $html = crm_v3_operaciones_render_table(
        $operaciones
    );

    wp_send_json_success([
        'html'  => $html,
        'total' => count($operaciones),
    ]);
}



/**
 * ============================================================
 * CRM V3 — AJAX PROPIEDADES
 * ============================================================
 */


add_action(
    'wp_ajax_crm_v3_propiedades_filtrar',
    'crm_v3_ajax_propiedades_filtrar'
);


function crm_v3_ajax_propiedades_filtrar() {

    check_ajax_referer(
        'crm_v3_propiedades_nonce',
        'nonce'
    );

    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Sin permisos.'
        ]);
    }

    $filtros = [
        'colonia' => isset($_POST['colonia'])
            ? absint($_POST['colonia'])
            : 0,

        'ciudad' => isset($_POST['ciudad'])
            ? absint($_POST['ciudad'])
            : 0,

        'tipo_propiedad' => isset($_POST['tipo_propiedad'])
            ? absint($_POST['tipo_propiedad'])
            : 0,

        'tipo_operacion' => isset($_POST['tipo_operacion'])
            ? sanitize_key($_POST['tipo_operacion'])
            : '',

        'estado_inmueble' => isset($_POST['estado_inmueble'])
            ? sanitize_key($_POST['estado_inmueble'])
            : '',

        'estado_comercial' => isset($_POST['estado_comercial'])
            ? sanitize_key($_POST['estado_comercial'])
            : '',

        'documentacion' => isset($_POST['documentacion'])
            ? sanitize_key($_POST['documentacion'])
            : '',

        'contador' => isset($_POST['contador'])
            ? sanitize_key($_POST['contador'])
            : '',
    ];

    $propiedades = crm_v3_propiedades_obtener_filtradas($filtros);

$html = crm_v3_propiedades_render_table($propiedades);

wp_send_json_success([
    'html'  => $html,
    'total' => $propiedades->found_posts,
]);

}



/**
 * ============================================================
 * BACKOFFICE — CLIENTES POR ESTATUS
 * ============================================================
 */

add_action(
    'wp_ajax_crm_v3_backoffice_clientes_estatus',
    'crm_v3_ajax_backoffice_clientes_estatus'
);

function crm_v3_ajax_backoffice_clientes_estatus() {

    check_ajax_referer(
        'crm_v3_backoffice_nonce',
        'nonce'
    );

    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Sin permisos.'
        ]);
    }

    $estatus = isset($_POST['estatus'])
        ? sanitize_key($_POST['estatus'])
        : '';

    $estatus_validos = [
        'en_proceso',
        'para_relacionar',
        'perdido',
    ];

    if (!in_array($estatus, $estatus_validos, true)) {
        wp_send_json_error([
            'message' => 'Estatus no válido.'
        ]);
    }

    $clientes = get_posts([
        'post_type'      => 'clientes',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [
            [
                'key'     => 'estatus',
                'value'   => $estatus,
                'compare' => '=',
            ],
        ],
    ]);

    $options = '<option value="">Seleccionar cliente</option>';

    foreach ($clientes as $cliente) {

        $options .= sprintf(
            '<option value="%d">%s</option>',
            (int) $cliente->ID,
            esc_html(get_the_title($cliente->ID))
        );
    }

    wp_send_json_success([
        'options' => $options,
        'total'   => count($clientes),
    ]);
}


/**
 * ============================================================
 * BACKOFFICE — TIPOS DE PROPIEDAD POR COLONIA
 * ============================================================
 */

add_action(
    'wp_ajax_crm_v3_backoffice_mercado_tipos',
    'crm_v3_ajax_backoffice_mercado_tipos'
);


function crm_v3_ajax_backoffice_mercado_tipos() {

    check_ajax_referer(
        'crm_v3_backoffice_nonce',
        'nonce'
    );

    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Sin permisos.'
        ]);
    }


    $colonia_id = isset($_POST['colonia_id'])
        ? absint($_POST['colonia_id'])
        : 0;


    if (!$colonia_id) {
        wp_send_json_error([
            'message' => 'Colonia no válida.'
        ]);
    }


    $propiedades = get_posts([
        'post_type'      => 'propiedades',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',

        'tax_query'      => [
            [
                'taxonomy' => 'fraccionamiento-o-colonia',
                'field'    => 'term_id',
                'terms'    => $colonia_id,
            ],
        ],
    ]);


    $tipos = [];


    foreach ($propiedades as $propiedad_id) {

        $terminos = wp_get_post_terms(
            $propiedad_id,
            'tipo-de-propiedad'
        );


        if (
            is_wp_error($terminos) ||
            empty($terminos)
        ) {
            continue;
        }


        foreach ($terminos as $termino) {

            $tipos[$termino->term_id] = [
                'id'   => $termino->term_id,
                'name' => $termino->name,
            ];
        }
    }


    if (!empty($tipos)) {

        usort(
            $tipos,
            function ($a, $b) {
                return strcasecmp(
                    $a['name'],
                    $b['name']
                );
            }
        );
    }


    wp_send_json_success([
        'tipos' => array_values($tipos),
    ]);
}



/**
 * ============================================================
 * BACKOFFICE — PROPIEDADES COMPARABLES DE MERCADO
 * ============================================================
 */

add_action(
    'wp_ajax_crm_v3_backoffice_mercado_propiedades',
    'crm_v3_ajax_backoffice_mercado_propiedades'
);


function crm_v3_ajax_backoffice_mercado_propiedades() {

    check_ajax_referer(
        'crm_v3_backoffice_nonce',
        'nonce'
    );


    if (!current_user_can('manage_options')) {

        wp_send_json_error([
            'message' => 'Sin permisos.'
        ]);

    }


    $colonia_id = isset($_POST['colonia_id'])
        ? absint($_POST['colonia_id'])
        : 0;


    $tipo_id = isset($_POST['tipo_id'])
        ? absint($_POST['tipo_id'])
        : 0;


    if (
        !$colonia_id ||
        !$tipo_id
    ) {

        wp_send_json_error([
            'message' => 'Filtros de mercado incompletos.'
        ]);

    }


    /*
     * =========================================================
     * PROPIEDADES COMPARABLES
     *
     * Máximo 5.
     * Las más recientes primero.
     * =========================================================
     */

    $propiedades = get_posts([

        'post_type'      => 'propiedades',

        'post_status'    => 'publish',

        'posts_per_page' => 5,

        'orderby'        => 'date',

        'order'          => 'DESC',

        'tax_query'      => [

            'relation' => 'AND',

            [
                'taxonomy' => 'fraccionamiento-o-colonia',
                'field'    => 'term_id',
                'terms'    => $colonia_id,
            ],

            [
                'taxonomy' => 'tipo-de-propiedad',
                'field'    => 'term_id',
                'terms'    => $tipo_id,
            ],

        ],

    ]);


    $comparables = [];


    foreach (
        $propiedades as $propiedad
    ) {

        $propiedad_id =
            $propiedad->ID;


        /*
         * =====================================================
         * DATOS DE LA PROPIEDAD
         * =====================================================
         */

        $valor_catastral =
            get_field(
                'valor_catastral',
                $propiedad_id
            );


        $precio_venta =
            get_field(
                'precio_de_venta',
                $propiedad_id
            );


        $m2_terreno =
            get_field(
                'm2_terreno',
                $propiedad_id
            );


        $m2_construccion =
            get_field(
                'm2_construccion',
                $propiedad_id
            );


        $estatus =
            get_field(
                'estatus',
                $propiedad_id
            );


        /*
         * =====================================================
         * ÚLTIMA OPERACIÓN RELACIONADA
         *
         * Se utilizará para obtener valor de mercado
         * cuando exista una operación.
         * =====================================================
         */

        $operaciones =
            get_posts([

                'post_type'      => 'operaciones',

                'post_status'    => 'publish',

                'posts_per_page' => 1,

                'orderby'        => 'date',

                'order'          => 'DESC',

                'meta_query'     => [

                    [
                        'key'     => 'propiedad',
                        'value'   => $propiedad_id,
                        'compare' => '=',
                    ],

                ],

            ]);


        $valor_mercado = null;


        if (!empty($operaciones)) {

            $valor_mercado =
                get_field(
                    'valor_mercado',
                    $operaciones[0]->ID
                );

        }


        /*
         * =====================================================
         * DATOS PARA AJAX
         * =====================================================
         */

        $comparables[] = [

            'id' =>
                $propiedad_id,

            'nombre' =>
                get_the_title(
                    $propiedad_id
                ),

            'fecha' =>
                get_the_date(
                    'Y-m-d',
                    $propiedad_id
                ),

            'm2_terreno' =>
                $m2_terreno,

            'm2_construccion' =>
                $m2_construccion,

            'valor_catastral' =>
                $valor_catastral,

            'precio_venta' =>
                $precio_venta,

            'valor_mercado' =>
                $valor_mercado,

            'estatus' =>
                crm_v3_display_value(
                    $estatus
                ),

        ];

    }


    wp_send_json_success([

        'total' =>
            count($comparables),

        'propiedades' =>
            $comparables,

    ]);

}



/**
 * ============================================================
 * BACKOFFICE — RESUMEN DE CLIENTE
 * ============================================================
 */

add_action(
    'wp_ajax_crm_v3_backoffice_cliente_resumen',
    'crm_v3_ajax_backoffice_cliente_resumen'
);

function crm_v3_ajax_backoffice_cliente_resumen() {

    check_ajax_referer(
        'crm_v3_backoffice_nonce',
        'nonce'
    );

    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Sin permisos.'
        ]);
    }

    $cliente_id = isset($_POST['cliente_id'])
        ? absint($_POST['cliente_id'])
        : 0;

    if (!$cliente_id) {
        wp_send_json_error([
            'message' => 'Cliente no válido.'
        ]);
    }

    $cliente = get_post($cliente_id);

    if (
        !$cliente ||
        $cliente->post_type !== 'clientes'
    ) {
        wp_send_json_error([
            'message' => 'El cliente no existe.'
        ]);
    }

    $tipo_cliente = get_field(
        'tipo_cliente',
        $cliente_id
    );

    $estatus = get_field(
        'estatus',
        $cliente_id
    );

    $prioridad = get_field(
        'prioridad',
        $cliente_id
    );

    $presupuesto = get_field(
        'presupuesto',
        $cliente_id
    );

    $adeudo_hipoteca = get_field(
        'adeudo_hipoteca',
        $cliente_id
    );

    $origen_lead = crm_v3_display_value(
        get_field('origen_lead', $cliente_id)
    );

    $relacion_comercial = crm_v3_display_value(
        get_field('relacion_comercial', $cliente_id)
    );

    $origen_recursos = crm_v3_display_value(
        get_field('origen_recursos', $cliente_id)
    );

    $tipo_credito = crm_v3_display_value(
        get_field('tipo_credito', $cliente_id)
    );


    /*
     * =========================================================
     * PROPIEDADES DEL CLIENTE
     * =========================================================
     */

    $propiedades_cliente = get_posts([
        'post_type'      => 'propiedades',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [
            [
                'key'     => 'cliente_propietario',
                'value'   => $cliente_id,
                'compare' => '=',
            ],
        ],
    ]);


    $propiedades = [];

    foreach ($propiedades_cliente as $propiedad) {

        $propiedad_id = $propiedad->ID;

        $tipo_propiedad = wp_get_post_terms(
            $propiedad_id,
            'tipo-de-propiedad',
            [
                'fields' => 'names',
            ]
        );

        $colonia = wp_get_post_terms(
            $propiedad_id,
            'fraccionamiento-o-colonia',
            [
                'fields' => 'names',
            ]
        );

        $ciudad = wp_get_post_terms(
            $propiedad_id,
            'ciudad',
            [
                'fields' => 'names',
            ]
        );

        $propiedades[] = [
            'id'              => $propiedad_id,
            'nombre'          => get_the_title($propiedad_id),
            'tipo'            => !empty($tipo_propiedad)
                ? implode(', ', $tipo_propiedad)
                : '—',
            'colonia'         => !empty($colonia)
                ? implode(', ', $colonia)
                : '—',
            'ciudad'          => !empty($ciudad)
                ? implode(', ', $ciudad)
                : '—',
            'precio_venta'    => crm_v3_format_money(
                get_field('precio_de_venta', $propiedad_id)
            ),
            'estatus'         => crm_v3_display_value(
                get_field('estatus', $propiedad_id)
            ),
            'm2_terreno'      => crm_v3_display_value(
                get_field('m2_terreno', $propiedad_id)
            ),
            'm2_construccion' => crm_v3_display_value(
                get_field('m2_construccion', $propiedad_id)
            ),
        ];
    }


    /*
     * =========================================================
     * OPERACIONES DEL CLIENTE
     * =========================================================
     */

    $operaciones_cliente = get_posts([
        'post_type'      => 'operaciones',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [
            [
                'key'     => 'comprador',
                'value'   => $cliente_id,
                'compare' => '=',
            ],
        ],
    ]);


    /*
     * Operaciones relacionadas con propiedades
     * donde el cliente es propietario.
     */

    $propiedad_ids = wp_list_pluck(
        $propiedades_cliente,
        'ID'
    );

    if (!empty($propiedad_ids)) {

        $operaciones_vendedor = get_posts([
            'post_type'      => 'operaciones',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [
                [
                    'key'     => 'propiedad',
                    'value'   => $propiedad_ids,
                    'compare' => 'IN',
                ],
            ],
        ]);

        $operaciones_cliente = array_merge(
            $operaciones_cliente,
            $operaciones_vendedor
        );
    }


    /*
     * Eliminar operaciones duplicadas.
     */

    $operaciones_cliente = array_values(
        array_unique(
            $operaciones_cliente,
            SORT_REGULAR
        )
    );


    $operaciones = [];

    foreach ($operaciones_cliente as $operacion) {

        $operacion_id = $operacion->ID;

        $propiedad_relacionada = get_field(
            'propiedad',
            $operacion_id
        );

        $propiedad_id = crm_v3_get_related_id(
            $propiedad_relacionada
        );
        
        $comprador_relacionado = get_field(
            'comprador',
            $operacion_id
        );

        $comprador_id = crm_v3_get_related_id(
            $comprador_relacionado
        );



        $operaciones[] = [
            'id' => $operacion_id,

            'nombre' => get_the_title(
                $operacion_id
            ),

            'propiedad' => $propiedad_id
                ? get_the_title($propiedad_id)
                : '—',
            
                'comprador' => $comprador_id
                ? get_the_title($comprador_id)
                : '—',    

            'estatus' => crm_v3_display_value(
                get_field(
                    'estatus_de_operecion',
                    $operacion_id
                )
            ),

            'valor_mercado' => crm_v3_format_money(
                get_field(
                    'valor_mercado',
                    $operacion_id
                )
            ),

            'precio_cierre' => crm_v3_format_money(
                get_field(
                    'precio_de_cierre',
                    $operacion_id
                )
            ),

            'fecha_cierre' => crm_v3_format_date(
                get_field(
                    'fecha_cierre',
                    $operacion_id
                )
            ),
            
        ];
    }


    wp_send_json_success([
        'cliente' => [
            'id'                  => $cliente_id,
            'nombre'              => get_the_title($cliente_id),
            'tipo'                => crm_v3_display_value($tipo_cliente),
            'estatus'             => crm_v3_display_value($estatus),
            'prioridad'           => crm_v3_display_value($prioridad),
            'origen_lead'         => $origen_lead,
            'relacion_comercial'  => $relacion_comercial,
            'origen_recursos'     => $origen_recursos,
            'tipo_credito'        => $tipo_credito,
            'presupuesto'         => crm_v3_format_money($presupuesto),
            'adeudo_hipoteca'     => crm_v3_format_money($adeudo_hipoteca),
        ],

        'propiedades' => $propiedades,

        'operaciones' => $operaciones,

    ]);
}
