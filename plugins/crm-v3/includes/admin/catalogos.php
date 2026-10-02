<?php
/**
 * =========================================================
 * CRM CIBR — CATÁLOGOS
 * Página principal
 * =========================================================
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ---------------------------------------------------------
 * Render de tarjetas de catálogo
 * ---------------------------------------------------------
 */
function crm_v3_render_catalogo_cards($post_type, $titulo, $descripcion = '') {

    $query = new WP_Query(array(
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ));

    ?>

    <section class="crm-catalogos-section">

        <div class="crm-catalogos-section-header">

            <div>
                <h2><?php echo esc_html($titulo); ?></h2>

                <?php if ($descripcion) : ?>
                    <p><?php echo esc_html($descripcion); ?></p>
                <?php endif; ?>
            </div>

            <span class="crm-catalogos-count">
                <?php echo esc_html($query->post_count); ?>
            </span>

        </div>


        <?php if ($query->have_posts()) : ?>

            <div class="crm-catalogos-grid">

                <?php while ($query->have_posts()) : $query->the_post(); ?>

                    <?php
                    $nombre       = get_field('nombre');
                    $direccion    = get_field('direccion');
                    $telefono     = get_field('telefono');
                    $email        = get_field('email');
                    $web          = get_field('web');
                    $comentarios  = get_field('comentarios');

                    $nombre = $nombre ?: get_the_title();
                    ?>

                    <article class="crm-catalogo-card">

                        <div class="crm-catalogo-card-header">

                            <div class="crm-catalogo-card-icon">
                                <?php echo esc_html(mb_strtoupper(mb_substr($nombre, 0, 1))); ?>
                            </div>

                            
                            
                            <div class="crm-catalogo-card-title">

                                <h3>
                                    <?php echo esc_html(get_the_title()); ?>
                                </h3>
                            </div>

                        </div>


                        <div class="crm-catalogo-card-body">
                            
                            
                                <?php if ($nombre) : ?>
                                <div class="crm-catalogo-card-row">
                                    <span class="crm-catalogo-card-label">Nombre</span>
                                    <span class="crm-catalogo-card-value">
                                        <?php echo esc_html($nombre); ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if ($direccion) : ?>
                                <div class="crm-catalogo-card-row">
                                    <span class="crm-catalogo-card-label">Dirección</span>
                                    <span class="crm-catalogo-card-value">
                                        <?php echo esc_html($direccion); ?>
                                    </span>
                                </div>
                            <?php endif; ?>


                            <?php if ($telefono) : ?>
                                <div class="crm-catalogo-card-row">
                                    <span class="crm-catalogo-card-label">Teléfono</span>
                                    <span class="crm-catalogo-card-value">
                                        <?php echo esc_html($telefono); ?>
                                    </span>
                                </div>
                            <?php endif; ?>


                            <?php if ($email) : ?>
                                <div class="crm-catalogo-card-row">
                                    <span class="crm-catalogo-card-label">Email</span>
                                    <span class="crm-catalogo-card-value">
                                        <?php echo esc_html($email); ?>
                                    </span>
                                </div>
                            <?php endif; ?>


                            <?php if ($web) : ?>
                                <div class="crm-catalogo-card-row">
                                    <span class="crm-catalogo-card-label">Web</span>
                                    <a
                                        href="<?php echo esc_url($web); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="crm-catalogo-card-link"
                                    >
                                        Visitar sitio
                                    </a>
                                </div>
                            <?php endif; ?>


                            <?php if ($comentarios) : ?>
                                <div class="crm-catalogo-card-comments">
                                    <?php echo esc_html($comentarios); ?>
                                </div>
                            <?php endif; ?>

                        </div>

                    </article>

                <?php endwhile; ?>

            </div>

        <?php else : ?>

            <div class="crm-catalogos-empty">
                No hay registros capturados.
            </div>

        <?php endif; ?>

    </section>

    <?php

    wp_reset_postdata();
}


/**
 * =========================================================
 * PÁGINA PRINCIPAL
 * =========================================================
 */
function crm_v3_catalogos_page() {
    ?>

    <div class="crm-v3-catalogos">

        <!-- HEADER -->
        <div class="crm-catalogos-header">

            <div class="crm-catalogos-header-info">
                <h1>CATÁLOGOS</h1>
                <p>Directorio interno de proveedores y servicios inmobiliarios.</p>
            </div>

            <div class="crm-catalogos-header-actions">
                <?php
                $crm_catalogos_nuevos = array(
                    'valuadores' => '+ Valuador',
                    'bancos'     => '+ Banco',
                    'notarias'   => '+ Notaría',
                    'servicios'  => '+ Servicio',
                );
                ?>

                <?php foreach ($crm_catalogos_nuevos as $crm_tipo => $crm_texto) : ?>

                    <a
                        href="<?php echo esc_url(
                            admin_url('post-new.php?post_type=' . $crm_tipo)
                        ); ?>"
                        class="crm-catalogos-btn"
                    >
                        <?php echo esc_html($crm_texto); ?>
                    </a>

                <?php endforeach; ?>

            </div>

        </div>


        <!-- VALUADORES -->
        <?php
        crm_v3_render_catalogo_cards(
            'valuadores',
            'VALUADORES',
            'Directorio de valuadores registrados.'
        );
        ?>


        <!-- BANCOS -->
        <?php
        crm_v3_render_catalogo_cards(
            'bancos',
            'BANCOS',
            'Entidades financieras registradas.'
        );
        ?>


        <!-- NOTARÍAS -->
        <?php
        crm_v3_render_catalogo_cards(
            'notarias',
            'NOTARÍAS',
            'Directorio de notarías.'
        );
        ?>


        <!-- SERVICIOS -->
        <?php
        crm_v3_render_catalogo_cards(
            'servicios',
            'SERVICIOS',
            'Proveedores y servicios relacionados con la operación inmobiliaria.'
        );
        ?>

    </div>

    <?php
}