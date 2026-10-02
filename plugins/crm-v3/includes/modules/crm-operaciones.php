<?php
/**
 * CRM V3 - Operaciones
 */

if (!defined('ABSPATH')) {
    exit;
}

function crm_v3_operaciones_page() {

    $expediente_actual = isset($_GET['expediente'])
    ? sanitize_key($_GET['expediente'])
    : '';

    $operaciones_args = [
    'post_type'      => 'operaciones',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'meta_key'       => 'fecha_de_operacion',
    'orderby'        => 'meta_value_num',
    'order'          => 'DESC',
];

if ($expediente_actual !== '') {

    $operaciones_args['meta_query'] = [
        [
            'key'     => 'expediente_completo',
            'value'   => $expediente_actual,
            'compare' => '=',
        ],
    ];
}

$operaciones = get_posts($operaciones_args);

    $total = count($operaciones);
    $en_proceso = 0;
    $sin_expediente = 0;
    $cerradas = 0;
    $perdidas = 0;

    foreach ($operaciones as $operacion) {

        $id = $operacion->ID;

        $estatus = get_field('estatus_de_operecion', $id);
        $expediente = get_field('expediente_completo', $id);

        if ($estatus === 'en_proceso') {
            $en_proceso++;
        }

        if (!$expediente) {
            $sin_expediente++;
        }

        if ($estatus === 'cerrada') {
            $cerradas++;
        }

        if ($estatus === 'perdida') {
            $perdidas++;
        }
    }



$tipos_propiedad = [];

foreach ($operaciones as $operacion) {

    $propiedad = get_field('propiedad', $operacion->ID);

    if (!$propiedad) {
        continue;
    }

    $propiedad_id = $propiedad instanceof WP_Post
        ? $propiedad->ID
        : absint($propiedad);

    if (!$propiedad_id) {
        continue;
    }

    $tipo_propiedad = get_field(
        'tipo_de_propiedad',
        $propiedad_id
    );

    $tipo_nombre = crm_v3_propiedad_taxonomia(
        $tipo_propiedad,
        'tipo-de-propiedad'
    );

    if ($tipo_nombre !== '—') {
        $tipos_propiedad[$tipo_nombre] = $tipo_nombre;
    }
}

ksort($tipos_propiedad);



$instituciones_financieras = [];
$notarias = [];

foreach ($operaciones as $operacion) {

    $operacion_id = $operacion->ID;

    /* Institución financiera */
    $institucion = get_field(
        'institucion_financiera',
        $operacion_id
    );

    if (!empty($institucion)) {

        if (!is_array($institucion)) {
            $institucion = [$institucion];
        }

        foreach ($institucion as $term_id) {

            $term = get_term(
                (int) $term_id,
                'origen-de-recurso'
            );

            if ($term && !is_wp_error($term)) {
                $instituciones_financieras[$term->term_id] = $term->name;
            }
        }
    }


    /* Notaria */
    $notaria = get_field(
        'notaria',
        $operacion_id
    );

    $notaria_id = crm_v3_get_related_id($notaria);

    if ($notaria_id) {
        $notarias[$notaria_id] = get_the_title($notaria_id);
    }
}

asort($instituciones_financieras);
asort($notarias);


$campo_tipo_avaluo = acf_get_field(
    'tipo_de_avaluo'
);

$campo_estatus = acf_get_field(
    'estatus_de_operecion'
);

$tipos_avaluo = (
    is_array($campo_tipo_avaluo) &&
    !empty($campo_tipo_avaluo['choices'])
)
? $campo_tipo_avaluo['choices']
: [];

$estatus_operacion = (
    is_array($campo_estatus) &&
    !empty($campo_estatus['choices'])
)
? $campo_estatus['choices']
: [];



    ?>
    <div class="crm-operaciones-wrap">

        <div class="crm-operaciones-header">

            <div class="crm-operaciones-header-info">
                <h1>CRM Operaciones</h1>
                <p>Gestión y análisis de operaciones inmobiliarias</p>
            </div>

            <div class="crm-operaciones-header-actions">
                <a href="admin.php?page=crm-nuevos-registros" class="crm-operaciones-btn">
                    + Nuevos registros
                </a>
            </div>

        </div>



<!-- =====================================================
            CONTADORES Y FILTROS
===================================================== -->

        <div class="crm-operaciones-stats">

            <div class="crm-operaciones-stat stat-total" data-filtro="total">
                <span class="stat-value">
                    <?php echo esc_html($total); ?>
                </span>
                <span class="stat-label">Total</span>
            </div>


            <div class="crm-operaciones-stat stat-proceso" data-filtro="en_proceso">
                <span class="stat-value">
                    <?php echo esc_html($en_proceso); ?>
                </span>
                <span class="stat-label">En proceso</span>
            </div>


            <div class="crm-operaciones-stat stat-expediente" data-filtro="sin_expediente">
                <span class="stat-value">
                    <?php echo esc_html($sin_expediente); ?>
                </span>
                <span class="stat-label">Sin expediente</span>
            </div>


            <div class="crm-operaciones-stat stat-cerradas" data-filtro="cerrada">
                <span class="stat-value">
                    <?php echo esc_html($cerradas); ?>
                </span>
                <span class="stat-label">Cerradas</span>
            </div>


            <div class="crm-operaciones-stat stat-perdidas" data-filtro="perdida">
                <span class="stat-value">
                    <?php echo esc_html($perdidas); ?>
                </span>
                <span class="stat-label">Perdidas</span>
            </div>

        </div>

    </div>




<!-- =====================================================
                 FILTROS GENERALES
===================================================== -->

<div class="crm-operaciones-filters">

    <div class="crm-operaciones-filter">
    <label for="operacion-tipo-propiedad">Tipo de propiedad</label>

    <select id="operacion-tipo-propiedad">

        <option value="">Todos</option>

        <?php foreach ($tipos_propiedad as $tipo) : ?>

            <option value="<?php echo esc_attr($tipo); ?>">
                <?php echo esc_html($tipo); ?>
            </option>

        <?php endforeach; ?>

    </select>
</div>

    <div class="crm-operaciones-filter">
        <label for="operacion-expediente">Expediente</label>
        <select id="operacion-expediente">
            <option value="">Todos</option>
            <option value="1">Completo</option>
            <option value="0">Incompleto</option>
        </select>
    </div>

   <div class="crm-operaciones-filter">
    <label for="operacion-avaluo">
        Tipo de avalúo
    </label>

    <select id="operacion-avaluo">

        <option value="">Todos</option>

        <?php foreach ($tipos_avaluo as $valor => $nombre) : ?>

            <option value="<?php echo esc_attr($valor); ?>">
                <?php echo esc_html($nombre); ?>
            </option>

        <?php endforeach; ?>

    </select>
</div>

    <div class="crm-operaciones-filter">
    <label for="operacion-institucion">
        Institución financiera
    </label>

    <select id="operacion-institucion">

        <option value="">Todas</option>

        <?php foreach ($instituciones_financieras as $id => $nombre) : ?>

            <option value="<?php echo esc_attr($id); ?>">
                <?php echo esc_html($nombre); ?>
            </option>

        <?php endforeach; ?>

    </select>
</div>

    <div class="crm-operaciones-filter">
    <label for="operacion-notaria">
        Notaria
    </label>

    <select id="operacion-notaria">

        <option value="">Todas</option>

        <?php foreach ($notarias as $id => $nombre) : ?>

            <option value="<?php echo esc_attr($id); ?>">
                <?php echo esc_html($nombre); ?>
            </option>

        <?php endforeach; ?>

    </select>
</div>

    <div class="crm-operaciones-filter">
    <label for="operacion-estatus">
        Estatus de operación
    </label>

    <select id="operacion-estatus">

        <option value="">Todos</option>

        <?php foreach ($estatus_operacion as $valor => $nombre) : ?>

            <option value="<?php echo esc_attr($valor); ?>">
                <?php echo esc_html($nombre); ?>
            </option>

        <?php endforeach; ?>

    </select>
</div>

</div>


<!-- =====================================================
              RENDER TABLA CRM OPERACIONES
===================================================== -->

<div class="crm-operaciones-table-wrap">

    <?php echo crm_v3_operaciones_render_table($operaciones); ?>

</div>


    <?php
}


function crm_v3_operaciones_obtener_filtradas($filtros = []) {

    $operaciones = get_posts([
    'post_type'      => 'operaciones',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'meta_key'       => 'fecha_de_operacion',
    'orderby'        => 'meta_value_num',
    'order'          => 'DESC',
]);


    $operaciones_filtradas = [];


    foreach ($operaciones as $operacion) {

        $operacion_id = $operacion->ID;


/* =====================================================
 * FILTRO POR CONTADOR
 * ===================================================== */

if (
    isset($filtros['contador']) &&
    $filtros['contador'] !== '' &&
    $filtros['contador'] !== 'total'
) {

    $estatus = get_field(
        'estatus_de_operecion',
        $operacion_id
    );

    if (is_array($estatus)) {
        $estatus = reset($estatus);
    }

    if (
        is_object($estatus) &&
        isset($estatus->value)
    ) {
        $estatus = $estatus->value;
    }


    if ($filtros['contador'] === 'sin_expediente') {

        $expediente = get_field(
            'expediente_completo',
            $operacion_id
        );

        if ($expediente) {
            continue;
        }

    } else {

        if (
            (string) $estatus !==
            (string) $filtros['contador']
        ) {
            continue;
        }
    }
}



        /* =====================================================
         * EXPEDIENTE
         * ===================================================== */

        if (
            isset($filtros['expediente']) &&
            $filtros['expediente'] !== ''
        ) {

            $expediente = get_field(
                'expediente_completo',
                $operacion_id
            );

            $expediente = $expediente ? '1' : '0';

            if ($expediente !== $filtros['expediente']) {
                continue;
            }
        }


        /* =====================================================
         * TIPO DE AVALÚO
         * ===================================================== */

        if (
            !empty($filtros['tipo_avaluo'])
        ) {

            $tipo_avaluo = get_field(
                'tipo_de_avaluo',
                $operacion_id
            );

            if (is_array($tipo_avaluo)) {
                $tipo_avaluo = reset($tipo_avaluo);
            }

            if (
                is_object($tipo_avaluo) &&
                isset($tipo_avaluo->value)
            ) {
                $tipo_avaluo = $tipo_avaluo->value;
            }

            if (
                (string) $tipo_avaluo !==
                (string) $filtros['tipo_avaluo']
            ) {
                continue;
            }
        }


        /* =====================================================
         * INSTITUCIÓN FINANCIERA
         * ===================================================== */

        if (
            !empty($filtros['institucion'])
        ) {

            $institucion = get_field(
                'institucion_financiera',
                $operacion_id
            );

            $institucion_id = 0;

            if ($institucion instanceof WP_Term) {
                $institucion_id = (int) $institucion->term_id;
            } elseif (is_numeric($institucion)) {
                $institucion_id = (int) $institucion;
            }

            if (
                $institucion_id !==
                (int) $filtros['institucion']
            ) {
                continue;
            }
        }


        /* =====================================================
         * NOTARÍA
         * ===================================================== */

        if (
            !empty($filtros['notaria'])
        ) {

            $notaria = get_field(
                'notaria',
                $operacion_id
            );

            $notaria_id = crm_v3_get_related_id(
                $notaria
            );

            if (
                $notaria_id !==
                (int) $filtros['notaria']
            ) {
                continue;
            }
        }


        /* =====================================================
         * ESTATUS DE OPERACIÓN
         * ===================================================== */

        if (
            isset($filtros['estatus']) &&
            $filtros['estatus'] !== ''
        ) {

            $estatus = get_field(
                'estatus_de_operecion',
                $operacion_id
            );

            if (is_array($estatus)) {
                $estatus = reset($estatus);
            }

            if (
                is_object($estatus) &&
                isset($estatus->value)
            ) {
                $estatus = $estatus->value;
            }

            if (
                (string) $estatus !==
                (string) $filtros['estatus']
            ) {
                continue;
            }
        }


        /* =====================================================
         * TIPO DE PROPIEDAD
         * ===================================================== */

        if (
            !empty($filtros['tipo_propiedad'])
        ) {

            $propiedad = get_field(
                'propiedad',
                $operacion_id
            );

            $propiedad_id = crm_v3_get_related_id(
                $propiedad
            );

            if (!$propiedad_id) {
                continue;
            }

            $terminos = wp_get_post_terms(
                $propiedad_id,
                'tipo-de-propiedad',
                [
                    'fields' => 'ids',
                ]
            );

            if (is_wp_error($terminos)) {
                continue;
            }

            $tipo = get_term_by(
                'name',
                $filtros['tipo_propiedad'],
                'tipo-de-propiedad'
            );

            if (!$tipo) {
                continue;
            }

            if (
                !in_array(
                    (int) $tipo->term_id,
                    array_map('intval', $terminos),
                    true
                )
            ) {
                continue;
            }
        }


        $operaciones_filtradas[] = $operacion;
    }


    return $operaciones_filtradas;
}


/**
 * =========================================================
 *           TABLA DE CRM OPERACIONES
 * =========================================================
 */
function crm_v3_operaciones_render_table($operaciones) {
    ob_start();
    ?>

    <table class="crm-operaciones-table">

        <thead>
            <tr>
                <th>Propiedad</th>
                <th>Vendedor</th>
                <th>Comprador</th>
                <th>Colonia</th>
                <th>Tipo avalúo</th>
                <th>Financiamiento</th>
                <th>Notaría</th>
                <th>V. mercado</th>
                <th>Precio venta</th>
                <th>Fecha cierre</th>
                <th>Estatus</th>
            </tr>
        </thead>

        <tbody>

        <?php if (!empty($operaciones)) : ?>

            <?php foreach ($operaciones as $operacion) : ?>

                <?php
                $operacion_id = $operacion->ID;

                $comprador = get_field('comprador', $operacion_id);
                $propiedad = get_field('propiedad', $operacion_id);
                $tipo_avaluo = get_field('tipo_de_avaluo', $operacion_id);
                $institucion = get_field('institucion_financiera', $operacion_id);
                $notaria = get_field('notaria', $operacion_id);
                $valor_mercado = get_field('valor_mercado', $operacion_id);
                $precio_cierre = get_field('precio_de_cierre', $operacion_id);
                $fecha_cierre = get_field('fecha_cierre', $operacion_id);
                $estatus = get_field('estatus_de_operecion', $operacion_id);

                $propiedad_id = crm_v3_get_related_id($propiedad);

                $vendedor = $propiedad_id
                    ? get_field('cliente_propietario', $propiedad_id)
                    : null;

                $colonia = $propiedad_id
                    ? get_field('colonia_fraccionamiento', $propiedad_id)
                    : null;
                ?>

                <tr>

                    <td>
                        <?php
                        echo esc_html(
                            $propiedad_id
                                ? get_the_title($propiedad_id)
                                : '—'
                        );
                        ?>
                    </td>

                    <td>
                        <?php echo esc_html(
                            crm_v3_get_related_name($vendedor)
                        ); ?>
                    </td>

                    <td>
                        <?php echo esc_html(
                            crm_v3_get_related_name($comprador)
                        ); ?>
                    </td>

        

                    <td>
                        <?php echo esc_html(
                            crm_v3_display_value($colonia)
                        ); ?>
                    </td>

                    <td>
                        <?php echo esc_html(
                            crm_v3_display_value($tipo_avaluo)
                        ); ?>
                    </td>

                    <td>
                        <?php echo esc_html(
                            crm_v3_display_value($institucion)
                        ); ?>
                    </td>

                    <td>
                        <?php echo esc_html(
                            crm_v3_get_related_name($notaria)
                        ); ?>
                    </td>

                    <td class="crm-operaciones-money">
                        <?php echo esc_html(
                            crm_v3_format_money($valor_mercado)
                        ); ?>
                    </td>

                    <td class="crm-operaciones-money">
                        <?php echo esc_html(
                            crm_v3_format_money($precio_cierre)
                        ); ?>
                    </td>

                    <td>
                        <?php echo esc_html(
                            crm_v3_format_date($fecha_cierre)
                        ); ?>
                    </td>

                    <td>
    <?php
$estatus_clase = sanitize_key(
    is_string($estatus)
        ? $estatus
        : ''
);

$estatus_etiquetas = [
    'perdida'   => 'Perdida',
    'en_proceso' => 'En proceso',
    'cerrada'   => 'Cerrada',
];

$estatus_label = $estatus_etiquetas[$estatus_clase]
    ?? crm_v3_display_value($estatus);
?>

<span class="crm-operacion-status crm-operacion-status-<?php echo esc_attr($estatus_clase); ?>">
    <?php echo esc_html($estatus_label); ?>
</span>
</td>

                </tr>

            <?php endforeach; ?>

        <?php else : ?>

            <tr>
                <td colspan="11">
                    No hay operaciones que coincidan con los filtros.
                </td>
            </tr>

        <?php endif; ?>

        </tbody>

    </table>

    <?php

    return ob_get_clean();
}