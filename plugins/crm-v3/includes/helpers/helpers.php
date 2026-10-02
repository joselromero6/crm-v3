<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * CRM V3 — Helpers generales
 */


/**
 * Normalizar y mostrar valores ACF.
 *
 * Convierte arrays, objetos, IDs de términos
 * y valores simples en texto legible.
 */
function crm_v3_display_value($value, $empty = '—') {

    if ($value === null || $value === '') {
        return $empty;
    }

    if (is_array($value)) {

        $values = array();

        foreach ($value as $item) {

            if (is_object($item) && isset($item->name)) {

                $values[] = $item->name;

            } elseif (is_array($item) && isset($item['name'])) {

                $values[] = $item['name'];

            } elseif (is_numeric($item)) {

                $term = get_term($item);

                if ($term && !is_wp_error($term)) {
                    $values[] = $term->name;
                }

            } else {

                $values[] = (string) $item;
            }
        }

        $values = array_filter($values, 'strlen');

        return !empty($values)
            ? implode(', ', $values)
            : $empty;
    }

    if (is_object($value) && isset($value->name)) {
        return (string) $value->name;
    }

    if (is_numeric($value)) {

        $term = get_term($value);

        if ($term && !is_wp_error($term)) {
            return $term->name;
        }
    }

    return trim((string) $value) !== ''
        ? (string) $value
        : $empty;
}

/**
 * Formatear cantidades monetarias del CRM.
 */
function crm_v3_format_money($value, $decimals = 2) {

    if ($value === null || $value === '') {
        return '—';
    }

    if (!is_numeric($value)) {
        return (string) $value;
    }

    return '$' . number_format(
        (float) $value,
        $decimals,
        '.',
        ','
    );
}



/**
 * Formatear fechas del CRM.
 *
 * Acepta:
 * - Ymd
 * - Y-m-d
 * - d/m/Y
 *
 * Devuelve:
 * d/m/Y
 */
function crm_v3_format_date($value, $empty = '—') {

    if (empty($value)) {
        return $empty;
    }

    $value = trim((string) $value);

    $formats = array(
        'Ymd',
        'Y-m-d',
        'd/m/Y',
    );

    foreach ($formats as $format) {

        $date = DateTime::createFromFormat(
            $format,
            $value
        );

        if (
            $date &&
            $date->format($format) === $value
        ) {
            return $date->format('d/m/Y');
        }
    }

    return $value;
}


/**
 * Obtener ID de una relación ACF.
 */
function crm_v3_get_related_id($value) {

    if (empty($value)) {
        return 0;
    }

    if (is_object($value) && isset($value->ID)) {
        return (int) $value->ID;
    }

    if (is_numeric($value)) {
        return (int) $value;
    }

    if (is_array($value)) {

        $first = reset($value);

        if (is_object($first) && isset($first->ID)) {
            return (int) $first->ID;
        }

        if (is_numeric($first)) {
            return (int) $first;
        }
    }

    return 0;
}


/**
 * Obtener nombre de un objeto relacionado.
 */
function crm_v3_get_related_name($value, $empty = '—') {

    if (empty($value)) {
        return $empty;
    }

    if (is_object($value) && isset($value->post_title)) {
        return $value->post_title;
    }

    if (is_numeric($value)) {

        $post = get_post((int) $value);

        if ($post) {
            return $post->post_title;
        }
    }

    if (is_array($value)) {

        $names = array();

        foreach ($value as $item) {

            $name = crm_v3_get_related_name(
                $item,
                ''
            );

            if ($name !== '') {
                $names[] = $name;
            }
        }

        return !empty($names)
            ? implode(', ', $names)
            : $empty;
    }

    return (string) $value;
}