<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
====================================
Propiedades en el sitio web. No tocar.
====================================

El tipo de contenido "propiedades" se define en ACF. Aquí solo se
asegura lo que el sitio público necesita: que sea público y que
tenga listado en /propiedades/. Antes se registraba por segunda
vez desde aquí, y esa copia pisaba la configuración de ACF.
*/

function crm_v3_propiedades_args_web($args, $post_type) {

    if ($post_type !== 'propiedades') {
        return $args;
    }

    $args['public']             = true;
    $args['publicly_queryable'] = true;
    $args['has_archive']        = true;
    $args['show_in_rest']       = true;

    $rewrite = isset($args['rewrite']) && is_array($args['rewrite'])
        ? $args['rewrite']
        : array();

    $rewrite['slug'] = 'propiedades';

    $args['rewrite'] = $rewrite;

    return $args;
}

add_filter(
    'register_post_type_args',
    'crm_v3_propiedades_args_web',
    10,
    2
);


/*
 * Respaldo: si ACF no registró el tipo (por ejemplo, con ACF
 * desactivado), se registra aquí para que el sitio no pierda
 * sus propiedades.
 */

function crm_v3_register_propiedades() {

    if (post_type_exists('propiedades')) {
        return;
    }

    register_post_type('propiedades', array(

        'label' => 'Propiedades',
        'public' => true,
        'has_archive' => true,
        'rewrite' => array(
        'slug' => 'propiedades'
        ),

        'supports' => array(
            'title',
            'thumbnail'
        ),

        'show_in_rest' => true,

    ));
}

add_action('init', 'crm_v3_register_propiedades', 20);




/**
 * ============================================================
 * CRM V3 - ESTADO REAL DE PROPIEDADES
 * Fuente única para contadores y filtros
 * ============================================================
 */

function crm_v3_propiedades_estados_reales() {

    /*
     * --------------------------------------------------------
     * TODAS LAS PROPIEDADES PUBLICADAS
     * --------------------------------------------------------
     */

    $propiedades = get_posts([
        'post_type'      => 'propiedades',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);


    /*
     * --------------------------------------------------------
     * OPERACIONES
     *
     * Se toma la operación más reciente de cada propiedad.
     * --------------------------------------------------------
     */

    $operaciones_por_propiedad = [];

    $operaciones = get_posts([
    'post_type'      => 'operaciones',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

    $operaciones = crm_v3_ordenar_por_fecha(
        $operaciones,
        'fecha_de_operacion'
    );


    foreach ($operaciones as $operacion) {

        $propiedad = get_field(
            'propiedad',
            $operacion->ID
        );

        $propiedad_id = crm_v3_get_related_id(
            $propiedad
        );

        if (!$propiedad_id) {
            continue;
        }


        /*
         * Como las operaciones vienen DESC,
         * la primera es la más reciente.
         */
        if (isset($operaciones_por_propiedad[$propiedad_id])) {
            continue;
        }


        $estatus_operacion = get_field(
            'estatus_de_operacion',
            $operacion->ID
        );


        /*
         * Normalizar estatus de operación.
         */
        if (is_array($estatus_operacion)) {
            $estatus_operacion = reset($estatus_operacion);
        }

        if (
            is_object($estatus_operacion) &&
            isset($estatus_operacion->value)
        ) {
            $estatus_operacion = $estatus_operacion->value;
        }


        $operaciones_por_propiedad[$propiedad_id] =
            strtolower(
                trim(
                    (string) $estatus_operacion
                )
            );
    }


    /*
     * --------------------------------------------------------
     * CATEGORÍAS
     * --------------------------------------------------------
     */

    $resultado = [
        'todos'         => [],
        'exclusiva'     => [],
        'en_proceso'    => [],
        'relacionada'   => [],
        'sin_relacion'  => [],
        'cerrada'       => [],
        'perdida'       => [],
    ];


    /*
     * --------------------------------------------------------
     * CLASIFICAR CADA PROPIEDAD
     * --------------------------------------------------------
     */

    foreach ($propiedades as $propiedad_id) {

        /*
         * Obtener estatus de propiedad.
         */
        $estatus_propiedad = get_field(
            'estatus',
            $propiedad_id
        );


        /*
         * Obtener tipo de operación.
         */
        $tipo_operacion = get_field(
            'tipo_de_operacion',
            $propiedad_id
        );


        /*
         * Normalizar estatus de propiedad.
         */
        if (is_array($estatus_propiedad)) {
            $estatus_propiedad = reset($estatus_propiedad);
        }

        if (
            is_object($estatus_propiedad) &&
            isset($estatus_propiedad->value)
        ) {
            $estatus_propiedad = $estatus_propiedad->value;
        }


        /*
         * Normalizar tipo de operación.
         */
        if (is_array($tipo_operacion)) {
            $tipo_operacion = reset($tipo_operacion);
        }

        if (
            is_object($tipo_operacion) &&
            isset($tipo_operacion->value)
        ) {
            $tipo_operacion = $tipo_operacion->value;
        }


        /*
         * Convertir valores a formato comparable.
         */
        $estatus_propiedad = strtolower(
            trim(
                (string) $estatus_propiedad
            )
        );

        $tipo_operacion = strtolower(
            trim(
                (string) $tipo_operacion
            )
        );


        /*
         * Obtener estatus de la operación relacionada.
         */
        $estatus_operacion =
            isset(
                $operaciones_por_propiedad[$propiedad_id]
            )
            ? $operaciones_por_propiedad[$propiedad_id]
            : '';


        /*
         * ----------------------------------------------------
         * TODOS
         *
         * 100% de las propiedades publicadas.
         * ----------------------------------------------------
         */

        $resultado['todos'][] = $propiedad_id;


        /*
         * ----------------------------------------------------
         * PERDIDA
         *
         * Propiedad perdida
         * O
         * Operación perdida.
         * ----------------------------------------------------
         */

        if (
            $estatus_propiedad === 'perdida' ||
            $estatus_operacion === 'perdida'
        ) {

            $resultado['perdida'][] = $propiedad_id;

            continue;
        }


        /*
         * ----------------------------------------------------
         * CERRADA
         *
         * Operación relacionada cerrada.
         * ----------------------------------------------------
         */

        if (
            $estatus_operacion === 'cerrada' ||
            $estatus_operacion === 'cerrado'
        ) {

            $resultado['cerrada'][] = $propiedad_id;

            continue;
        }


        /*
         * ----------------------------------------------------
         * EXCLUSIVA
         *
         * Toda propiedad cuyo tipo de operación
         * sea "exclusiva".
         *
         * NO se excluye por estar:
         * - En proceso
         * - Relacionada
         * - Sin relación
         *
         * Solamente queda fuera si ya fue:
         * - Perdida
         * - Cerrada
         * ----------------------------------------------------
         */

        if (
            $tipo_operacion === 'exclusiva'
        ) {

            $resultado['exclusiva'][] = $propiedad_id;
        }


        /*
         * ----------------------------------------------------
         * EN PROCESO
         *
         * Solamente el estatus propio
         * de CRM Propiedades.
         *
         * NO consulta Operaciones.
         * ----------------------------------------------------
         */

        if (
            $estatus_propiedad === 'en_proceso'
        ) {

            $resultado['en_proceso'][] = $propiedad_id;
        }


        /*
         * ----------------------------------------------------
         * RELACIONADA
         *
         * Propiedad:
         * para_relacionar
         *
         * +
         *
         * Operación activa:
         * en_proceso
         * ----------------------------------------------------
         */

        if (
            $estatus_propiedad === 'para_relacionar' &&
            $estatus_operacion === 'en_proceso'
        ) {

            $resultado['relacionada'][] = $propiedad_id;
        }


        /*
         * ----------------------------------------------------
         * SIN RELACIÓN
         *
         * Propiedad:
         * para_relacionar
         *
         * +
         *
         * Sin operación activa.
         * ----------------------------------------------------
         */

        if (
            $estatus_propiedad === 'para_relacionar' &&
            $estatus_operacion === ''
        ) {

            $resultado['sin_relacion'][] = $propiedad_id;
        }
    }


    return $resultado;
}


// ============================================================
// OBTENER OPCIONES ACF UTILIZADAS EN PROPIEDADES
// ============================================================

function crm_v3_propiedades_opciones_acf_usadas($campo) {

    global $wpdb;

    $valores = $wpdb->get_col(
        $wpdb->prepare(
            "
            SELECT DISTINCT pm.meta_value
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p
                ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
              AND pm.meta_value <> ''
              AND p.post_type = 'propiedades'
              AND p.post_status = 'publish'
            ORDER BY pm.meta_value ASC
            ",
            $campo
        )
    );

    if (empty($valores)) {
        return array();
    }

    $field = get_field_object($campo);

    $resultado = array();

    foreach ($valores as $valor) {

        $valor = maybe_unserialize($valor);

        if (is_array($valor)) {
            continue;
        }

        $valor = (string) $valor;

        if (
            $field &&
            !empty($field['choices']) &&
            isset($field['choices'][$valor])
        ) {
            $resultado[$valor] = $field['choices'][$valor];
        } else {
            $resultado[$valor] = $valor;
        }
    }

    return $resultado;
}

/**
 * ============================================================
 * OBTENER PROPIEDADES FILTRADAS
 * Fuente única para página + AJAX
 * ============================================================
 */

function crm_v3_propiedades_obtener_filtradas($filtros = []) {

    /*
     * --------------------------------------------------------
     * CONSULTAS
     * --------------------------------------------------------
     */

    $meta_query = [];
    $tax_query  = [];


    /*
     * --------------------------------------------------------
     * FILTRO POR CONTADOR
     *
     * Utilizamos exactamente la misma clasificación
     * que utilizan los contadores.
     * --------------------------------------------------------
     */

    $post__in = [];


    $contador = !empty($filtros['contador'])
        ? sanitize_key($filtros['contador'])
        : '';


    if (
        $contador !== '' &&
        $contador !== 'todos'
    ) {

        $estados_reales =
            crm_v3_propiedades_estados_reales();


        if (
            isset(
                $estados_reales[$contador]
            )
        ) {

            $post__in =
                $estados_reales[$contador];

        } else {

            /*
             * Si el contador no existe,
             * no mostrar resultados.
             */

            $post__in = [0];
        }
    }


    /*
     * --------------------------------------------------------
     * FILTRO TIPO DE OPERACIÓN
     * --------------------------------------------------------
     */

    if (
        !empty(
            $filtros['tipo_operacion']
        )
    ) {

        $meta_query[] = [
            'key'     => 'tipo_de_operacion',
            'value'   => $filtros['tipo_operacion'],
            'compare' => '=',
        ];
    }


    /*
     * --------------------------------------------------------
     * FILTRO ESTADO DEL INMUEBLE
     * --------------------------------------------------------
     */

    if (
        !empty(
            $filtros['estado_inmueble']
        )
    ) {

        $meta_query[] = [
            'key'     => 'estado_del_inmueble',
            'value'   => $filtros['estado_inmueble'],
            'compare' => '=',
        ];
    }


    /*
     * --------------------------------------------------------
     * FILTRO ESTADO COMERCIAL
     * --------------------------------------------------------
     */

    if (
        !empty(
            $filtros['estado_comercial']
        )
    ) {

        $meta_query[] = [
            'key'     => 'estado_comercial',
            'value'   => $filtros['estado_comercial'],
            'compare' => '=',
        ];
    }


    /*
     * --------------------------------------------------------
     * FILTRO DOCUMENTACIÓN
     * --------------------------------------------------------
     */

    if (
        !empty(
            $filtros['documentacion']
        )
    ) {

        $meta_query[] = [
            'key'     => 'documentacion',
            'value'   => $filtros['documentacion'],
            'compare' => '=',
        ];
    }


    /*
     * --------------------------------------------------------
     * FILTRO COLONIA
     * --------------------------------------------------------
     */

    if (
        !empty(
            $filtros['colonia']
        )
    ) {

        $tax_query[] = [
            'taxonomy' => 'fraccionamiento-o-colonia',
            'field'    => 'term_id',
            'terms'    => $filtros['colonia'],
        ];
    }


    /*
     * --------------------------------------------------------
     * FILTRO CIUDAD
     * --------------------------------------------------------
     */

    if (
        !empty(
            $filtros['ciudad']
        )
    ) {

        $tax_query[] = [
            'taxonomy' => 'ciudad',
            'field'    => 'term_id',
            'terms'    => $filtros['ciudad'],
        ];
    }


    /*
     * --------------------------------------------------------
     * FILTRO TIPO DE PROPIEDAD
     * --------------------------------------------------------
     */

    if (
        !empty(
            $filtros['tipo_propiedad']
        )
    ) {

        $tax_query[] = [
            'taxonomy' => 'tipo-de-propiedad',
            'field'    => 'term_id',
            'terms'    => $filtros['tipo_propiedad'],
        ];
    }


    /*
     * --------------------------------------------------------
     * RELACIÓN DE TAXONOMÍAS
     * --------------------------------------------------------
     */

    if (
        !empty($tax_query)
    ) {

        $tax_query['relation'] = 'AND';
    }


    /*
     * --------------------------------------------------------
     * CONSULTA FINAL
     * --------------------------------------------------------
     */

   $args = [
    'post_type'      => 'propiedades',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => $meta_query,
    'tax_query'      => $tax_query,
];


    /*
     * Solamente agregar post__in cuando
     * estamos filtrando por un contador.
     *
     * "Todos" no utiliza post__in porque debe
     * mostrar el 100% de propiedades publicadas.
     */

    if (
        !empty($post__in)
    ) {

        $args['post__in'] = $post__in;
    }


    $query = new WP_Query($args);

    $query->posts = crm_v3_ordenar_por_fecha(
        $query->posts,
        'fecha_captacion'
    );

    return $query;
}


/**
 * ============================================================
 * RENDER TABLA CRM PROPIEDADES
 * ============================================================
 */

function crm_v3_propiedades_render_table($propiedades) {

    ob_start();
    ?>

        <?php

    /*
     * ============================================================
     * OBTENER ESTATUS DE OPERACIONES RELACIONADAS
     * ============================================================
     */

    $operaciones_por_propiedad = [];

    $operaciones = get_posts([
    'post_type'      => 'operaciones',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

    $operaciones = crm_v3_ordenar_por_fecha(
        $operaciones,
        'fecha_de_operacion'
    );

    foreach ($operaciones as $operacion) {

        $propiedad_relacionada = get_field(
            'propiedad',
            $operacion->ID
        );

        $propiedad_relacionada_id = crm_v3_get_related_id(
            $propiedad_relacionada
        );

        if (!$propiedad_relacionada_id) {
            continue;
        }

        if (isset($operaciones_por_propiedad[$propiedad_relacionada_id])) {
            continue;
        }

        $operaciones_por_propiedad[$propiedad_relacionada_id] = get_field(
            'estatus_de_operacion',
            $operacion->ID
        );
    }

    ?>


    <table class="widefat fixed crm-v3-propiedades-table">

        <thead>
            <tr>
                <th>Propiedad</th>
                <th>Propietario</th>
                <th>Colonia</th>
                <th>Ciudad</th>
                <th>Tipo</th>
                <th>M² terreno</th>
                <th>M² const</th>
                <th>V. catastral</th>
                <th>Precio venta</th>
                <th>Estatus</th>
                <th>Estatus OP</th>
            </tr>
        </thead>

        <tbody>

        <?php if ($propiedades->have_posts()) : ?>

            <?php while ($propiedades->have_posts()) : $propiedades->the_post(); ?>

                <?php
                $post_id = get_the_ID();

                $cliente = get_field('cliente_propietario', $post_id);

                $colonia = get_the_terms(
                    $post_id,
                    'fraccionamiento-o-colonia'
                );

                $ciudad = get_the_terms(
                    $post_id,
                    'ciudad'
                );

                $tipo_propiedad = get_the_terms(
                    $post_id,
                    'tipo-de-propiedad'
                );

                $m2_terreno      = get_field('m2_terreno', $post_id);
                $m2_construccion = get_field('m2_construccion', $post_id);
                $valor_catastral = get_field('valor_catastral', $post_id);
                $precio_venta    = get_field('precio_de_venta', $post_id);
                $estatus         = get_field('estatus', $post_id);

                $estatus_op = isset($operaciones_por_propiedad[$post_id])
                ? $operaciones_por_propiedad[$post_id]
                : '';
                ?>

                <tr>

               <td class="crm-propiedad-titulo">
    <?php echo esc_html(get_the_title($post_id)); ?>
</td>

                    <td class="crm-propiedad-cliente">
    <?php

    if ($cliente instanceof WP_Post) {

        $cliente_id = $cliente->ID;

    } elseif (is_numeric($cliente)) {

        $cliente_id = (int) $cliente;

    } else {

        $cliente_id = 0;
    }


    if ($cliente_id) {

        $ficha_url = add_query_arg(
            array(
                'page'       => 'crm-ficha-cliente',
                'cliente_id' => $cliente_id,
            ),
            admin_url('admin.php')
        );

        ?>

        <a href="<?php echo esc_url($ficha_url); ?>">
            <?php echo esc_html(
                get_the_title($cliente_id)
            ); ?>
        </a>

        <?php

    } else {

        echo '—';
    }

    ?>
</td>

                    <td>
                        <?php
                        echo !empty($colonia)
                            ? esc_html($colonia[0]->name)
                            : '—';
                        ?>
                    </td>

                    <td>
                        <?php
                        echo !empty($ciudad)
                            ? esc_html($ciudad[0]->name)
                            : '—';
                        ?>
                    </td>

                    <td>
                        <?php
                        echo !empty($tipo_propiedad)
                            ? esc_html($tipo_propiedad[0]->name)
                            : '—';
                        ?>
                    </td>

                    <td>
                        <?php echo $m2_terreno !== '' && $m2_terreno !== null
                            ? esc_html($m2_terreno)
                            : '—'; ?>
                    </td>

                    <td>
                        <?php echo $m2_construccion !== '' && $m2_construccion !== null
                            ? esc_html($m2_construccion)
                            : '—'; ?>
                    </td>

                    <td class="crm-monto">
                        <?php echo $valor_catastral !== '' && $valor_catastral !== null
                            ? '$' . esc_html(number_format((float) $valor_catastral, 2))
                            : '—'; ?>
                    </td>

                    <td class="crm-monto">
                        <?php echo $precio_venta !== '' && $precio_venta !== null
                            ? '$' . esc_html(number_format((float) $precio_venta, 2))
                            : '—'; ?>
                    </td>

                    <td>
                        <span class="crm-propiedad-estatus crm-estatus-<?php echo esc_attr(
                        crm_v3_propiedad_estatus_clase($estatus)
                        ); ?>">
                        <?php echo esc_html(
                        crm_v3_propiedad_estatus_formato($estatus)
                        ); ?>
                    </span>
                    </td>

                    <td>
                        <span class="crm-propiedad-estatus crm-estatus-op-<?php echo esc_attr(
                         crm_v3_propiedad_estatus_clase($estatus_op)
                         ); ?>">
                        <?php echo esc_html(
                        crm_v3_propiedad_estatus_formato($estatus_op)
                        ); ?>
                     </span>
                    </td>


                </tr>

            <?php endwhile; ?>

        <?php else : ?>

            <tr>
                <td colspan="11" class="crm-propiedades-empty">
                    No se encontraron propiedades.
                </td>
            </tr>

        <?php endif; ?>

        </tbody>

    </table>

    <?php

    wp_reset_postdata();

    return ob_get_clean();
}


function crm_v3_propiedades_page() {

    if (!current_user_can('manage_options')) {
        return;
    }


/*
 * Valores de los filtros en la URL. Solo marcan la opción
 * seleccionada en cada lista; el filtrado se hace por AJAX.
 */
$colonia          = isset($_GET['colonia']) ? absint($_GET['colonia']) : 0;
$ciudad           = isset($_GET['ciudad']) ? absint($_GET['ciudad']) : 0;
$tipo_propiedad   = isset($_GET['tipo_propiedad']) ? absint($_GET['tipo_propiedad']) : 0;
$tipo_operacion   = isset($_GET['tipo_operacion']) ? sanitize_key($_GET['tipo_operacion']) : '';
$estado_inmueble  = isset($_GET['estado_inmueble']) ? sanitize_key($_GET['estado_inmueble']) : '';
$estado_comercial = isset($_GET['estado_comercial']) ? sanitize_key($_GET['estado_comercial']) : '';
$documentacion    = isset($_GET['documentacion']) ? sanitize_key($_GET['documentacion']) : '';


    $propiedades = new WP_Query(array(
    'post_type'      => 'propiedades',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
));

    $propiedades->posts = crm_v3_ordenar_por_fecha(
        $propiedades->posts,
        'fecha_captacion'
    );
    ?>

    <div class="wrap crm-v3-propiedades">

        <div class="crm-v3-module-header">

            <div class="crm-v3-module-header-info">

            <span>CRM Propiedades</span>
            <p>Gestión, análisis y control de propiedades</p>

            </div>

            <a
            href="<?php echo esc_url(
            admin_url('admin.php?page=crm-nuevos-registros')
            ); ?>"
            class="crm-v3-new-property"
            >
            + Nuevos registros
            </a>

        </div>  


<?php
$estados_reales = crm_v3_propiedades_estados_reales();
?>

<div class="crm-v3-propiedades-counters">

    <button
        type="button"
        class="crm-prop-counter is-active"
        data-filtro="todos"
    >
        <span>Todos</span>
        <strong>
            <?php echo count($estados_reales['todos']); ?>
        </strong>
    </button>


    <button
        type="button"
        class="crm-prop-counter"
        data-filtro="exclusiva"
    >
        <span>Exclusiva</span>
        <strong>
            <?php echo count($estados_reales['exclusiva']); ?>
        </strong>
    </button>


    <button
        type="button"
        class="crm-prop-counter"
        data-filtro="en_proceso"
    >
        <span>En proceso</span>
        <strong>
            <?php echo count($estados_reales['en_proceso']); ?>
        </strong>
    </button>


    <button
        type="button"
        class="crm-prop-counter"
        data-filtro="relacionada"
    >
        <span>Relacionada</span>
        <strong>
            <?php echo count($estados_reales['relacionada']); ?>
        </strong>
    </button>


    <button
        type="button"
        class="crm-prop-counter"
        data-filtro="sin_relacion"
    >
        <span>Sin relación</span>
        <strong>
            <?php echo count($estados_reales['sin_relacion']); ?>
        </strong>
    </button>


    <button
        type="button"
        class="crm-prop-counter"
        data-filtro="cerrada"
    >
        <span>Cerrada</span>
        <strong>
            <?php echo count($estados_reales['cerrada']); ?>
        </strong>
    </button>


    <button
        type="button"
        class="crm-prop-counter"
        data-filtro="perdida"
    >
        <span>Perdida</span>
        <strong>
            <?php echo count($estados_reales['perdida']); ?>
        </strong>
    </button>

</div>


 <!-- ==================================================
             FILTROS
================================================== -->

<div class="crm-v3-propiedades-filters">

    <form method="get">

        <input type="hidden" name="page" value="crm-propiedades">

        <select id="propiedad-colonia" name="colonia">
            <option value="">Colonia</option>
            <?php
            $terminos = get_terms(array(
                'taxonomy'   => 'fraccionamiento-o-colonia',
                'hide_empty' => true,
            ));

            foreach ($terminos as $termino) {
                ?>
                <option value="<?php echo esc_attr($termino->term_id); ?>"
                    <?php selected($colonia, $termino->term_id); ?>>
                    <?php echo esc_html($termino->name); ?>
                </option>
                <?php
            }
            ?>
        </select>


        <select id="propiedad-ciudad" name="ciudad">
            <option value="">Ciudad</option>
            <?php
            $terminos = get_terms(array(
                'taxonomy'   => 'ciudad',
                'hide_empty' => true,
            ));

            foreach ($terminos as $termino) {
                ?>
                <option value="<?php echo esc_attr($termino->term_id); ?>"
                    <?php selected($ciudad, $termino->term_id); ?>>
                    <?php echo esc_html($termino->name); ?>
                </option>
                <?php
            }
            ?>
        </select>


        <select id="propiedad-tipo-propiedad" name="tipo_propiedad">
            <option value="">Tipo de propiedad</option>
            <?php
            $terminos = get_terms(array(
                'taxonomy'   => 'tipo-de-propiedad',
                'hide_empty' => true,
            ));

            foreach ($terminos as $termino) {
                ?>
                <option value="<?php echo esc_attr($termino->term_id); ?>"
                    <?php selected($tipo_propiedad, $termino->term_id); ?>>
                    <?php echo esc_html($termino->name); ?>
                </option>
                <?php
            }
            ?>
        </select>


       <select id="propiedad-tipo-operacion" name="tipo_operacion">
    <option value="">Tipo de operación</option>

    <?php foreach (crm_v3_propiedades_opciones_acf_usadas('tipo_de_operacion') as $valor => $etiqueta) : ?>
        <option value="<?php echo esc_attr($valor); ?>"
            <?php selected($tipo_operacion, $valor); ?>>
            <?php echo esc_html($etiqueta); ?>
        </option>
    <?php endforeach; ?>
</select>


        <select id="propiedad-estado-inmueble" name="estado_inmueble">
    <option value="">Estado del inmueble</option>

    <?php foreach (crm_v3_propiedades_opciones_acf_usadas('estado_del_inmueble') as $valor => $etiqueta) : ?>
        <option value="<?php echo esc_attr($valor); ?>"
            <?php selected($estado_inmueble, $valor); ?>>
            <?php echo esc_html($etiqueta); ?>
        </option>
    <?php endforeach; ?>
</select>


        <select id="propiedad-estado-comercial" name="estado_comercial">
    <option value="">Estado comercial</option>

    <?php foreach (crm_v3_propiedades_opciones_acf_usadas('estado_comercial') as $valor => $etiqueta) : ?>
        <option value="<?php echo esc_attr($valor); ?>"
            <?php selected($estado_comercial, $valor); ?>>
            <?php echo esc_html($etiqueta); ?>
        </option>
    <?php endforeach; ?>
</select>


        <select id="propiedad-documentacion" name="documentacion">
    <option value="">Documentación</option>

    <?php foreach (crm_v3_propiedades_opciones_acf_usadas('documentacion') as $valor => $etiqueta) : ?>
        <option value="<?php echo esc_attr($valor); ?>"
            <?php selected($documentacion, $valor); ?>>
            <?php echo esc_html($etiqueta); ?>
        </option>
    <?php endforeach; ?>
</select>

    </form>

</div>


      <div class="crm-v3-propiedades-table-wrapper">

    <?php
    echo crm_v3_propiedades_render_table($propiedades);
    ?>

</div>

    </div>

    <?php

    wp_reset_postdata();
}


/**
 * ============================================================
 * TAXONOMÍAS ACF
 * ============================================================
 */

function crm_v3_propiedad_taxonomia($valor, $taxonomia) {

    if (!$valor) {
        return '—';
    }

    if (is_array($valor)) {

        $nombres = array();

        foreach ($valor as $term_id) {

            $term = get_term($term_id, $taxonomia);

            if ($term && !is_wp_error($term)) {
                $nombres[] = $term->name;
            }
        }

        return !empty($nombres)
            ? implode(', ', $nombres)
            : '—';
    }

    $term = get_term($valor, $taxonomia);

    if (!$term || is_wp_error($term)) {
        return '—';
    }

    return $term->name;
}


/**
 * ============================================================
 * FORMATO ESTATUS
 * No modifica el valor ACF.
 * Solo cambia la presentación.
 * ============================================================
 */

function crm_v3_propiedad_estatus_formato($valor) {

    if ($valor === '' || $valor === null) {
        return '—';
    }

    $valor = (string) $valor;

    return ucfirst(
        str_replace('_', ' ', $valor)
    );
}


/**
 * ============================================================
 * CLASE CSS ESTATUS
 * ============================================================
 */

function crm_v3_propiedad_estatus_clase($valor) {

    if ($valor === '' || $valor === null) {
        return 'sin-estatus';
    }

    return sanitize_html_class(
        strtolower(
            str_replace('_', '-', (string) $valor)
        )
    );
}