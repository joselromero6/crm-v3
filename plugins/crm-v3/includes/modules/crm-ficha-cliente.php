<?php

if (!defined('ABSPATH')) {
    exit;
}



/* ============================================================
 * BUSCAR OPERACIÓN DEL CLIENTE
 * ============================================================ */

function crm_v3_get_operacion_cliente($cliente_id) {

    $cliente_id = (int) $cliente_id;

    if (!$cliente_id) {
        return false;
    }


    /*
     * 1. Buscar operaciones donde el cliente sea comprador.
     */
    $operaciones = get_posts(array(
        'post_type'      => 'operaciones',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'comprador',
                'value'   => $cliente_id,
                'compare' => '=',
            ),
        ),
    ));

    if (!empty($operaciones)) {
        return $operaciones[0];
    }


    /*
     * 2. Buscar propiedades donde el cliente sea propietario/vendedor.
     */
    $propiedades = get_posts(array(
        'post_type'      => 'propiedades',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'cliente_propietario',
                'value'   => $cliente_id,
                'compare' => '=',
            ),
        ),
    ));

    if (empty($propiedades)) {
        return false;
    }


    /*
     * 3. Buscar una operación relacionada con cualquiera
     *    de las propiedades del cliente.
     */
    $property_ids = wp_list_pluck($propiedades, 'ID');

    $meta_query = array(
        'relation' => 'OR',
    );

    foreach ($property_ids as $property_id) {

        $meta_query[] = array(
            'key'     => 'propiedad',
            'value'   => $property_id,
            'compare' => '=',
        );
    }

    $operaciones = get_posts(array(
        'post_type'      => 'operaciones',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => $meta_query,
    ));

    if (!empty($operaciones)) {
        return $operaciones[0];
    }

    return false;
}


/* ============================================================
 * PROPIEDADES DEL CLIENTE
 * ============================================================ */

/**
 * Devuelve todas las propiedades pertenecientes a un cliente.
 *
 * Relación:
 * CLIENTE → PROPIEDAD
 *
 * ACF:
 * cliente_propietario
 */
function crm_v3_get_propiedades_cliente($cliente_id) {

    $cliente_id = absint($cliente_id);

    if (!$cliente_id) {
        return array();
    }

    return get_posts(array(
        'post_type'      => 'propiedades',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'     => 'cliente_propietario',
                'value'   => $cliente_id,
                'compare' => '=',
            ),
        ),
    ));
}


/* ============================================================
 * VALIDAR PROPIEDAD DEL CLIENTE
 * ============================================================ */

/**
 * Verifica que una propiedad realmente pertenezca al cliente.
 */
function crm_v3_propiedad_pertenece_cliente(
    $propiedad_id,
    $cliente_id
) {

    $propiedad_id = absint($propiedad_id);
    $cliente_id   = absint($cliente_id);

    if (!$propiedad_id || !$cliente_id) {
        return false;
    }

    if (get_post_type($propiedad_id) !== 'propiedades') {
        return false;
    }

    $propietario = get_field(
        'cliente_propietario',
        $propiedad_id
    );

    $propietario_id = crm_v3_get_related_id($propietario);

    return $propietario_id === $cliente_id;
}


/* ============================================================
 * OPERACIÓN DE UNA PROPIEDAD
 * ============================================================ */

/**
 * Busca la operación relacionada directamente con una propiedad.
 *
 * Relación:
 * CLIENTE → PROPIEDAD → OPERACIÓN
 *
 * ACF de operación:
 * propiedad
 */
function crm_v3_get_operacion_propiedad($propiedad_id) {

    $propiedad_id = absint($propiedad_id);

    if (!$propiedad_id) {
        return false;
    }

    $operaciones = get_posts(array(
        'post_type'      => 'operaciones',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'     => 'propiedad',
                'value'   => $propiedad_id,
                'compare' => '=',
            ),
        ),
    ));

    return !empty($operaciones)
        ? $operaciones[0]
        : false;
}


/* ============================================================
 * RENDER OPERACIÓN
 * ============================================================ */

function crm_v3_operacion_ficha($cliente_id, $propiedad_id = 0) {

    /*
     * Si existe una propiedad seleccionada,
     * la operación se obtiene directamente desde ella.
     *
     * CLIENTE
     *    ↓
     * PROPIEDAD SELECCIONADA
     *    ↓
     * OPERACIÓN
     */
    if ($propiedad_id) {

        $operacion = crm_v3_get_operacion_propiedad(
            $propiedad_id
        );

    } else {

        /*
         * Comportamiento anterior:
         * utilizado cuando no existe una propiedad
         * seleccionada, por ejemplo en otros contextos
         * de la ficha.
         */
        $operacion = crm_v3_get_operacion_cliente(
            $cliente_id
        );
    }

    /*
     * Si no existe operación, no mostramos el bloque.
     */
    if (!$operacion) {
        return;
    }

    $operacion_id = $operacion->ID;


    /* --------------------------------------------------------
     * DATOS DE LA OPERACIÓN
     * -------------------------------------------------------- */

    $fecha_operacion = get_field(
        'fecha_de_operacion',
        $operacion_id
    );

    $comprador = get_field(
        'comprador',
        $operacion_id
    );

    $propiedad = get_field(
        'propiedad',
        $operacion_id
    );

    $expediente_completo = get_field(
        'expediente_completo',
        $operacion_id
    );

    $valuador = get_field(
        'valuador',
        $operacion_id
    );

    $tipo_avaluo = get_field(
        'tipo_de_avaluo',
        $operacion_id
    );

    $institucion_financiera = get_field(
        'institucion_financiera',
        $operacion_id
    );

    $notaria = get_field(
        'notaria',
        $operacion_id
    );

    $estatus_operacion = get_field(
        'estatus_de_operecion',
        $operacion_id
    );

    $valor_mercado = get_field(
        'valor_mercado',
        $operacion_id
    );

    $precio_cierre = get_field(
        'precio_de_cierre',
        $operacion_id
    );

    $fecha_cierre = get_field(
        'fecha_cierre',
        $operacion_id
    );

    $operacion_cerrada = get_field(
        'operacion_cerrada',
        $operacion_id
    );


    /* --------------------------------------------------------
     * DATOS DE LA PROPIEDAD
     * -------------------------------------------------------- */

    $propiedad_id = crm_v3_get_related_id($propiedad);



    $vendedor = null;

    if ($propiedad_id) {

        $vendedor = get_field(
            'cliente_propietario',
            $propiedad_id
        );

    }


    $valor_catastral = $propiedad_id
        ? get_field('valor_catastral', $propiedad_id)
        : '';

    $precio_venta = $propiedad_id
        ? get_field('precio_de_venta', $propiedad_id)
        : '';

    $comision = $propiedad_id
        ? get_field('comision', $propiedad_id)
        : '';

    $direccion = $propiedad_id
        ? get_field('direccion', $propiedad_id)
        : '';


    $institucion_texto = crm_v3_display_value(
    $institucion_financiera
);


    /* --------------------------------------------------------
     * DATOS PARA MOSTRAR
     * -------------------------------------------------------- */

    $vendedor_nombre = crm_v3_get_related_name($vendedor);

    $comprador_nombre = crm_v3_get_related_name($comprador);

    $propiedad_nombre = crm_v3_get_related_name($propiedad);


    ?>
<section class="crm-v3-operacion">

    <!-- =====================================================
         ENCABEZADO FICHA DE OPERACION
         ===================================================== -->

    <div class="crm-v3-operacion-header">

        <div class="crm-v3-operacion-title">

            <span>OPERACIÓN</span>

            <?php if ($fecha_operacion): ?>

                <small>
                    <?php echo esc_html($fecha_operacion); ?>
                </small>

            <?php endif; ?>

        </div>


        <!-- BOTONES -->

        <div class="crm-v3-operacion-actions">


            <a
                class="crm-v3-operacion-button"
                href="<?php echo esc_url(
                    admin_url(
                        'admin.php?page=crm-expediente&operacion_id='
                        . $operacion_id
                        . '&cliente_id='
                        . $cliente_id
                    )
                ); ?>"
            >
                Expediente
            </a>


        </div>

    </div>


    <!-- =====================================================
         CUERPO
         ===================================================== -->

    <div class="crm-v3-operacion-body">

        <div class="crm-v3-operacion-layout">


            <!-- =================================================
                 DATOS DE LA OPERACIÓN
                 ================================================= -->

            <div class="crm-v3-operacion-data">

                <div class="crm-v3-operacion-grid">


                    <div class="crm-ficha-field">

                        <span>Vendedor</span>

                        <strong>
                            <?php echo esc_html($vendedor_nombre); ?>
                        </strong>

                    </div>

                    <div class="crm-ficha-field">

                        <span>Comprador</span>

                        <strong>
                            <?php echo esc_html($comprador_nombre); ?>
                        </strong>

                    </div>


                    <div class="crm-ficha-field">

                        <span>Institución financiera</span>

                        <strong>
                            <?php echo esc_html($institucion_texto); ?>
                        </strong>

                    </div>


                    <div class="crm-ficha-field">

                        <span>Valor catastral</span>

                        <strong>
                            <?php echo esc_html(
                                crm_v3_format_money($valor_catastral)
                            ); ?>
                        </strong>

                    </div>


                    <div class="crm-ficha-field">

                        <span>Precio de venta</span>

                        <strong>
                            <?php echo esc_html(
                                crm_v3_format_money($precio_venta)
                            ); ?>
                        </strong>

                    </div>


                    <div class="crm-ficha-field">

                        <span>Comisión</span>

                        <strong>
                            <?php echo esc_html(
                                crm_v3_format_money($comision)
                            ); ?>
                        </strong>

                    </div>


                    <div class="crm-ficha-field">

                        <span>Propiedad</span>

                        <strong>
                            <?php echo esc_html($propiedad_nombre); ?>
                        </strong>

                    </div>


                    <div class="crm-ficha-field">

                        <span>Dirección</span>

                        <strong>
                            <?php echo esc_html(
                                $direccion ?: '—'
                            ); ?>
                        </strong>

                    </div>


                    <div class="crm-ficha-field">

                        <span>Valor de mercado</span>

                        <strong>
                            <?php echo esc_html(
                                crm_v3_format_money($valor_mercado)
                            ); ?>
                        </strong>

                    </div>


                    <div class="crm-ficha-field">

                        <span>Precio de cierre</span>

                        <strong>
                            <?php echo esc_html(
                                crm_v3_format_money($precio_cierre)
                            ); ?>
                        </strong>

                    </div>


                    <div class="crm-ficha-field">

                        <span>Estatus de operación</span>

                        <strong>
                            <?php echo esc_html(
                                $estatus_operacion ?: '—'
                            ); ?>
                        </strong>

                    </div>


                    <div class="crm-ficha-field">

                        <span>Tipo de avalúo</span>

                        <strong>
                            <?php echo esc_html(
                                $tipo_avaluo ?: '—'
                            ); ?>
                        </strong>

                    </div>


                </div>

            </div>

        </div>

    </div>

</section>

<?php
}


/* ============================================================
 * FICHA DEL CLIENTE
 * ============================================================ */

function crm_v3_ficha_cliente_page() {

    $cliente_id = isset($_GET['cliente_id'])
        ? absint($_GET['cliente_id'])
        : 0;

    if (!$cliente_id) {
        echo '<div class="notice notice-error"><p>Cliente no válido.</p></div>';
        return;
    }

    $cliente = get_post($cliente_id);

    if (!$cliente || $cliente->post_type !== 'clientes') {
        echo '<div class="notice notice-error"><p>El cliente no existe.</p></div>';
        return;
    }


    /* ========================================================
     * CAMPOS ACF
     * ======================================================== */

    $fecha_registro     = get_field('fecha_de_registro', $cliente_id);
    $origen_lead        = get_field('origen_lead', $cliente_id);
    $domicilio          = get_field('domicilio', $cliente_id);
    $telefono           = get_field('telefono', $cliente_id);
    $email              = get_field('e-mail', $cliente_id);
    $tipo_cliente       = get_field('tipo_cliente', $cliente_id);
    $relacion_comercial = get_field('relacion_comercial', $cliente_id);
    $origen_recursos    = get_field('origen_recursos', $cliente_id);
    $tipo_credito       = get_field('tipo_credito', $cliente_id);
    $presupuesto        = get_field('presupuesto', $cliente_id);
    $adeudo_hipoteca    = get_field('adeudo_hipoteca', $cliente_id);
    $fecha_contacto     = get_field('fecha_de_contacto', $cliente_id);
    $prioridad          = get_field('prioridad', $cliente_id);
    $estatus            = get_field('estatus', $cliente_id);
    $notas              = get_field('notas', $cliente_id);

    ?>

    <div class="crm-v3-ficha">


        <!-- ==================================================
             ENCABEZADO
             ================================================== -->

        <div class="crm-v3-ficha-header">

            <span>FICHA DEL CLIENTE</span>

            <a
                href="<?php echo esc_url(
                    admin_url('admin.php?page=crm-clientes')
                ); ?>"
                class="crm-v3-ficha-back"
            >
                ← Volver a clientes
            </a>

        </div>


        <!-- ==================================================
             COLUMNAS: CLIENTE + PROPIEDAD
             ================================================== -->

        <div class="crm-v3-ficha-columns">


            <!-- ==================================================
                 DATOS DEL CLIENTE
                 ================================================== -->

            <section class="crm-v3-ficha-section">

                <div class="crm-v3-ficha-section-title">
                    DATOS DEL CLIENTE
                </div>

                <div class="crm-v3-ficha-section-content">

                    <div class="crm-v3-ficha-client-name">
                        <?php echo esc_html(
                            get_the_title($cliente_id)
                        ); ?>
                    </div>


                    <div class="crm-v3-ficha-grid">


                        <div class="crm-ficha-field">
                            <span>Fecha de registro</span>

                            <strong>
                                <?php echo esc_html(
                                    $fecha_registro ?: '—'
                                ); ?>
                            </strong>
                        </div>


                        <div class="crm-ficha-field">
                            <span>Origen del lead</span>

                            <strong>
                                <?php echo esc_html(
                                    crm_v3_display_value($origen_lead)
                                ); ?>
                            </strong>
                        </div>


                        <div class="crm-ficha-field">
                            <span>Domicilio</span>

                            <strong>
                                <?php echo esc_html(
                                    $domicilio ?: '—'
                                ); ?>
                            </strong>
                        </div>


                        <div class="crm-ficha-field">
                            <span>Teléfono</span>

                            <strong>
                                <?php echo esc_html(
                                    $telefono ?: '—'
                                ); ?>
                            </strong>
                        </div>


                        <div class="crm-ficha-field">
                            <span>E-mail</span>

                            <strong>
                                <?php echo esc_html(
                                    $email ?: '—'
                                ); ?>
                            </strong>
                        </div>


                        <div class="crm-ficha-field">
                            <span>Tipo de cliente</span>

                            <strong>
                                <?php echo esc_html(
                                    crm_v3_display_value($tipo_cliente)
                                ); ?>
                            </strong>
                        </div>


                        <div class="crm-ficha-field">
                            <span>Relación comercial</span>

                            <strong>
                                <?php echo esc_html(
                                    crm_v3_display_value(
                                        $relacion_comercial
                                    )
                                ); ?>
                            </strong>
                        </div>


                        <div class="crm-ficha-field">
                            <span>Origen de recursos</span>

                            <strong>
                                <?php echo esc_html(
                                    crm_v3_display_value(
                                        $origen_recursos
                                    )
                                ); ?>
                            </strong>
                        </div>


                        <div class="crm-ficha-field">
                            <span>Tipo de crédito</span>

                            <strong>
                                <?php echo esc_html(
                                    crm_v3_display_value(
                                        $tipo_credito
                                    )
                                ); ?>
                            </strong>
                        </div>


                        <div class="crm-ficha-field">
                            <span>Presupuesto</span>

                            <strong>

                            <?php
                                echo esc_html(
                                crm_v3_format_money($presupuesto)
                            );
                            ?>

                            </strong>
                        </div>


                        <div class="crm-ficha-field">
                            <span>Adeudo hipoteca</span>

                            <strong>

                                <?php

                                echo esc_html(
                                    crm_v3_format_money($adeudo_hipoteca)
                                ); ?>

                            </strong>
                        </div>


                        <div class="crm-ficha-field">
                            <span>Fecha de contacto</span>

                            <strong>
                                <?php echo esc_html(
                                    $fecha_contacto ?: '—'
                                ); ?>
                            </strong>
                        </div>


                        <div class="crm-ficha-field">
                            <span>Prioridad</span>

                            <strong>
                                <?php echo esc_html(
                                    crm_v3_display_value($prioridad)
                                ); ?>
                            </strong>
                        </div>


                        <div class="crm-ficha-field">
                            <span>Estatus</span>

                            <strong>
                                <?php echo esc_html(
                                    crm_v3_display_value($estatus)
                                ); ?>
                            </strong>
                        </div>


                    </div>


                    <div class="crm-v3-ficha-notas">

                        <span>Notas</span>

                        <div class="crm-v3-ficha-notas-content">

                            <?php if ($notas): ?>

                                <div class="crm-v3-ficha-notas-texto">
                                    <?php echo nl2br(esc_html($notas)); ?>
                                </div>

                            <?php endif; ?>

                            <?php
                            $cliente_comentarios = get_post_meta(
                                $cliente_id,
                                '_crm_v3_cliente_comentarios',
                                true
                            );

                            if (!is_array($cliente_comentarios)) {
                                $cliente_comentarios = array();
                            }
                            ?>

                            <form
                                method="post"
                                class="crm-v3-comentario-form"
                            >

                                <?php
                                wp_nonce_field(
                                    'crm_v3_guardar_comentario_cliente',
                                    'crm_v3_comentario_cliente_nonce'
                                );
                                ?>

                                <input
                                    type="hidden"
                                    name="cliente_id"
                                    value="<?php echo esc_attr($cliente_id); ?>"
                                >

                                <?php if (!empty($propiedad_id)): ?>

                                    <input
                                        type="hidden"
                                        name="propiedad_id"
                                        value="<?php echo esc_attr($propiedad_id); ?>"
                                    >

                                <?php endif; ?>

                                <textarea
                                    name="comentario"
                                    class="crm-v3-comentario-textarea"
                                    placeholder="Agregar comentario..."
                                    required
                                ></textarea>

                                <div class="crm-v3-comentarios-actions">

                                    <button
                                        type="submit"
                                        name="crm_v3_guardar_comentario_cliente"
                                        class="crm-v3-comentario-guardar"
                                    >
                                        Agregar comentario
                                    </button>

                                    <button
                                        type="button"
                                        class="crm-v3-comentarios-ver"
                                        data-modal="crm-v3-comentarios-cliente-modal-<?php echo esc_attr($cliente_id); ?>"
                                    >
                                        Ver comentarios
                                    </button>

                                </div>

                            </form>

                        </div>

                    </div>

                    <!-- =================================================
                         MODAL DE COMENTARIOS DEL CLIENTE
                         ================================================= -->

                    <div
                        id="crm-v3-comentarios-cliente-modal-<?php echo esc_attr($cliente_id); ?>"
                        class="crm-v3-comentarios-modal"
                        aria-hidden="true"
                    >

                        <div class="crm-v3-comentarios-modal-overlay"></div>

                        <div
                            class="crm-v3-comentarios-modal-window"
                            role="dialog"
                            aria-modal="true"
                            aria-label="Comentarios del cliente"
                        >

                            <div class="crm-v3-comentarios-modal-header">

                                <strong>
                                    COMENTARIOS
                                </strong>

                                <button
                                    type="button"
                                    class="crm-v3-comentarios-modal-close"
                                >
                                    ×
                                </button>

                            </div>

                            <div class="crm-v3-comentarios-modal-body">

                                <?php if (empty($cliente_comentarios)): ?>

                                    <div class="crm-v3-comentarios-vacio">
                                        No hay comentarios registrados.
                                    </div>

                                <?php else: ?>

                                    <?php foreach (array_reverse($cliente_comentarios) as $comentario): ?>

                                        <div class="crm-v3-comentario-item">

                                            <div class="crm-v3-comentario-meta">

                                                <strong>
                                                    <?php
                                                    echo esc_html(
                                                        $comentario['usuario'] ?? 'Usuario'
                                                    );
                                                    ?>
                                                </strong>

                                                <span>
                                                    <?php
                                                    echo esc_html(
                                                        ($comentario['fecha'] ?? '') .
                                                        ' ' .
                                                        ($comentario['hora'] ?? '')
                                                    );
                                                    ?>
                                                </span>

                                            </div>

                                            <div class="crm-v3-comentario-texto">

                                                <?php
                                                echo nl2br(
                                                    esc_html(
                                                        $comentario['texto'] ?? ''
                                                    )
                                                );
                                                ?>

                                            </div>

                                            <form
                                                method="post"
                                                class="crm-v3-comentario-eliminar-form"
                                            >

                                                <?php
                                                wp_nonce_field(
                                                    'crm_v3_eliminar_comentario_cliente',
                                                    'crm_v3_eliminar_comentario_cliente_nonce'
                                                );
                                                ?>

                                                <input
                                                    type="hidden"
                                                    name="cliente_id"
                                                    value="<?php echo esc_attr($cliente_id); ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="comentario_id"
                                                    value="<?php echo esc_attr(
                                                        $comentario['id'] ?? ''
                                                    ); ?>"
                                                >

                                                <?php if (!empty($propiedad_id)): ?>

                                                    <input
                                                        type="hidden"
                                                        name="propiedad_id"
                                                        value="<?php echo esc_attr($propiedad_id); ?>"
                                                    >

                                                <?php endif; ?>

                                                <button
                                                    type="submit"
                                                    name="crm_v3_eliminar_comentario_cliente"
                                                    class="crm-v3-comentario-eliminar"
                                                >
                                                    Eliminar
                                                </button>

                                            </form>

                                        </div>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function () {

                    const botones = document.querySelectorAll(
                        '.crm-v3-comentarios-ver'
                    );

                    botones.forEach(function (boton) {

                        const modalId = boton.getAttribute('data-modal');
                        const modal = document.getElementById(modalId);

                        if (!modal) {
                            return;
                        }

                        const cerrar = modal.querySelector(
                            '.crm-v3-comentarios-modal-close'
                        );

                        const overlay = modal.querySelector(
                            '.crm-v3-comentarios-modal-overlay'
                        );

                        boton.addEventListener('click', function () {

                            modal.classList.add('is-open');

                            modal.setAttribute(
                                'aria-hidden',
                                'false'
                            );

                            document.body.classList.add(
                                'crm-v3-modal-open'
                            );

                        });

                        function cerrarModal() {

                            modal.classList.remove('is-open');

                            modal.setAttribute(
                                'aria-hidden',
                                'true'
                            );

                            document.body.classList.remove(
                                'crm-v3-modal-open'
                            );

                        }

                        if (cerrar) {
                            cerrar.addEventListener(
                                'click',
                                cerrarModal
                            );
                        }

                        if (overlay) {
                            overlay.addEventListener(
                                'click',
                                cerrarModal
                            );
                        }

                    });

                    document.addEventListener(
                        'keydown',
                        function (event) {

                            if (event.key !== 'Escape') {
                                return;
                            }

                            document
                                .querySelectorAll(
                                    '.crm-v3-comentarios-modal.is-open'
                                )
                                .forEach(function (modal) {

                                    modal.classList.remove('is-open');

                                    modal.setAttribute(
                                        'aria-hidden',
                                        'true'
                                    );

                                });

                            document.body.classList.remove(
                                'crm-v3-modal-open'
                            );

                        }
                    );

                });
                </script>

            </section>


            <!-- ==================================================
                 CARD DE LA PROPIEDAD
                 ================================================== -->

            <section class="crm-v3-ficha-section">

                <div class="crm-v3-ficha-section-title">
                    INFORMACION DE LA PROPIEDAD
                </div>

               <div class="crm-v3-ficha-section-content">


    <?php

$tipo_cliente_ficha = crm_v3_display_value($tipo_cliente);

$propiedades_cliente = array();
$propiedad_id        = 0;


/* ============================================================
 * CLIENTE VENDEDOR
 * ============================================================ */

if (stripos($tipo_cliente_ficha, 'vendedor') !== false) {

    /*
     * Obtener todas las propiedades relacionadas
     * con este cliente.
     */
    $propiedades_cliente = crm_v3_get_propiedades_cliente(
        $cliente_id
    );

    /*
     * Propiedad solicitada mediante URL.
     */
    $propiedad_solicitada = isset($_GET['propiedad_id'])
        ? absint($_GET['propiedad_id'])
        : 0;

    /*
     * Si la propiedad solicitada pertenece al cliente,
     * utilizarla.
     */
    if (
        $propiedad_solicitada &&
        crm_v3_propiedad_pertenece_cliente(
            $propiedad_solicitada,
            $cliente_id
        )
    ) {

        $propiedad_id = $propiedad_solicitada;

    /*
     * Si no existe selección válida,
     * utilizar la primera propiedad.
     */
    } elseif (!empty($propiedades_cliente)) {

        $propiedad_id = (int) $propiedades_cliente[0]->ID;
    }
}

?>


    <?php if ($propiedad_id) : ?>

        <?php

        $prop_direccion          = get_field('direccion', $propiedad_id);
        $prop_colonia            = get_field('colonia_fraccionamiento', $propiedad_id);        
        $prop_ciudad             = get_field('ciudad', $propiedad_id);
        $prop_tipo_operacion     = get_field('tipo_de_operacion', $propiedad_id);
        $prop_estado_inmueble    = get_field('estado_del_inmueble', $propiedad_id);
        $prop_estado_comercial   = get_field('estado_comercial', $propiedad_id);
        $prop_documentacion      = get_field('documentacion', $propiedad_id);
        $prop_tipo                = get_field('tipo_de_propiedad', $propiedad_id);
        $prop_recamaras           = get_field('recamaras', $propiedad_id);
        $prop_banos               = get_field('banos', $propiedad_id);
        $prop_cochera             = get_field('cochera', $propiedad_id);
        $prop_m2_terreno          = get_field('m2_terreno', $propiedad_id);
        $prop_m2_construccion     = get_field('m2_construccion', $propiedad_id);
        $prop_adeudo_agua         = get_field('adeudo_agua', $propiedad_id);
        $prop_adeudo_predial      = get_field('adeudo_predial', $propiedad_id);
        $prop_rehabilitacion      = get_field('costo_rehabilitacion', $propiedad_id);
        $prop_tipo_cartera        = get_field('tipo_de_cartera', $propiedad_id);
        $prop_valor_catastral     = get_field('valor_catastral', $propiedad_id);
        $prop_precio_venta        = get_field('precio_de_venta', $propiedad_id);
        $prop_estatus             = get_field('estatus', $propiedad_id);

        ?>


        <div class="crm-v3-ficha-property-header">

        <!--   <div class="crm-v3-ficha-property-selector-label">
        PROPIEDADES DEL CLIENTE
        </div>   -->

    <div class="crm-v3-ficha-property-selector">

        <?php foreach ($propiedades_cliente as $propiedad) : ?>

            <?php
            $propiedad_item_id = (int) $propiedad->ID;

            $url_propiedad = add_query_arg(
                array(
                    'page'         => 'crm-ficha-cliente',
                    'cliente_id'   => $cliente_id,
                    'propiedad_id' => $propiedad_item_id,
                ),
                admin_url('admin.php')
            );

            $propiedad_activa =
                $propiedad_item_id === $propiedad_id;
            ?>

            <a
                href="<?php echo esc_url($url_propiedad); ?>"
                class="crm-v3-ficha-property-tab <?php echo $propiedad_activa ? 'is-active' : ''; ?>"
            >
                <?php echo esc_html(
                    get_the_title($propiedad_item_id)
                ); ?>
            </a>

        <?php endforeach; ?>

    </div>

</div>


        <div class="crm-v3-ficha-property-grid">


            <div class="crm-ficha-field">
                <span>Dirección</span>
                <strong>
                    <?php echo esc_html($prop_direccion ?: '—'); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Colonia / Fraccionamiento</span>
                <strong>
                    <?php echo esc_html(
                        crm_v3_display_value($prop_colonia)
                    ); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Ciudad</span>
                <strong>
                    <?php echo esc_html(
                        crm_v3_display_value($prop_ciudad)
                    ); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Tipo de operación</span>
                <strong>
                    <?php echo esc_html(
                        crm_v3_display_value($prop_tipo_operacion)
                    ); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Tipo de propiedad</span>
                <strong>
                    <?php echo esc_html(
                        crm_v3_display_value($prop_tipo)
                    ); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Estado del inmueble</span>
                <strong>
                    <?php echo esc_html(
                        crm_v3_display_value($prop_estado_inmueble)
                    ); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Estado comercial</span>
                <strong>
                    <?php echo esc_html(
                        crm_v3_display_value($prop_estado_comercial)
                    ); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Documentación</span>
                <strong>
                    <?php echo esc_html(
                        crm_v3_display_value($prop_documentacion)
                    ); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Recámaras</span>
                <strong>
                    <?php echo esc_html($prop_recamaras ?: '—'); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Baños</span>
                <strong>
                    <?php echo esc_html($prop_banos ?: '—'); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Cochera / vehículos</span>
                <strong>
                    <?php echo esc_html($prop_cochera ?: '—'); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>M² terreno</span>
                <strong>
                    <?php echo esc_html($prop_m2_terreno ?: '—'); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>M² construcción</span>
                <strong>
                    <?php echo esc_html($prop_m2_construccion ?: '—'); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Tipo de cartera</span>
                <strong>
                    <?php echo esc_html(
                        crm_v3_display_value($prop_tipo_cartera)
                    ); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Valor catastral</span>
                <strong>
                    <?php echo esc_html(
                        crm_v3_format_money($prop_valor_catastral)
                ); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Precio de venta</span>
                <strong>
                    <?php echo esc_html(
                        crm_v3_format_money($prop_precio_venta)
                    ); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Adeudo agua</span>
                <strong>
                    <?php echo esc_html(
                        crm_v3_format_money($prop_adeudo_agua)
                    ); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Adeudo predial</span>
                <strong>
                    <?php echo esc_html(
                        crm_v3_format_money($prop_adeudo_predial)
                    ); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Costo rehabilitación</span>
                <strong>
                    <?php echo esc_html(
                        crm_v3_format_money($prop_rehabilitacion)
                    ); ?>
                </strong>
            </div>


            <div class="crm-ficha-field">
                <span>Estatus</span>
                <strong>
                    <?php echo esc_html(
                        crm_v3_display_value($prop_estatus)
                    ); ?>
                </strong>
            </div>


        </div>


    <?php elseif (stripos($tipo_cliente_ficha, 'vendedor') !== false) : ?>

        <div class="crm-v3-ficha-empty">

            Este cliente es vendedor, actualmente
            no tiene un comprador relacionado.

        </div>


    <?php else : ?>

        <div class="crm-v3-ficha-empty">

            Este cliente Comprador, no tiene una propiedad asociada.
            Revisa si existe una relación con un vendedor y una Operación Activa.

        </div>

    <?php endif; ?>

</div>

            </section>


        </div>


        <?php
crm_v3_operacion_ficha(
    $cliente_id,
    $propiedad_id
);
?>


    <!-- =========================================================
     PIPELINE
     ========================================================= -->

<section class="crm-v3-ficha-section">

    <div class="crm-v3-ficha-section-title">
    PIPELINE
</div>

<div class="crm-v3-ficha-section-body">

    <?php

    /*
     * Utilizamos exactamente la misma lógica que utiliza
     * la ficha de operación.
     *
     * Esto permite que el pipeline aparezca tanto para:
     *
     * - comprador
     * - vendedor / propietario
     *
     * siempre que exista una operación relacionada.
     */

        /*
     * ============================================================
     * OPERACIÓN DEL PIPELINE
     * ============================================================
     *
     * Para vendedores:
     * CLIENTE → PROPIEDAD SELECCIONADA → OPERACIÓN → PIPELINE
     *
     * Para otros casos:
     * se conserva la lógica existente.
     */

    if (
        isset($propiedad_id) &&
        $propiedad_id
    ) {

        $operacion_pipeline = crm_v3_get_operacion_propiedad(
            $propiedad_id
        );

    } else {

        $operacion_pipeline = crm_v3_get_operacion_cliente(
            $cliente_id
        );
    }


    if ($operacion_pipeline) {

        crm_v3_pipeline_operacion(
            $operacion_pipeline->ID
        );

    } else {

        echo '<div class="crm-v3-pipeline-vacio">';
        echo 'No hay una operación activa para la propiedad seleccionada. El pipeline aparecerá cuando exista una operación.';
        echo '</div>';

    }

    ?>

</div>




<?php
}


/* ============================================================
 * COMENTARIOS DEL CLIENTE
 * ============================================================ */

/**
 * Guardar comentario de un cliente.
 */
function crm_v3_guardar_comentario_cliente() {

    if (
        !isset($_POST['crm_v3_comentario_cliente_nonce']) ||
        !wp_verify_nonce(
            $_POST['crm_v3_comentario_cliente_nonce'],
            'crm_v3_guardar_comentario_cliente'
        )
    ) {
        return;
    }

    if (!current_user_can('edit_posts')) {
        return;
    }

    $cliente_id = isset($_POST['cliente_id'])
        ? absint($_POST['cliente_id'])
        : 0;

    $comentario = isset($_POST['comentario'])
        ? sanitize_textarea_field($_POST['comentario'])
        : '';

    if (!$cliente_id || !$comentario) {
        return;
    }

    if (get_post_type($cliente_id) !== 'clientes') {
        return;
    }

    $comentarios = get_post_meta(
        $cliente_id,
        '_crm_v3_cliente_comentarios',
        true
    );

    if (!is_array($comentarios)) {
        $comentarios = array();
    }

    $usuario = wp_get_current_user();

    $comentarios[] = array(
        'id'       => wp_generate_uuid4(),
        'texto'    => $comentario,
        'fecha'    => current_time('d/m/Y'),
        'hora'     => current_time('H:i'),
        'usuario'  => $usuario->display_name
            ? $usuario->display_name
            : 'Usuario',
    );

    update_post_meta(
        $cliente_id,
        '_crm_v3_cliente_comentarios',
        $comentarios
    );
}


/**
 * Eliminar comentario de un cliente.
 */
function crm_v3_eliminar_comentario_cliente() {

    if (
        !isset($_POST['crm_v3_eliminar_comentario_cliente_nonce']) ||
        !wp_verify_nonce(
            $_POST['crm_v3_eliminar_comentario_cliente_nonce'],
            'crm_v3_eliminar_comentario_cliente'
        )
    ) {
        return;
    }

    if (!current_user_can('edit_posts')) {
        return;
    }

    $cliente_id = isset($_POST['cliente_id'])
        ? absint($_POST['cliente_id'])
        : 0;

    $comentario_id = isset($_POST['comentario_id'])
        ? sanitize_text_field($_POST['comentario_id'])
        : '';

    if (!$cliente_id || !$comentario_id) {
        return;
    }

    if (get_post_type($cliente_id) !== 'clientes') {
        return;
    }

    $comentarios = get_post_meta(
        $cliente_id,
        '_crm_v3_cliente_comentarios',
        true
    );

    if (!is_array($comentarios)) {
        return;
    }

    $nuevos_comentarios = array();

    foreach ($comentarios as $comentario) {

        if (
            isset($comentario['id']) &&
            $comentario['id'] === $comentario_id
        ) {
            continue;
        }

        $nuevos_comentarios[] = $comentario;
    }

    update_post_meta(
        $cliente_id,
        '_crm_v3_cliente_comentarios',
        $nuevos_comentarios
    );
}


/**
 * Procesar acciones de comentarios antes de mostrar la ficha.
 */
function crm_v3_procesar_comentarios_cliente() {

    if (!is_admin()) {
        return;
    }

    if (
        isset($_POST['crm_v3_guardar_comentario_cliente'])
    ) {

        crm_v3_guardar_comentario_cliente();

        $cliente_id = isset($_POST['cliente_id'])
            ? absint($_POST['cliente_id'])
            : 0;

        $propiedad_id = isset($_POST['propiedad_id'])
            ? absint($_POST['propiedad_id'])
            : 0;

        if ($cliente_id) {

            $args = array(
                'page'       => 'crm-ficha-cliente',
                'cliente_id' => $cliente_id,
                'comentario' => 'guardado',
            );

            if ($propiedad_id) {
                $args['propiedad_id'] = $propiedad_id;
            }

            wp_safe_redirect(
                add_query_arg(
                    $args,
                    admin_url('admin.php')
                )
            );

            exit;
        }
    }


    if (
        isset($_POST['crm_v3_eliminar_comentario_cliente'])
    ) {

        crm_v3_eliminar_comentario_cliente();

        $cliente_id = isset($_POST['cliente_id'])
            ? absint($_POST['cliente_id'])
            : 0;

        $propiedad_id = isset($_POST['propiedad_id'])
            ? absint($_POST['propiedad_id'])
            : 0;

        if ($cliente_id) {

            $args = array(
                'page'       => 'crm-ficha-cliente',
                'cliente_id' => $cliente_id,
                'comentario' => 'eliminado',
            );

            if ($propiedad_id) {
                $args['propiedad_id'] = $propiedad_id;
            }

            wp_safe_redirect(
                add_query_arg(
                    $args,
                    admin_url('admin.php')
                )
            );

            exit;
        }
    }
}

add_action(
    'admin_init',
    'crm_v3_procesar_comentarios_cliente'
);
