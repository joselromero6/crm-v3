<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ============================================================
 * CRM V3 - LEADS
 * ============================================================
 * Fuente de datos:
 * - WordPress
 * - ACF
 * - Taxonomías configuradas en ACF
 *
 * No se duplican opciones de ACF en este archivo.
 * ============================================================
 */


/**
 * ============================================================
 * GUARDAR LEAD
 * ============================================================
 */

function crm_v3_guardar_lead() {

    if (!isset($_POST['crm_v3_lead_nonce'])) {
        return;
    }

    if (
        !wp_verify_nonce(
            sanitize_text_field(
                wp_unslash($_POST['crm_v3_lead_nonce'])
            ),
            'crm_v3_guardar_lead'
        )
    ) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }


    /*
     * --------------------------------------------------------
     * Nombre
     * --------------------------------------------------------
     */

    $nombre = isset($_POST['lead_nombre'])
        ? sanitize_text_field(
            wp_unslash($_POST['lead_nombre'])
        )
        : '';


    if ($nombre === '') {

        wp_safe_redirect(
            add_query_arg(
                'crm_lead_error',
                'nombre',
                admin_url('admin.php?page=crm-leads')
            )
        );

        exit;
    }


    /*
     * --------------------------------------------------------
     * Crear Lead
     * --------------------------------------------------------
     */

    $lead_id = wp_insert_post(
        [
            'post_type'   => 'leads',
            'post_title'  => $nombre,
            'post_status' => 'publish',
        ]
    );


    if (is_wp_error($lead_id) || !$lead_id) {

        wp_safe_redirect(
            add_query_arg(
                'crm_lead_error',
                'guardar',
                admin_url('admin.php?page=crm-leads')
            )
        );

        exit;
    }


    /*
     * --------------------------------------------------------
     * Guardar campos ACF
     * --------------------------------------------------------
     */

    $campos = [
        'origen_captacion',
        'telefono',
        'e-mail',
        'tipo_de_lead',
        'zona_de_compra',
        'nss',
        'curp',
        'rfc',
        'institucion_crediticia',
        'monto_de_credito',
        'estatus',
        'notas',
    ];


    foreach ($campos as $campo) {

        if (!isset($_POST[$campo])) {
            continue;
        }


        $valor = wp_unslash($_POST[$campo]);


        /*
         * Campos numéricos
         */

        if (
            $campo === 'telefono' ||
            $campo === 'nss'
        ) {

            /*
             * Se conservan solo los dígitos: "33 1234-5678"
             * se guarda como 3312345678 en lugar de perderse.
             */
            $valor = preg_replace(
                '/\D+/',
                '',
                (string) $valor
            );
        }

        elseif ($campo === 'monto_de_credito') {

            $valor = str_replace(
                array(',', '$', ' '),
                '',
                (string) $valor
            );

            $valor = is_numeric($valor)
                ? $valor
                : '';
        }


        /*
         * Taxonomías
         */

        elseif (
            $campo === 'origen_captacion' ||
            $campo === 'institucion_crediticia'
        ) {

            $valor = absint($valor);
        }


        /*
         * E-mail
         */

        elseif ($campo === 'e-mail') {

            $valor = sanitize_email($valor);
        }


        /*
         * Notas
         */

        elseif ($campo === 'notas') {

            $valor = sanitize_textarea_field($valor);
        }


        /*
         * Texto / Select
         */

        else {

            $valor = sanitize_text_field($valor);
        }


        update_field(
            $campo,
            $valor,
            $lead_id
        );
    }


    /*
     * --------------------------------------------------------
     * Regresar al CRM
     * --------------------------------------------------------
     */

    set_transient(
    'crm_lead_guardado_' . get_current_user_id(),
    true,
    30
    );

    wp_safe_redirect(
        admin_url('admin.php?page=crm-leads')
    );

    exit;
    }


add_action(
    'admin_post_crm_v3_guardar_lead',
    'crm_v3_guardar_lead'
);

/**
 * ============================================================
 * GUARDAR EL COMENTARIO DEL LEAD
 * ============================================================
 *
 * Sirve tanto para escribir el comentario por primera vez
 * como para editarlo: siempre es el mismo comentario.
 */

function crm_v3_guardar_nota_lead() {

    if (!current_user_can('manage_options')) {
        wp_send_json_error(
            [
                'message' => 'No tienes permisos.'
            ],
            403
        );
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(
                wp_unslash($_POST['nonce'])
            ),
            'crm_v3_lead_notas'
        )
    ) {
        wp_send_json_error(
            [
                'message' => 'Solicitud no válida.'
            ],
            403
        );
    }

    $lead_id = isset($_POST['lead_id'])
        ? absint($_POST['lead_id'])
        : 0;

    $nota = isset($_POST['nota'])
        ? sanitize_textarea_field(
            wp_unslash($_POST['nota'])
        )
        : '';

    if (!$lead_id || get_post_type($lead_id) !== 'leads') {
        wp_send_json_error(
            [
                'message' => 'Lead no válido.'
            ],
            400
        );
    }

    if (trim($nota) === '') {
        wp_send_json_error(
            [
                'message' => 'El comentario no puede estar vacío.'
            ],
            400
        );
    }

    /*
     * El comentario vive en el campo ACF "notas".
     */
    update_field(
        'notas',
        $nota,
        $lead_id
    );

    /*
     * El historial se conserva por compatibilidad: su primera
     * entrada siempre refleja el comentario, sin crear nuevas.
     */
    $historial = get_post_meta(
        $lead_id,
        '_crm_lead_notas_historial',
        true
    );

    if (!is_array($historial)) {
        $historial = [];
    }

    if (empty($historial)) {

        $historial[] = [
            'id'      => wp_generate_uuid4(),
            'fecha'   => current_time('mysql'),
            'usuario' => get_current_user_id(),
            'nota'    => $nota,
        ];

    } else {

        $historial[0]['nota'] = $nota;

        if (empty($historial[0]['id'])) {
            $historial[0]['id'] = wp_generate_uuid4();
        }
    }

    update_post_meta(
        $lead_id,
        '_crm_lead_notas_historial',
        $historial
    );

    wp_send_json_success(
        [
            'nota' => $nota,
        ]
    );
}

add_action(
    'wp_ajax_crm_v3_agregar_nota_lead',
    'crm_v3_guardar_nota_lead'
);

add_action(
    'wp_ajax_crm_v3_editar_nota_lead',
    'crm_v3_guardar_nota_lead'
);




/**
 * ============================================================
 * OBTENER TÉRMINOS DE TAXONOMÍA
 * ============================================================
 */

function crm_v3_leads_get_terms($taxonomy) {

    $terms = get_terms(
        [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ]
    );


    if (is_wp_error($terms)) {
        return [];
    }


    return $terms;
}


/**
 * ============================================================
 * MOSTRAR NOMBRE DE TAXONOMÍA
 * ============================================================
 */

function crm_v3_leads_term_name($value, $taxonomy) {

    // En leads solo se muestra el primer término.
    if (is_array($value)) {
        $value = reset($value);
    }

    return esc_html(
        crm_v3_nombres_terminos($value, $taxonomy)
    );
}


/**
 * ============================================================
 * OBTENER LABEL DE SELECT DESDE ACF
 * ============================================================
 */

function crm_v3_leads_acf_choice_label($field_name, $value, $post_id) {

    if ($value === '' || $value === null) {
        return '—';
    }

    $etiqueta = crm_v3_opcion_acf($field_name, $post_id, $value);

    return esc_html(
        $etiqueta !== null ? $etiqueta : $value
    );
}


/**
 * ============================================================
 * PÁGINA CRM LEADS
 * ============================================================
 */

function crm_v3_leads_page() {

    if (!current_user_can('manage_options')) {

        wp_die(
            'No tienes permisos para acceder a esta sección.'
        );
    }


   /*
 * --------------------------------------------------------
 * Filtros
 * --------------------------------------------------------
 */

$filtro_origen = isset($_GET['origen'])
    ? absint($_GET['origen'])
    : '';

$filtro_tipo = isset($_GET['tipo_de_lead'])
    ? sanitize_text_field(
        wp_unslash($_GET['tipo_de_lead'])
    )
    : '';

$filtro_institucion = isset($_GET['institucion'])
    ? absint($_GET['institucion'])
    : '';

$filtro_estatus = isset($_GET['estatus'])
    ? sanitize_text_field(
        wp_unslash($_GET['estatus'])
    )
    : '';

$filtro_zona = isset($_GET['zona'])
    ? sanitize_text_field(
        wp_unslash($_GET['zona'])
    )
    : '';

$filtro_busqueda = isset($_GET['lead_busqueda'])
    ? sanitize_text_field(
        wp_unslash($_GET['lead_busqueda'])
    )
    : '';


/*
 * ============================================================
 * CONSULTA CRM LEADS
 * ============================================================
 */

$args_leads = [
    'post_type'      => 'leads',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
];

$meta_query = [];
$tax_query  = [];


/*
 * Tipo de Lead — ACF SELECT
 */

if ($filtro_tipo !== '') {

    $meta_query[] = [
        'key'     => 'tipo_de_lead',
        'value'   => $filtro_tipo,
        'compare' => '=',
    ];
}


/*
 * Estatus — ACF SELECT
 */

if ($filtro_estatus !== '') {

    $meta_query[] = [
        'key'     => 'estatus',
        'value'   => $filtro_estatus,
        'compare' => '=',
    ];
}


/*
 * Zona — ACF TEXT
 */

if ($filtro_zona !== '') {

    $meta_query[] = [
        'key'     => 'zona_de_compra',
        'value'   => $filtro_zona,
        'compare' => 'LIKE',
    ];
}


if (!empty($meta_query)) {
    $args_leads['meta_query'] = $meta_query;
}


if (!empty($tax_query)) {
    $args_leads['tax_query'] = $tax_query;
}


/*
 * Buscar por nombre
 */

if ($filtro_busqueda !== '') {

    $args_leads['s'] = $filtro_busqueda;
}


$leads = get_posts($args_leads);


/*
 * Origen e Institución — campos de taxonomía de ACF.
 *
 * Estos campos guardan el término en el propio Lead (no como
 * taxonomía de WordPress), así que se filtra por el valor del campo.
 */

if ($filtro_origen || $filtro_institucion) {

    $leads = array_values(
        array_filter(
            $leads,
            function ($lead) use ($filtro_origen, $filtro_institucion) {

                if (
                    $filtro_origen &&
                    !crm_v3_valor_incluye_termino(
                        get_field('origen_captacion', $lead->ID),
                        $filtro_origen
                    )
                ) {
                    return false;
                }

                if (
                    $filtro_institucion &&
                    !crm_v3_valor_incluye_termino(
                        get_field('institucion_crediticia', $lead->ID),
                        $filtro_institucion
                    )
                ) {
                    return false;
                }

                return true;
            }
        )
    );
}

    /*
     * --------------------------------------------------------
     * Taxonomías configuradas en ACF
     * --------------------------------------------------------
     */

    $origenes = crm_v3_leads_get_terms(
        'origen-lead'
    );


    $instituciones = crm_v3_leads_get_terms(
        'tipo-de-creditos'
    );


    /*
     * --------------------------------------------------------
     * Campos Select ACF
     *
     * Keys tomadas directamente de la configuración ACF
     * --------------------------------------------------------
     */

    $campo_tipo_lead = acf_get_field(
        'field_6a85fc6c7de27'
    );


    $campo_estatus = acf_get_field(
        'field_6a8600e4158d0'
    );

    /*
     * Si ACF no encuentra el campo, se sigue con una lista vacía
     * en lugar de lanzar avisos en los recorridos de opciones.
     */
    if (!is_array($campo_tipo_lead) || empty($campo_tipo_lead['choices'])) {
        $campo_tipo_lead = array('choices' => array());
    }

    if (!is_array($campo_estatus) || empty($campo_estatus['choices'])) {
        $campo_estatus = array('choices' => array());
    }

/* ============================================================
 * VALORES REALMENTE CAPTURADOS PARA LOS FILTROS
 * ============================================================ */

$leads_filtro = get_posts([
    'post_type'      => 'leads',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
]);

$origenes_filtro = [];
$instituciones_filtro = [];
$tipos_filtro = [];
$estatus_filtro = [];

foreach ($leads_filtro as $lead_id) {

    $origen = get_field(
        'origen_captacion',
        $lead_id
    );

    if (is_object($origen) && isset($origen->term_id)) {

        $origenes_filtro[$origen->term_id] = true;

    } elseif (is_array($origen)) {

        foreach ($origen as $term) {

            if (
                is_object($term) &&
                isset($term->term_id)
            ) {
                $origenes_filtro[$term->term_id] = true;
            } else {
                $origenes_filtro[absint($term)] = true;
            }
        }

    } elseif (!empty($origen)) {

        $origenes_filtro[absint($origen)] = true;
    }

    $institucion = get_field(
        'institucion_crediticia',
        $lead_id
    );

    if (is_object($institucion) && isset($institucion->term_id)) {

        $instituciones_filtro[$institucion->term_id] = true;

    } elseif (is_array($institucion)) {

        foreach ($institucion as $term) {

            if (
                is_object($term) &&
                isset($term->term_id)
            ) {
                $instituciones_filtro[$term->term_id] = true;
            } else {
                $instituciones_filtro[absint($term)] = true;
            }
        }

    } elseif (!empty($institucion)) {

        $instituciones_filtro[absint($institucion)] = true;
    }


    $tipo = get_field(
    'tipo_de_lead',
    $lead_id
);

if ($tipo !== '' && $tipo !== null) {

    $tipos_filtro[$tipo] = true;
}

$estatus = get_field(
    'estatus',
    $lead_id
);

if ($estatus !== '' && $estatus !== null) {

    $estatus_filtro[$estatus] = true;
}



}


?>
<!-- ==================================================
             MENSAJES
================================================== -->

        <?php

$lead_guardado = get_transient(
    'crm_lead_guardado_' . get_current_user_id()
);

if ($lead_guardado) {
    delete_transient(
        'crm_lead_guardado_' . get_current_user_id()
    );
}

?>

<?php if ($lead_guardado) : ?>

    <div class="crm-lead-save-notice">

        <p>
            <strong>
                Lead creado correctamente.
            </strong>
        </p>

    </div>

<?php endif; ?>

<div class="wrap crm-v3-leads">


        <!-- ==================================================
             HEADER
        ================================================== -->

        <div class="crm-leads-header">

            <div class="crm-leads-header-content">

                <div>

                    <h1>LEADS CRM INMOBILIARIO</h1>
                    <p>Gestión y seguimiento de prospectos.</p>

                </div>
                 
                <div class="crm-leads-header-actions">
                <a href="admin.php?page=crm-v3-cibr" class="crm-leads-btn">
                    + Backoffice
                </a>
                </div>

            </div>

        </div>





        <?php if (isset($_GET['crm_lead_error'])) : ?>

            <div class="notice notice-error is-dismissible">

                <p>
                    No fue posible crear el Lead.
                    Verifica los datos.
                </p>

            </div>

        <?php endif; ?>


<!-- ==================================================
     CONTADORES
================================================== -->

<div class="crm-v3-leads-counters">


    <!-- TODOS -->

    <a
        href="<?php echo esc_url(
            admin_url('admin.php?page=crm-leads')
        ); ?>"
        class="crm-lead-counter <?php echo (
            $filtro_estatus === '' &&
            $filtro_tipo === '' &&
            $filtro_origen === '' &&
            $filtro_institucion === '' &&
            $filtro_zona === '' &&
            $filtro_busqueda === ''
        ) ? 'is-active' : ''; ?>"
    >

        <span>Todos</span>

        <strong>
            <?php
            echo esc_html(
                count(
                    get_posts([
                        'post_type'      => 'leads',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'fields'         => 'ids',
                    ])
                )
            );
            ?>
        </strong>

    </a>


    <?php foreach (
        $campo_estatus['choices']
        as $status_value => $status_label
    ) : ?>


        <?php

        $status_count = count(
            get_posts([
                'post_type'      => 'leads',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => [
                    [
                        'key'     => 'estatus',
                        'value'   => $status_value,
                        'compare' => '=',
                    ],
                ],
            ])
        );

        ?>


        <a
            href="<?php echo esc_url(
                add_query_arg(
                    [
                        'page'    => 'crm-leads',
                        'estatus' => $status_value,
                    ],
                    admin_url('admin.php')
                )
            ); ?>"
            class="crm-lead-counter"
        >

            <span>
                <?php echo esc_html($status_label); ?>
            </span>

            <strong>
                <?php echo esc_html($status_count); ?>
            </strong>

        </a>


    <?php endforeach; ?>

</div>





<!-- ==================================================
     CAPTURA DE LEAD
================================================== -->

<div class="crm-leads-capture">

    <div class="crm-leads-capture-title">
        <span>Nuevo Lead</span>
        <small>Captura de prospecto</small>
    </div>


    <form
        id="crm-v3-lead-form"
        method="post"
        action="<?php echo esc_url(
            admin_url('admin-post.php')
        ); ?>"
    >

        <input
            type="hidden"
            name="action"
            value="crm_v3_guardar_lead"
        >

        <?php wp_nonce_field(
            'crm_v3_guardar_lead',
            'crm_v3_lead_nonce'
        ); ?>


        <!-- ==================================================
             LÍNEA 1 · DATOS PRINCIPALES
        ================================================== -->

        <div class="crm-leads-capture-row crm-leads-capture-row-main">


            <!-- Nombre -->

            <div class="crm-lead-field crm-lead-field-name">

                <label>Nombre</label>

                <input
                    type="text"
                    name="lead_nombre"
                    placeholder="Nombre del lead"
                    required
                >

            </div>


            <!-- Origen -->

            <div class="crm-lead-field crm-lead-field-origin">

                <label>Origen</label>

                <select name="origen_captacion">

                    <option value="">
                        Seleccionar
                    </option>

                    <?php foreach (
                        $origenes as $term
                    ) : ?>

                        <option
                            value="<?php echo esc_attr(
                                $term->term_id
                            ); ?>"
                        >
                            <?php echo esc_html(
                                $term->name
                            ); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Teléfono -->

            <div class="crm-lead-field crm-lead-field-phone">

                <label>Teléfono</label>

                <input
                    type="text"
                    name="telefono"
                    inputmode="numeric"
                    placeholder="Teléfono"
                >

            </div>


            <!-- Tipo de Lead -->

            <div class="crm-lead-field crm-lead-field-type">

                <label>Tipo de lead</label>

                <select name="tipo_de_lead">

                    <option value="">
                        Seleccionar
                    </option>

                    <?php if (
                        !empty(
                            $campo_tipo_lead['choices']
                        )
                    ) : ?>

                        <?php foreach (
                            $campo_tipo_lead['choices']
                            as $value => $label
                        ) : ?>

                            <option
                                value="<?php echo esc_attr(
                                    $value
                                ); ?>"
                            >
                                <?php echo esc_html(
                                    $label
                                ); ?>
                            </option>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </select>

            </div>


            <!-- Zona -->

            <div class="crm-lead-field crm-lead-field-zone">

                <label>Zona de Compra/Venta</label>

                <input
                    type="text"
                    name="zona_de_compra"
                    placeholder="Zona"
                >

            </div>


            <!-- NSS -->

            <div class="crm-lead-field crm-lead-field-nss">

                <label>NSS</label>

                <input
                    type="text"
                    name="nss"
                    inputmode="numeric"
                    placeholder="NSS"
                >

            </div>


            <!-- CURP -->

            <div class="crm-lead-field crm-lead-field-curp">

                <label>CURP</label>

                <input
                    type="text"
                    name="curp"
                    placeholder="CURP"
                >

            </div>


            


        </div>


        <!-- ==================================================
             LÍNEA 2 · ESTATUS / NOTAS / GUARDAR
        ================================================== -->

        <div class="crm-leads-capture-row crm-leads-capture-row-secondary">

            <!-- RFC -->

            <div class="crm-lead-field crm-lead-field-rfc">

                <label>RFC</label>

                <input
                    type="text"
                    name="rfc"
                    placeholder="RFC"
                >

            </div>


            <!-- Institución -->

            <div class="crm-lead-field crm-lead-field-bank">

                <label>Institución financiera</label>

                <select name="institucion_crediticia">

                    <option value="">
                        Seleccionar
                    </option>

                    <?php foreach (
                        $instituciones as $term
                    ) : ?>

                        <option
                            value="<?php echo esc_attr(
                                $term->term_id
                            ); ?>"
                        >
                            <?php echo esc_html(
                                $term->name
                            ); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <!-- Monto -->

            <div class="crm-lead-field crm-lead-field-amount">

                <label>Monto crédito</label>

                <input
                    type="number"
                    name="monto_de_credito"
                    placeholder="$0"
                    min="0"
                    step="0.01"
                >

            </div>



            <!-- Estatus -->

            <div class="crm-lead-field crm-lead-field-status">

                <label>Estatus</label>

                <select name="estatus">

                    <option value="">
                        Seleccionar
                    </option>

                    <?php if (
                        !empty(
                            $campo_estatus['choices']
                        )
                    ) : ?>

                        <?php foreach (
                            $campo_estatus['choices']
                            as $value => $label
                        ) : ?>

                            <option
                                value="<?php echo esc_attr(
                                    $value
                                ); ?>"
                            >
                                <?php echo esc_html(
                                    $label
                                ); ?>
                            </option>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </select>

            </div>


            <!-- Notas -->

            <div class="crm-lead-field crm-lead-field-notes">

                <label>Notas</label>

                <textarea
                    name="notas"
                    placeholder="Notas del seguimiento..."
                    rows="2"
                ></textarea>

            </div>


            <!-- Guardar -->

            <div class="crm-lead-field crm-lead-field-save">

                <label>&nbsp;</label>

                <button
                    type="submit"
                    class="crm-lead-save"
                >
                    Guardar Lead
                </button>

            </div>


        </div>

    </form>

</div>



<!-- ==================================================
     FILTROS
================================================== -->

<div class="crm-v3-leads-filters">


    <form method="get">

        <input
            type="hidden"
            name="page"
            value="crm-leads"
        >


        <!-- Buscar -->

        <input
            type="text"
            name="lead_busqueda"
            value="<?php echo esc_attr(
                $filtro_busqueda
            ); ?>"
            placeholder="Buscar lead"
        >


        <!-- Origen -->

        <select name="origen">

            <option value="">
                Origen de captación
            </option>

            <?php foreach (
    $origenes as $term
) : ?>

    <?php if (
        !isset($origenes_filtro[$term->term_id])
    ) {
        continue;
    } ?>

                <option
                    value="<?php echo esc_attr(
                        $term->term_id
                    ); ?>"
                    <?php selected(
                        $filtro_origen,
                        $term->term_id
                    ); ?>
                >

                    <?php echo esc_html(
                        $term->name
                    ); ?>

                </option>

            <?php endforeach; ?>

        </select>


        <!-- Tipo de Lead -->

        <select name="tipo_de_lead">

            <option value="">
                Tipo de lead
            </option>

            <?php foreach (
    $campo_tipo_lead['choices']
    as $value => $label
) : ?>

    <?php if (
        !isset($tipos_filtro[$value])
    ) {
        continue;
    } ?>

                <option
                    value="<?php echo esc_attr(
                        $value
                    ); ?>"
                    <?php selected(
                        $filtro_tipo,
                        $value
                    ); ?>
                >

                    <?php echo esc_html(
                        $label
                    ); ?>

                </option>

            <?php endforeach; ?>

        </select>


        <!-- Institución -->

        <select name="institucion">

            <option value="">
                Institución financiera
            </option>

            <?php foreach (
    $instituciones as $term
) : ?>

    <?php if (
        !isset($instituciones_filtro[$term->term_id])
    ) {
        continue;
    } ?>

                <option
                    value="<?php echo esc_attr(
                        $term->term_id
                    ); ?>"
                    <?php selected(
                        $filtro_institucion,
                        $term->term_id
                    ); ?>
                >

                    <?php echo esc_html(
                        $term->name
                    ); ?>

                </option>

            <?php endforeach; ?>

        </select>


        <!-- Estatus -->

        <select name="estatus">

            <option value="">
                Estatus
            </option>

            <?php foreach (
    $campo_estatus['choices']
    as $value => $label
) : ?>

    <?php if (
        !isset($estatus_filtro[$value])
    ) {
        continue;
    } ?>

                <option
                    value="<?php echo esc_attr(
                        $value
                    ); ?>"
                    <?php selected(
                        $filtro_estatus,
                        $value
                    ); ?>
                >

                    <?php echo esc_html(
                        $label
                    ); ?>

                </option>

            <?php endforeach; ?>

        </select>


        <!-- Zona -->

        <input
            type="text"
            name="zona"
            value="<?php echo esc_attr(
                $filtro_zona
            ); ?>"
            placeholder="Zona de compra"
        >


        <!-- Limpiar -->

        <a
            href="<?php echo esc_url(
                admin_url(
                    'admin.php?page=crm-leads'
                )
            ); ?>"
            class="crm-leads-clear"
        >
            Limpiar
        </a>

    </form>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.querySelector(
        '.crm-v3-leads-filters form'
    );

    if (!form) {
        return;
    }

    form.querySelectorAll('select').forEach(function (select) {

        select.addEventListener('change', function () {
            form.submit();
        });

    });

});
</script>


</div>


        <!-- ==================================================
             TABLA
        ================================================== -->

        <div class="crm-leads-card">

            <div class="crm-leads-table-wrapper">

                <table class="crm-leads-table">


                    <!-- ==================================================
                         HEADER
                    ================================================== -->

                    <thead>

                        <tr>

                            <th>Nombre</th>
                            <th>Origen</th>
                            <th>Teléfono</th>
                            <th>Tipo de lead</th>
                            <th>Zona de compra</th>
                            <th>NSS</th>
                            <th>CURP</th>
                            <th>RFC</th>
                            <th>Institución f.</th>
                            <th>crédito</th>
                            <th>Estatus</th>
                            <th>Notas</th>

                        </tr>

                    </thead>


                    <tbody>


                        <!-- ==================================================
                             LEADS EXISTENTES
                        ================================================== -->

                        <?php if (!empty($leads)) : ?>

                            <?php foreach (
                                $leads
                                as $lead
                            ) : ?>


                                <?php

                                $origen = get_field(
                                    'origen_captacion',
                                    $lead->ID
                                );


                                $telefono = get_field(
                                    'telefono',
                                    $lead->ID
                                );


                                $tipo_lead = get_field(
                                    'tipo_de_lead',
                                    $lead->ID
                                );


                                $zona = get_field(
                                    'zona_de_compra',
                                    $lead->ID
                                );


                                $nss = get_field(
                                    'nss',
                                    $lead->ID
                                );


                                $curp = get_field(
                                    'curp',
                                    $lead->ID
                                );


                                $rfc = get_field(
                                    'rfc',
                                    $lead->ID
                                );


                                $institucion = get_field(
                                    'institucion_crediticia',
                                    $lead->ID
                                );


                                $monto = get_field(
                                    'monto_de_credito',
                                    $lead->ID
                                );


                                $estatus = get_field(
                                    'estatus',
                                    $lead->ID
                                );

                                $nota_original = get_field(
                                    'notas',
                                    $lead->ID
                                );

                                /*
                                 * Compatibilidad con leads creados antes
                                 * de esta versión: si el ACF está vacío,
                                 * tomamos la primera nota histórica como
                                 * comentario original.
                                 */
                                if (
                                    trim((string) $nota_original) === ''
                                ) {

                                    $historial_notas = get_post_meta(
                                        $lead->ID,
                                        '_crm_lead_notas_historial',
                                        true
                                    );

                                    if (
                                        is_array($historial_notas) &&
                                        !empty($historial_notas[0]['nota'])
                                    ) {
                                        $nota_original =
                                            $historial_notas[0]['nota'];
                                    }
                                }

                                ?>


                                <tr>


                                    <!-- Nombre -->

                                    <td>

                                        <strong>
                                            <?php echo esc_html(
                                                $lead->post_title
                                            ); ?>
                                        </strong>

                                        <?php
                                        $email_lead = get_field(
                                            'e-mail',
                                            $lead->ID
                                        );
                                        ?>

                                        <?php if ($email_lead) : ?>

                                            <br>

                                            <a
                                                href="<?php echo esc_url(
                                                    'mailto:' . $email_lead
                                                ); ?>"
                                            >
                                                <?php echo esc_html(
                                                    $email_lead
                                                ); ?>
                                            </a>

                                        <?php endif; ?>

                                    </td>


                                    <!-- Origen -->

                                    <td>

                                        <?php echo crm_v3_leads_term_name(
                                            $origen,
                                            'origen-lead'
                                        ); ?>

                                    </td>


                                    <!-- Teléfono -->

                                    <td>

                                        <?php echo esc_html(
                                            $telefono ?: '—'
                                        ); ?>

                                    </td>


                                    <!-- Tipo de Lead -->

                                    <td>

                                        <?php echo crm_v3_leads_acf_choice_label(
                                        'tipo_de_lead',
                                        $tipo_lead,
                                        $lead->ID
                                    ); ?>

                                    </td>


                                    <!-- Zona -->

                                    <td>

                                        <?php echo esc_html(
                                            $zona ?: '—'
                                        ); ?>

                                    </td>


                                    <!-- NSS -->

                                    <td>

                                        <?php echo esc_html(
                                            $nss ?: '—'
                                        ); ?>

                                    </td>


                                    <!-- CURP -->

                                    <td>

                                        <?php echo esc_html(
                                            $curp ?: '—'
                                        ); ?>

                                    </td>


                                    <!-- RFC -->

                                    <td>

                                        <?php echo esc_html(
                                            $rfc ?: '—'
                                        ); ?>

                                    </td>


                                    <!-- Institución -->

                                    <td>

                                        <?php echo crm_v3_leads_term_name(
                                            $institucion,
                                            'tipo-de-creditos'
                                        ); ?>

                                    </td>


                                    <!-- Monto -->

                                    <td>

                                        <?php if (
                                            $monto !== '' &&
                                            $monto !== null
                                        ) : ?>

                                            <?php echo esc_html(crm_v3_format_money($monto)); ?>

                                        <?php else : ?>

                                            —

                                        <?php endif; ?>

                                    </td>


                                    <!-- Estatus -->

                                    <td>

                                        <div class="crm-lead-status-cell">

                                    <span
                                        class="crm-lead-status crm-lead-status-<?php echo esc_attr(
                                            sanitize_title((string) $estatus)
                                        ); ?>"
                                    >

                                                <?php echo crm_v3_leads_acf_choice_label(
                                                    'estatus',
                                                    $estatus,
                                                    $lead->ID
                                                ); ?>

                                            </span>

                                        </div>

                                    </td>

                                    <!-- Notas -->

                                    <td class="crm-lead-notes-cell">

                                        <?php if (
                                            trim((string) $nota_original) !== ''
                                        ) : ?>

                                            <button
                                                type="button"
                                                class="crm-lead-note-button"
                                                data-lead-id="<?php echo esc_attr(
                                                    $lead->ID
                                                ); ?>"
                                                data-lead-name="<?php echo esc_attr(
                                                    $lead->post_title
                                                ); ?>"
                                                data-note-exists="1"
                                            >
                                                Ver
                                            </button>

                                        <?php else : ?>

                                            <button
                                                type="button"
                                                class="crm-lead-note-button crm-lead-note-add"
                                                data-lead-id="<?php echo esc_attr(
                                                    $lead->ID
                                                ); ?>"
                                                data-lead-name="<?php echo esc_attr(
                                                    $lead->post_title
                                                ); ?>"
                                                data-note-exists="0"
                                            >
                                                Agregar
                                            </button>

                                        <?php endif; ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>

                        <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>


<!-- =========================================================
     MODAL COMENTARIO DEL LEAD
     ========================================================= -->

<div
    id="crm-lead-note-modal"
    class="crm-lead-note-modal"
    aria-hidden="true"
>

    <div
        class="crm-lead-note-overlay"
        data-note-close
    ></div>


    <div
        class="crm-lead-note-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="crm-lead-note-title"
    >

        <div class="crm-lead-note-header">

            <div>

                <span>
                    COMENTARIO DE SEGUIMIENTO
                </span>

                <strong id="crm-lead-note-title">
                    Lead
                </strong>

            </div>


            <button
                type="button"
                class="crm-lead-note-close"
                data-note-close
                aria-label="Cerrar"
            >
                ×
            </button>

        </div>


        <div
            id="crm-lead-note-content"
            class="crm-lead-note-content"
        ></div>


        <div class="crm-lead-note-form">

            <label for="crm-lead-note-input">
                Comentario
            </label>


            <textarea
                id="crm-lead-note-input"
                rows="3"
                placeholder="Escribe el seguimiento..."
                spellcheck="true"
            ></textarea>


            <div class="crm-lead-note-actions">

                <button
                    type="button"
                    id="crm-lead-note-save"
                    class="crm-lead-note-save"
                >
                    Guardar cambios
                </button>

            </div>

        </div>

    </div>

</div>


</div>

    <?php
}




/**
 * ============================================================
 * OBTENER HISTORIAL DE NOTAS DEL LEAD
 * ============================================================
 */

function crm_v3_obtener_notas_lead() {

    if (!current_user_can('manage_options')) {
        wp_send_json_error(
            [
                'message' => 'No tienes permisos.'
            ],
            403
        );
    }

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(
                wp_unslash($_POST['nonce'])
            ),
            'crm_v3_lead_notas'
        )
    ) {
        wp_send_json_error(
            [
                'message' => 'Solicitud no válida.'
            ],
            403
        );
    }

    $lead_id = isset($_POST['lead_id'])
        ? absint($_POST['lead_id'])
        : 0;

    if (
        !$lead_id ||
        get_post_type($lead_id) !== 'leads'
    ) {
        wp_send_json_error(
            [
                'message' => 'Lead no válido.'
            ],
            400
        );
    }

    /*
     * El comentario mostrado y editado es el ACF "notas".
     */
    $nota = get_field(
        'notas',
        $lead_id
    );

    /*
     * Compatibilidad con registros anteriores:
     * si ACF está vacío, usamos la primera entrada histórica.
     */
    if (
        trim((string) $nota) === ''
    ) {

        $historial = get_post_meta(
            $lead_id,
            '_crm_lead_notas_historial',
            true
        );

        if (
            is_array($historial) &&
            !empty($historial[0]['nota'])
        ) {
            $nota = $historial[0]['nota'];
        }
    }

    wp_send_json_success(
        [
            'has_note' => trim((string) $nota) !== '',
            'nota'     => (string) $nota,
        ]
    );
}

add_action(
    'wp_ajax_crm_v3_obtener_notas_lead',
    'crm_v3_obtener_notas_lead'
);
