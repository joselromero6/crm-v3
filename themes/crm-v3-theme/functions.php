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
        wp_get_theme()->get('Version')
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