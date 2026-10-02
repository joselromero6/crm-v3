<?php
/**
 * CRM V3 - Widget Escritorio WordPress
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * ============================================================
 * REGISTRAR WIDGET EN EL ESCRITORIO DE WORDPRESS
 * ============================================================
 */

add_action(
    'wp_dashboard_setup',
    'crm_v3_registrar_dashboard_widget'
);

function crm_v3_registrar_dashboard_widget() {

    if (!current_user_can('manage_options')) {
        return;
    }

    wp_add_dashboard_widget(
        'crm_v3_dashboard_widget',
        'CRM inmobiliario',
        'crm_v3_render_dashboard_widget'
    );
}


/**
 * ============================================================
 * CONTADORES CRM
 * ============================================================
 */

function crm_v3_dashboard_contadores() {

    /* --------------------------------------------------------
     * CLIENTES
     * -------------------------------------------------------- */

    $clientes = new WP_Query([
        'post_type'      => 'clientes',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
    ]);

    $total_clientes = (int) $clientes->found_posts;


    /* --------------------------------------------------------
     * CLIENTES PARA RELACIONAR
     * -------------------------------------------------------- */

    $clientes_relacionar = new WP_Query([
        'post_type'      => 'clientes',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'meta_query'     => [
            [
                'key'     => 'estatus',
                'value'   => 'para_relacionar',
                'compare' => '=',
            ],
        ],
    ]);

    $para_relacionar = (int) $clientes_relacionar->found_posts;


    /* --------------------------------------------------------
     * PROPIEDADES PARA RELACIONAR
     * -------------------------------------------------------- */

    $propiedades_relacionar = new WP_Query([
        'post_type'      => 'propiedades',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'meta_query'     => [
            [
                'key'     => 'estatus',
                'value'   => 'para_relacionar',
                'compare' => '=',
            ],
        ],
    ]);

    $propiedades_para_relacionar =
        (int) $propiedades_relacionar->found_posts;


    /* --------------------------------------------------------
     * OPERACIONES EN PROCESO
     * -------------------------------------------------------- */

    $operaciones_proceso = new WP_Query([
        'post_type'      => 'operaciones',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'meta_query'     => [
            [
                'key'     => 'estatus_de_operacion',
                'value'   => 'en_proceso',
                'compare' => '=',
            ],
        ],
    ]);

    $operaciones_en_proceso =
        (int) $operaciones_proceso->found_posts;


    return [
        'clientes'               => $total_clientes,
        'para_relacionar'        => $para_relacionar,
        'propiedades_relacionar' => $propiedades_para_relacionar,
        'operaciones_proceso'    => $operaciones_en_proceso,
    ];
}


/**
 * ============================================================
 * RENDER DEL WIDGET
 * ============================================================
 */

function crm_v3_render_dashboard_widget() {

    $contadores = crm_v3_dashboard_contadores();

    ?>

    <div class="crm-dashboard-widget">

        <div class="crm-dashboard-header">

            <div>
                <p>
                    Resumen de actividad y operaciones
                </p>
            </div>

        </div>


        <div class="crm-dashboard-stats">


            <!-- PARA RELACIONAR -->

            <a
                href="<?php echo esc_url(
                    admin_url(
                        'admin.php?page=crm-clientes'
                    )
                ); ?>"
                class="crm-dashboard-stat"
            >

                <div class="crm-dashboard-stat-icon">
                    <span class="dashicons dashicons-randomize"></span>
                </div>

                <div class="crm-dashboard-stat-content">

                    <span class="crm-dashboard-stat-label">
                        C relacionar
                    </span>

                    <strong class="crm-dashboard-stat-number">
                        <?php
                        echo esc_html(
                            number_format(
                                $contadores['para_relacionar']
                            )
                        );
                        ?>
                    </strong>

                </div>

                <span class="crm-dashboard-stat-arrow">
                    →
                </span>

            </a>


            <!-- PROPIEDADES PARA RELACIONAR -->

            <a
                href="<?php echo esc_url(
                    admin_url(
                        'admin.php?page=crm-propiedades'
                    )
                ); ?>"
                class="crm-dashboard-stat"
            >

                <div class="crm-dashboard-stat-icon">
                    <span class="dashicons dashicons-building"></span>
                </div>

                <div class="crm-dashboard-stat-content">

                    <span class="crm-dashboard-stat-label">
                        Prop relacionar
                    </span>

                    <strong class="crm-dashboard-stat-number">
                        <?php
                        echo esc_html(
                            number_format(
                                $contadores['propiedades_relacionar']
                            )
                        );
                        ?>
                    </strong>

                </div>

                <span class="crm-dashboard-stat-arrow">
                    →
                </span>

            </a>


            <!-- OPERACIONES EN PROCESO -->

            <a
                href="<?php echo esc_url(
                    admin_url(
                        'admin.php?page=crm-operaciones'
                    )
                ); ?>"
                class="crm-dashboard-stat"
            >

                <div class="crm-dashboard-stat-icon">
                    <span class="dashicons dashicons-update-alt"></span>
                </div>

                <div class="crm-dashboard-stat-content">

                    <span class="crm-dashboard-stat-label">
                        Op en proceso
                    </span>

                    <strong class="crm-dashboard-stat-number">
                        <?php
                        echo esc_html(
                            number_format(
                                $contadores['operaciones_proceso']
                            )
                        );
                        ?>
                    </strong>

                </div>

                <span class="crm-dashboard-stat-arrow">
                    →
                </span>

            </a>


        </div>

    </div>

    <?php
}