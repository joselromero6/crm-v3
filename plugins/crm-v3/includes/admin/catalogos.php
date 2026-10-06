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
 * Catálogos que se muestran en esta página:
 * tipo de registro => nombre en singular.
 */
function crm_v3_catalogos_tipos() {

    return array(
        'valuadores' => 'Valuador',
        'bancos'     => 'Banco',
        'notarias'   => 'Notaría',
        'servicios'  => 'Servicio',
    );
}


/**
 * Texto de una tarjeta listo para enviar por WhatsApp.
 *
 * Los comentarios NO se incluyen: son notas internas.
 * En WhatsApp, el texto entre asteriscos se ve en negritas.
 */
function crm_v3_catalogo_texto_whatsapp($registro) {

    $tipos = crm_v3_catalogos_tipos();

    $lineas = array(
        '*' . $registro['titulo'] . '*',
    );

    if (isset($tipos[$registro['tipo']])) {
        $lineas[] = '_' . $tipos[$registro['tipo']] . '_';
    }

    $lineas[] = '';

    $datos = array(
        'nombre'    => "\u{1F464}",       // persona
        'direccion' => "\u{1F4CD}",       // ubicación
        'telefono'  => "\u{1F4DE}",       // teléfono
        'email'     => "\u{2709}\u{FE0F}", // sobre
        'web'       => "\u{1F310}",       // sitio web
    );

    foreach ($datos as $campo => $icono) {

        $valor = trim((string) $registro[$campo]);

        // El nombre no se repite si es igual al título.
        if ($campo === 'nombre' && $valor === $registro['titulo']) {
            continue;
        }

        if ($valor !== '') {
            $lineas[] = $icono . ' ' . $valor;
        }
    }

    return trim(implode("\n", $lineas));
}


/**
 * Enlace que abre WhatsApp con la tarjeta escrita, para elegir
 * a quién enviarla.
 */
function crm_v3_catalogo_url_whatsapp($registro) {

    return 'https://wa.me/?text=' . rawurlencode(
        crm_v3_catalogo_texto_whatsapp($registro)
    );
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

                    // Datos tal como están guardados (para compartir y editar).
                    $registro = array(
                        'id'          => get_the_ID(),
                        'tipo'        => $post_type,
                        // El título tal cual se escribió, sin adornos de WordPress.
                        'titulo'      => (string) get_post_field('post_title', get_the_ID(), 'raw'),
                        'nombre'      => (string) $nombre,
                        'direccion'   => (string) $direccion,
                        'telefono'    => (string) $telefono,
                        'email'       => (string) $email,
                        'web'         => (string) $web,
                        'comentarios' => (string) $comentarios,
                    );

                    $nombre = $nombre ?: get_the_title();
                    ?>

                    <article
                        class="crm-catalogo-card"
                        id="crm-catalogo-<?php echo esc_attr($registro['id']); ?>"
                    >

                        <div class="crm-catalogo-card-header">

                            <div class="crm-catalogo-card-icon">
                                <?php echo esc_html(mb_strtoupper(mb_substr($nombre, 0, 1))); ?>
                            </div>

                            
                            
                            <div class="crm-catalogo-card-title">

                                <h3>
                                    <?php echo esc_html(get_the_title()); ?>
                                </h3>
                            </div>

                            <div class="crm-catalogo-card-actions">

                                <a
                                    class="crm-catalogo-card-action crm-catalogo-card-action-whatsapp"
                                    href="<?php
                                        // esc_attr y no esc_url: esc_url borraría los saltos de línea del mensaje.
                                        echo esc_attr(crm_v3_catalogo_url_whatsapp($registro));
                                    ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    title="Compartir por WhatsApp"
                                    aria-label="Compartir por WhatsApp"
                                >
                                    <span class="dashicons dashicons-share-alt2"></span>
                                </a>

                                <?php if (current_user_can('manage_options')) : ?>

                                    <button
                                        type="button"
                                        class="crm-catalogo-card-action crm-catalogo-editar"
                                        title="Editar"
                                        aria-label="Editar"
                                        data-registro="<?php echo esc_attr(wp_json_encode($registro)); ?>"
                                    >
                                        <span class="dashicons dashicons-edit"></span>
                                    </button>

                                <?php endif; ?>

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

        <?php if (isset($_GET['catalogo'])) : ?>

            <div class="crm-catalogos-aviso<?php echo $_GET['catalogo'] === 'ok' ? '' : ' is-error'; ?>">
                <?php echo $_GET['catalogo'] === 'ok'
                    ? 'Los cambios se guardaron.'
                    : 'No se pudieron guardar los cambios. Revisa que el título no esté vacío.'; ?>
            </div>

        <?php endif; ?>

        <!-- HEADER -->
        <div class="crm-catalogos-header">

            <div class="crm-catalogos-header-info">
                <h1>CATÁLOGOS</h1>
                <p>Directorio interno de proveedores y servicios inmobiliarios.</p>
            </div>

            <div class="crm-catalogos-header-actions">
                <?php foreach (crm_v3_catalogos_tipos() as $crm_tipo => $crm_texto) : ?>

                    <?php $crm_texto = '+ ' . $crm_texto; ?>

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

        <?php crm_v3_catalogos_modal_editar(); ?>

    </div>

    <?php
}


/**
 * =========================================================
 * VENTANA PARA EDITAR UNA TARJETA
 * =========================================================
 *
 * Es una sola ventana para toda la página; al pulsar el lápiz
 * de una tarjeta se llena con los datos de esa tarjeta.
 */
function crm_v3_catalogos_modal_editar() {

    if (!current_user_can('manage_options')) {
        return;
    }

    ?>

    <dialog class="crm-catalogo-modal" id="crm-catalogo-modal">

        <form
            method="post"
            action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
            class="crm-catalogo-modal-form"
        >

            <input type="hidden" name="action" value="crm_v3_catalogo_guardar">
            <input type="hidden" name="registro_id" value="">

            <?php wp_nonce_field('crm_v3_catalogo_guardar', 'crm_v3_catalogo_nonce'); ?>

            <div class="crm-catalogo-modal-header">
                <h2>Editar <span class="crm-catalogo-modal-tipo"></span></h2>

                <button
                    type="button"
                    class="crm-catalogo-modal-cerrar"
                    aria-label="Cerrar"
                >&times;</button>
            </div>

            <div class="crm-catalogo-modal-body">

                <label>
                    <span>Título de la tarjeta</span>
                    <input type="text" name="titulo" required>
                </label>

                <label>
                    <span>Nombre</span>
                    <input type="text" name="nombre">
                </label>

                <label>
                    <span>Dirección</span>
                    <input type="text" name="direccion">
                </label>

                <div class="crm-catalogo-modal-dos">

                    <label>
                        <span>Teléfono</span>
                        <input type="text" name="telefono">
                    </label>

                    <label>
                        <span>Email</span>
                        <input type="email" name="email">
                    </label>

                </div>

                <label>
                    <span>Web</span>
                    <input type="url" name="web" placeholder="https://">
                </label>

                <label>
                    <span>Comentarios (notas internas, no se comparten)</span>
                    <textarea name="comentarios" rows="4"></textarea>
                </label>

            </div>

            <div class="crm-catalogo-modal-footer">

                <button type="button" class="crm-catalogo-modal-cancelar">
                    Cancelar
                </button>

                <button type="submit" class="crm-catalogos-btn">
                    Guardar cambios
                </button>

            </div>

        </form>

    </dialog>

    <?php
}


/**
 * =========================================================
 * GUARDAR LOS CAMBIOS DE UNA TARJETA
 * =========================================================
 */
function crm_v3_catalogo_guardar() {

    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para esta acción.');
    }

    check_admin_referer('crm_v3_catalogo_guardar', 'crm_v3_catalogo_nonce');

    $id = isset($_POST['registro_id'])
        ? absint($_POST['registro_id'])
        : 0;

    $regreso = admin_url('admin.php?page=crm-v3-catalogos');

    $titulo = isset($_POST['titulo'])
        ? sanitize_text_field(wp_unslash($_POST['titulo']))
        : '';

    // Solo se pueden editar registros de los catálogos.
    if (
        !$id ||
        $titulo === '' ||
        !array_key_exists(get_post_type($id), crm_v3_catalogos_tipos())
    ) {
        wp_safe_redirect(add_query_arg('catalogo', 'error', $regreso));
        exit;
    }

    wp_update_post(array(
        'ID'         => $id,
        'post_title' => wp_slash($titulo),
    ));

    $campos = array(
        'nombre'      => 'sanitize_text_field',
        'direccion'   => 'sanitize_text_field',
        'telefono'    => 'sanitize_text_field',
        'email'       => 'sanitize_email',
        'web'         => 'esc_url_raw',
        'comentarios' => 'sanitize_textarea_field',
    );

    foreach ($campos as $campo => $limpiar) {

        $valor = isset($_POST[$campo]) && is_string($_POST[$campo])
            ? call_user_func($limpiar, trim(wp_unslash($_POST[$campo])))
            : '';

        update_field($campo, $valor, $id);
    }

    wp_safe_redirect(
        add_query_arg('catalogo', 'ok', $regreso) . '#crm-catalogo-' . $id
    );

    exit;
}

add_action('admin_post_crm_v3_catalogo_guardar', 'crm_v3_catalogo_guardar');
