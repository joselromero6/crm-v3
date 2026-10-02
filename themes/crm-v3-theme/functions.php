<?php

function crm_v3_theme_setup() {

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');

    register_nav_menus(array(
        'main_menu' => 'Menú Principal',
    ));
}

add_action('after_setup_theme', 'crm_v3_theme_setup');


function crm_v3_theme_assets() {
    wp_enqueue_style(
        'crm-v3-style',
        get_stylesheet_uri(),
        array(),
        // La fecha del archivo como versión: al cambiar el CSS,
        // el navegador descarga el nuevo en lugar de usar su copia.
        filemtime(get_stylesheet_directory() . '/style.css')
    );
}

add_action('wp_enqueue_scripts', 'crm_v3_theme_assets');



function filtrar_propiedades_archivo($query) {

if (
!is_admin()
&& $query->is_main_query()
&& is_post_type_archive('propiedades')
) {


// BUSCADOR

if (!empty($_GET['buscar'])) {

$query->set(
's',
sanitize_text_field($_GET['buscar'])
);

}


// FILTRO WEB

$meta_query = array(

array(
'key' => 'mostrar_web',
'value' => 1,
'compare' => '='
)

);


// TAXONOMIAS

$tax_query = array();


// CIUDAD

if (!empty($_GET['ciudad'])) {

$tax_query[] = array(
'taxonomy' => 'ciudad',
'field' => 'slug',
'terms' => sanitize_text_field($_GET['ciudad']),
);

}


// TIPO

if (!empty($_GET['tipo'])) {

$tax_query[] = array(
'taxonomy' => 'tipo-de-propiedad',
'field' => 'term_id',
'terms' => intval($_GET['tipo']),
);

}


// APLICAR TAX

if (!empty($tax_query)) {

if (count($tax_query) > 1) {
$tax_query['relation'] = 'AND';
}

$query->set(
'tax_query',
$tax_query
);

}


// APLICAR META

$query->set(
'meta_query',
$meta_query
);

}

}

add_action(
'pre_get_posts',
'filtrar_propiedades_archivo'
);


/*
|--------------------------------------------------------------------------
| Ayudas para las plantillas de propiedades
|--------------------------------------------------------------------------
*/

/**
 * Etiqueta visible de un campo de lista de ACF.
 *
 * ACF guarda la clave ("en_juicio"); al público se le muestra la
 * etiqueta ("En Juicio"). Si no hay etiqueta, se arma una legible.
 */
function crm_v3_theme_etiqueta_campo($campo, $post_id = 0) {

    if (!function_exists('get_field')) {
        return '';
    }

    $post_id = $post_id ? $post_id : get_the_ID();

    $valor = get_field($campo, $post_id);

    if (is_array($valor)) {
        $valor = isset($valor['label']) ? $valor['label'] : reset($valor);
    }

    if ($valor === null || $valor === false || $valor === '') {
        return '';
    }

    $valor = (string) $valor;

    $objeto = function_exists('get_field_object')
        ? get_field_object($campo, $post_id)
        : null;

    if (
        is_array($objeto) &&
        !empty($objeto['choices']) &&
        isset($objeto['choices'][$valor])
    ) {
        return $objeto['choices'][$valor];
    }

    return ucfirst(str_replace('_', ' ', $valor));
}


/**
 * Nombre de un término guardado en un campo de taxonomía de ACF.
 *
 * El campo puede devolver un ID, un objeto o una lista.
 */
function crm_v3_theme_nombre_termino($campo, $post_id = 0) {

    if (!function_exists('get_field')) {
        return '';
    }

    $post_id = $post_id ? $post_id : get_the_ID();

    $valor = get_field($campo, $post_id);

    if (is_array($valor) && !isset($valor['name'])) {
        $valor = reset($valor);
    }

    if (is_object($valor) && isset($valor->name)) {
        return $valor->name;
    }

    if (is_array($valor) && isset($valor['name'])) {
        return $valor['name'];
    }

    if (is_numeric($valor)) {

        $termino = get_term((int) $valor);

        if ($termino && !is_wp_error($termino)) {
            return $termino->name;
        }
    }

    return '';
}


/**
 * Etiqueta de estado comercial para las tarjetas y la ficha.
 *
 * @return array|null [ 'texto' => ..., 'clase' => ... ]
 */
function crm_v3_theme_badge_estado($post_id = 0) {

    if (!function_exists('get_field')) {
        return null;
    }

    $post_id = $post_id ? $post_id : get_the_ID();

    $estado = get_field('estado_comercial', $post_id);

    $badges = array(
        'en_negociacion' => array(
            'texto' => 'EN NEGOCIACIÓN',
            'clase' => 'estado-preparacion',
        ),
        'disponible' => array(
            'texto' => 'DISPONIBLE',
            'clase' => 'estado-disponible',
        ),
        'tratada' => array(
            'texto' => 'TRATADA',
            'clase' => 'estado-tratada',
        ),
        'vendida' => array(
            'texto' => 'VENDIDA',
            'clase' => 'estado-vendida',
        ),
    );

    return (is_string($estado) && isset($badges[$estado]))
        ? $badges[$estado]
        : null;
}


/**
 * URL de la imagen de una propiedad para listados.
 *
 * Orden: imagen destacada, primera imagen de la galería,
 * imagen por defecto del tema.
 */
function crm_v3_theme_imagen_propiedad_url($post_id = 0, $tamano = 'medium_large') {

    $post_id = $post_id ? $post_id : get_the_ID();

    if (has_post_thumbnail($post_id)) {

        $url = get_the_post_thumbnail_url($post_id, $tamano);

        if ($url) {
            return $url;
        }
    }

    $imagenes = function_exists('get_field')
        ? get_field('imagenes', $post_id)
        : null;

    if (is_string($imagenes) && $imagenes !== '') {
        $imagenes = array_map('trim', explode(',', $imagenes));
    }

    if (is_array($imagenes) && !empty($imagenes)) {

        $primera = reset($imagenes);

        if (is_array($primera)) {

            if (!empty($primera['sizes'][$tamano])) {
                return $primera['sizes'][$tamano];
            }

            if (!empty($primera['url'])) {
                return $primera['url'];
            }

            if (!empty($primera['ID'])) {
                $primera = $primera['ID'];
            }
        }

        if (is_numeric($primera)) {

            $url = wp_get_attachment_image_url((int) $primera, $tamano);

            if ($url) {
                return $url;
            }

        } elseif (is_string($primera) && $primera !== '') {

            return $primera;
        }
    }

    return get_template_directory_uri() . '/assets/img/default-property.svg';
}
