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


/**
 * Adeudo predial de una propiedad.
 *
 * El campo en ACF se llama "adeudo_pedial" (sin la "r").
 * Se lee primero el nombre correcto y después el actual, para
 * que funcione igual si algún día se renombra el campo en ACF.
 */
function crm_v3_get_adeudo_predial($propiedad_id) {

    $propiedad_id = (int) $propiedad_id;

    if (!$propiedad_id) {
        return null;
    }

    foreach (array('adeudo_predial', 'adeudo_pedial') as $campo) {

        $valor = get_post_meta($propiedad_id, $campo, true);

        if ($valor !== '' && $valor !== null && $valor !== false) {
            return $valor;
        }
    }

    return null;
}


/**
 * Ordena una lista de registros por un campo de fecha de ACF,
 * del más reciente al más antiguo.
 *
 * Los registros sin fecha NO se descartan: quedan al final,
 * ordenados por su fecha de creación. (Ordenar desde la consulta
 * con meta_key excluía a todo registro que no tuviera el campo.)
 */
function crm_v3_ordenar_por_fecha($posts, $campo) {

    if (!is_array($posts) || count($posts) < 2) {
        return $posts;
    }

    $claves = array();

    foreach ($posts as $post) {

        $id = is_object($post) ? $post->ID : (int) $post;

        $valor = get_post_meta($id, $campo, true);

        // ACF guarda las fechas como Ymd (20260930).
        $claves[$id] = is_numeric($valor) ? (int) $valor : 0;
    }

    usort(
        $posts,
        function ($a, $b) use ($claves) {

            $id_a = is_object($a) ? $a->ID : (int) $a;
            $id_b = is_object($b) ? $b->ID : (int) $b;

            if ($claves[$id_a] !== $claves[$id_b]) {
                return $claves[$id_b] <=> $claves[$id_a];
            }

            $fecha_a = is_object($a) ? $a->post_date : '';
            $fecha_b = is_object($b) ? $b->post_date : '';

            if ($fecha_a !== $fecha_b) {
                return strcmp($fecha_b, $fecha_a);
            }

            return $id_b <=> $id_a;
        }
    );

    return $posts;
}


/**
 * ¿El valor de un campo de taxonomía de ACF incluye este término?
 *
 * ACF puede devolver un ID, un objeto WP_Term o una lista de
 * cualquiera de los dos, según cómo esté configurado el campo.
 */
function crm_v3_valor_incluye_termino($valor, $term_id) {

    $term_id = (int) $term_id;

    if (!$term_id || empty($valor)) {
        return false;
    }

    $valores = is_array($valor) ? $valor : array($valor);

    foreach ($valores as $item) {

        if (is_object($item) && isset($item->term_id)) {
            $item = $item->term_id;
        }

        if (is_numeric($item) && (int) $item === $term_id) {
            return true;
        }
    }

    return false;
}


/**
 * Título de un registro como texto plano.
 *
 * Para enviarlo por AJAX: sin etiquetas y sin entidades HTML,
 * de modo que el navegador lo escape al mostrarlo.
 */
function crm_v3_titulo_plano($post_id) {

    return wp_strip_all_tags(
        html_entity_decode(
            (string) get_the_title($post_id),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}


/**
 * Etiqueta visible de un campo de lista de ACF.
 *
 * ACF guarda la clave ("en_proceso"); en pantalla se muestra la
 * etiqueta definida en ACF ("En proceso"). Si el campo o la opción
 * no existen, se arma una etiqueta legible a partir de la clave.
 */
function crm_v3_etiqueta_campo($campo, $post_id, $valor = null, $empty = '—') {

    if ($valor === null) {
        $valor = get_field($campo, $post_id);
    }

    if (is_array($valor)) {
        $valor = isset($valor['label']) ? $valor['label'] : reset($valor);
    }

    if ($valor === null || $valor === false || $valor === '') {
        return $empty;
    }

    $valor = (string) $valor;

    $etiqueta = crm_v3_opcion_acf($campo, $post_id, $valor);

    return $etiqueta !== null
        ? $etiqueta
        : ucfirst(str_replace('_', ' ', $valor));
}



/**
 * Valor simple de un campo de ACF.
 *
 * Según la configuración del campo, ACF puede devolver el valor solo,
 * dentro de una lista, o como objeto. Aquí siempre se obtiene el valor solo.
 */
function crm_v3_valor_simple($valor) {

    if (is_array($valor)) {
        $valor = reset($valor);
    }

    if (is_object($valor) && isset($valor->value)) {
        $valor = $valor->value;
    }

    return $valor;
}


/**
 * Valor simple, en minúsculas y sin espacios, listo para comparar.
 */
function crm_v3_valor_normalizado($valor) {

    return strtolower(
        trim(
            (string) crm_v3_valor_simple($valor)
        )
    );
}


/**
 * Nombre(s) de los términos de un catálogo (taxonomía).
 *
 * Acepta un ID, un término o una lista de ellos.
 * Si son varios, los devuelve separados por coma.
 */
function crm_v3_nombres_terminos($valor, $taxonomia, $empty = '—') {

    if (empty($valor)) {
        return $empty;
    }

    $nombres = array();

    foreach ((array) (is_object($valor) ? array($valor) : $valor) as $item) {

        if (is_object($item) && isset($item->term_id)) {
            $nombres[] = $item->name;
            continue;
        }

        $term = get_term($item, $taxonomia);

        if ($term && !is_wp_error($term)) {
            $nombres[] = $term->name;
        }
    }

    return !empty($nombres)
        ? implode(', ', $nombres)
        : $empty;
}


/**
 * Opciones (clave => etiqueta) de un campo de lista de ACF.
 */
function crm_v3_opciones_campo($campo) {

    $objeto = function_exists('acf_get_field')
        ? acf_get_field($campo)
        : null;

    return (
        is_array($objeto) &&
        !empty($objeto['choices']) &&
        is_array($objeto['choices'])
    )
        ? $objeto['choices']
        : array();
}


/**
 * Etiqueta definida en ACF para una opción de un campo de lista.
 * Devuelve null si el campo o la opción no existen.
 */
function crm_v3_opcion_acf($campo, $post_id, $valor) {

    if (!is_string($valor) && !is_int($valor)) {
        return null;
    }

    $objeto = get_field_object($campo, $post_id);

    if (
        is_array($objeto) &&
        !empty($objeto['choices']) &&
        isset($objeto['choices'][$valor])
    ) {
        return $objeto['choices'][$valor];
    }

    return null;
}


/**
 * Fecha en el formato que usan los campos de fecha del navegador (AAAA-MM-DD).
 *
 * Acepta d/m/Y, Ymd y Y-m-d. Devuelve '' si no reconoce la fecha.
 */
function crm_v3_fecha_para_input($valor) {

    if (empty($valor)) {
        return '';
    }

    $valor = (string) $valor;

    $fecha = DateTime::createFromFormat('d/m/Y', $valor);

    if ($fecha) {
        return $fecha->format('Y-m-d');
    }

    if (preg_match('/^\d{8}$/', $valor)) {

        $fecha = DateTime::createFromFormat('Ymd', $valor);

        return $fecha ? $fecha->format('Y-m-d') : '';
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        return $valor;
    }

    return '';
}


/**
 * Estatus de la operación más reciente de cada propiedad.
 *
 * Devuelve una lista: ID de propiedad => estatus de su última operación.
 * Se calcula una sola vez por carga de página.
 */
function crm_v3_estatus_ultima_operacion_por_propiedad() {

    static $resultado = null;

    if ($resultado !== null) {
        return $resultado;
    }

    $resultado = array();

    $operaciones = get_posts(array(
        'post_type'      => 'operaciones',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));

    $operaciones = crm_v3_ordenar_por_fecha(
        $operaciones,
        'fecha_de_operacion'
    );

    foreach ($operaciones as $operacion) {

        $propiedad_id = crm_v3_get_related_id(
            get_field('propiedad', $operacion->ID)
        );

        // La lista viene de la más reciente a la más antigua:
        // la primera operación de cada propiedad es la que cuenta.
        if (!$propiedad_id || isset($resultado[$propiedad_id])) {
            continue;
        }

        $resultado[$propiedad_id] = crm_v3_valor_simple(
            get_field('estatus_de_operacion', $operacion->ID)
        );
    }

    return $resultado;
}
