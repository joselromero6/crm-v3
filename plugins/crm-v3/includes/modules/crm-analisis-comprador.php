<?php
/**
 * ============================================================
 * ANÁLISIS FINANCIERO DEL CLIENTE COMPRADOR
 * ============================================================
 *
 * Tarjeta de la ficha del cliente, debajo del cálculo de ISR.
 *
 * Muestra el tipo de crédito y el presupuesto del comprador de la
 * operación, y permite capturar a mano dos gastos:
 *
 * - Costo del avalúo
 * - Gastos de impuestos y derechos
 *
 * Total = Presupuesto + Costo del avalúo + Impuestos y derechos.
 *
 * Los dos gastos se guardan en la operación (campos ACF
 * "costo_avaluo" y "gastos_impuestos_derechos").
 * ============================================================
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Identificador del formulario de una operación.
 */
function crm_v3_comprador_form_id($operacion_id) {
    return 'crm-v3-comprador-form-' . absint($operacion_id);
}


/**
 * Formulario (invisible) al que pertenecen los campos de la tarjeta.
 *
 * Se coloca ANTES del cálculo de ISR. El formulario del ISR no se
 * cierra dentro de su módulo, y un formulario escrito después de él
 * sería ignorado por el navegador. Los campos de la tarjeta se
 * enlazan a este formulario con el atributo form="…".
 */
function crm_v3_comprador_formulario($operacion_id) {

    if (!current_user_can('manage_options')) {
        return;
    }

    ?>

    <form
        method="post"
        id="<?php echo esc_attr(crm_v3_comprador_form_id($operacion_id)); ?>"
        hidden
    >

        <?php
        wp_nonce_field(
            'crm_v3_guardar_comprador',
            'crm_v3_comprador_nonce',
            true
        );
        ?>

        <input type="hidden" name="crm_v3_guardar_comprador" value="1">

        <input
            type="hidden"
            name="crm_v3_comprador_operacion_id"
            value="<?php echo esc_attr($operacion_id); ?>"
        >

        <input
            type="hidden"
            name="cliente_id"
            value="<?php echo esc_attr(
                isset($_GET['cliente_id']) ? absint($_GET['cliente_id']) : 0
            ); ?>"
        >

        <input
            type="hidden"
            name="propiedad_id"
            value="<?php echo esc_attr(
                isset($_GET['propiedad_id']) ? absint($_GET['propiedad_id']) : 0
            ); ?>"
        >

    </form>

    <?php
}


/**
 * Número capturado → cantidad válida (0 o más), o '' si está vacío.
 */
function crm_v3_comprador_monto($valor) {

    $valor = trim(str_replace(array('$', ',', ' '), '', (string) $valor));

    if ($valor === '' || !is_numeric($valor)) {
        return '';
    }

    return max(0, round((float) $valor, 2));
}


/**
 * ============================================================
 * TARJETA
 * ============================================================
 */
function crm_v3_analisis_comprador($operacion_id) {

    $operacion_id = absint($operacion_id);

    if (!$operacion_id) {
        return;
    }

    $comprador_id = crm_v3_get_related_id(
        get_field('comprador', $operacion_id)
    );

    $tipo_credito = '—';
    $presupuesto  = '';

    if ($comprador_id) {

        $tipo_credito = crm_v3_nombres_terminos(
            get_field('tipo_credito', $comprador_id),
            'tipo-de-creditos'
        );

        $presupuesto = get_field('presupuesto', $comprador_id);
    }

    $costo_avaluo = get_field('costo_avaluo', $operacion_id);
    $gastos       = get_field('gastos_impuestos_derechos', $operacion_id);

    $total =
        (is_numeric($presupuesto) ? (float) $presupuesto : 0) +
        (is_numeric($costo_avaluo) ? (float) $costo_avaluo : 0) +
        (is_numeric($gastos) ? (float) $gastos : 0);

    $form_id       = crm_v3_comprador_form_id($operacion_id);
    $puede_editar  = current_user_can('manage_options');

    ?>

    <div
        class="crm-v3-isr crm-v3-comprador"
        data-presupuesto="<?php echo esc_attr(is_numeric($presupuesto) ? (float) $presupuesto : 0); ?>"
    >

        <div class="crm-v3-isr-title">
            ANÁLISIS FINANCIERO CLIENTE COMPRADOR
        </div>

        <div class="crm-v3-isr-note">
            <?php if ($comprador_id) : ?>
                Comprador: <?php echo esc_html(crm_v3_titulo_plano($comprador_id)); ?>
            <?php else : ?>
                Esta operación todavía no tiene comprador asignado.
            <?php endif; ?>
        </div>

        <div class="crm-v3-isr-section">

            <div class="crm-v3-isr-grid">

                <div class="crm-v3-isr-item">
                    <span>Tipo de crédito</span>
                    <strong><?php echo esc_html($tipo_credito); ?></strong>
                </div>

                <div class="crm-v3-isr-item">
                    <span>Presupuesto</span>
                    <strong><?php echo esc_html(crm_v3_format_money($presupuesto)); ?></strong>
                </div>

                <label class="crm-v3-isr-item crm-v3-comprador-campo">
                    <span>Costo del avalúo</span>
                    <input
                        type="number"
                        name="costo_avaluo"
                        form="<?php echo esc_attr($form_id); ?>"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        value="<?php echo esc_attr($costo_avaluo); ?>"
                        <?php disabled(!$puede_editar); ?>
                    >
                </label>

                <label class="crm-v3-isr-item crm-v3-comprador-campo">
                    <span>Gastos de impuestos y derechos</span>
                    <input
                        type="number"
                        name="gastos_impuestos_derechos"
                        form="<?php echo esc_attr($form_id); ?>"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        value="<?php echo esc_attr($gastos); ?>"
                        <?php disabled(!$puede_editar); ?>
                    >
                </label>

                <div class="crm-v3-isr-item crm-v3-isr-total crm-v3-comprador-total">
                    <span>Total</span>
                    <strong><?php echo esc_html(crm_v3_format_money($total)); ?></strong>
                </div>

            </div>

        </div>

        <?php if ($puede_editar) : ?>

            <div class="crm-v3-comprador-acciones">

                <span>Total = presupuesto + avalúo + impuestos y derechos.</span>

                <button
                    type="submit"
                    form="<?php echo esc_attr($form_id); ?>"
                    class="crm-v3-operacion-editor-save"
                >
                    Guardar
                </button>

            </div>

        <?php endif; ?>

    </div>

    <?php
}


/**
 * ============================================================
 * GUARDAR
 * ============================================================
 */
function crm_v3_guardar_analisis_comprador() {

    if (!isset($_POST['crm_v3_guardar_comprador'])) {
        return;
    }

    if (
        !isset($_POST['crm_v3_comprador_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['crm_v3_comprador_nonce'])),
            'crm_v3_guardar_comprador'
        ) ||
        !current_user_can('manage_options')
    ) {
        return;
    }

    $operacion_id = isset($_POST['crm_v3_comprador_operacion_id'])
        ? absint($_POST['crm_v3_comprador_operacion_id'])
        : 0;

    if (!$operacion_id || get_post_type($operacion_id) !== 'operaciones') {
        return;
    }

    foreach (array('costo_avaluo', 'gastos_impuestos_derechos') as $campo) {

        update_field(
            $campo,
            crm_v3_comprador_monto(
                isset($_POST[$campo]) && is_string($_POST[$campo])
                    ? wp_unslash($_POST[$campo])
                    : ''
            ),
            $operacion_id
        );
    }

    // Regresar a la misma ficha (y a la misma propiedad).
    $cliente_id = isset($_POST['cliente_id'])
        ? absint($_POST['cliente_id'])
        : 0;

    if (!$cliente_id) {
        return;
    }

    $args = array(
        'page'       => 'crm-ficha-cliente',
        'cliente_id' => $cliente_id,
    );

    $propiedad_id = isset($_POST['propiedad_id'])
        ? absint($_POST['propiedad_id'])
        : 0;

    if ($propiedad_id) {
        $args['propiedad_id'] = $propiedad_id;
    }

    wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));

    exit;
}

add_action('admin_init', 'crm_v3_guardar_analisis_comprador');
