<?php
/**
 * CRM V3 — Migraciones de datos (se ejecutan una sola vez)
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * ============================================================
 * ESTATUS DE OPERACIÓN: UN SOLO CAMPO
 * ============================================================
 *
 * Antes, el pipeline guardaba el estatus en "estatus_de_operecion"
 * (con error de escritura) y el formulario de ACF lo guardaba en
 * "estatus_de_operacion". Cada pantalla leía uno distinto.
 *
 * Ahora todo el CRM usa "estatus_de_operacion", el nombre real
 * del campo en ACF. Esta migración copia al campo correcto el
 * valor que se había capturado desde el pipeline.
 *
 * Reglas:
 * - Si el campo viejo tiene un estatus válido, ese es el que vale,
 *   porque es el que se actualizaba desde la ficha del cliente.
 * - Si el campo viejo está vacío, no se toca nada.
 * - El campo viejo NO se borra: queda como respaldo.
 * - Se ejecuta una sola vez y guarda un resumen de lo que hizo.
 */

function crm_v3_migrar_estatus_operacion() {

    $opcion = 'crm_v3_migracion_estatus_operacion';

    if (get_option($opcion)) {
        return;
    }

    if (!post_type_exists('operaciones')) {
        return;
    }

    $validos = array(
        'en_proceso',
        'perdida',
        'cerrada',
    );

    $operaciones = get_posts(array(
        'post_type'        => 'operaciones',
        'post_status'      => 'any',
        'posts_per_page'   => -1,
        'fields'           => 'ids',
        'suppress_filters' => true,
        'no_found_rows'    => true,
    ));

    $resumen = array(
        'fecha'       => current_time('mysql'),
        'revisadas'   => count($operaciones),
        'copiadas'    => 0,
        'ya_iguales'  => 0,
        'sin_viejo'   => 0,
        'cambios'     => array(),
    );

    foreach ($operaciones as $operacion_id) {

        $viejo = get_post_meta(
            $operacion_id,
            'estatus_de_operecion',
            true
        );

        $viejo = is_string($viejo) ? trim($viejo) : '';

        if (!in_array($viejo, $validos, true)) {
            $resumen['sin_viejo']++;
            continue;
        }

        $actual = get_post_meta(
            $operacion_id,
            'estatus_de_operacion',
            true
        );

        if ($actual === $viejo) {
            $resumen['ya_iguales']++;
            continue;
        }

        if (function_exists('update_field')) {

            update_field(
                'estatus_de_operacion',
                $viejo,
                $operacion_id
            );

        } else {

            update_post_meta(
                $operacion_id,
                'estatus_de_operacion',
                $viejo
            );
        }

        $resumen['copiadas']++;

        $resumen['cambios'][$operacion_id] = array(
            'antes'   => (string) $actual,
            'despues' => $viejo,
        );
    }

    update_option($opcion, $resumen, false);
    update_option('crm_v3_migracion_estatus_aviso', 1, false);
}

add_action(
    'init',
    'crm_v3_migrar_estatus_operacion',
    50
);


/**
 * Aviso único con el resultado de la migración.
 */
function crm_v3_migracion_estatus_aviso() {

    if (!current_user_can('manage_options')) {
        return;
    }

    if (!get_option('crm_v3_migracion_estatus_aviso')) {
        return;
    }

    $resumen = get_option('crm_v3_migracion_estatus_operacion');

    delete_option('crm_v3_migracion_estatus_aviso');

    if (!is_array($resumen)) {
        return;
    }

    printf(
        '<div class="notice notice-success is-dismissible"><p><strong>CRM-V3:</strong> se unificó el campo de estatus de operación. Operaciones revisadas: %d. Estatus copiados desde el pipeline: %d. Ya coincidían: %d. Sin estatus en el pipeline: %d.</p></div>',
        (int) $resumen['revisadas'],
        (int) $resumen['copiadas'],
        (int) $resumen['ya_iguales'],
        (int) $resumen['sin_viejo']
    );
}

add_action(
    'admin_notices',
    'crm_v3_migracion_estatus_aviso'
);
