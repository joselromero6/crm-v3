<?php
/**
 * CRM V3 — Visibilidad pública
 *
 * 1. Los tipos de contenido internos del CRM (clientes, leads,
 *    operaciones y catálogos) nunca son públicos: sin página propia,
 *    sin REST API, sin buscador y sin sitemap.
 *
 * 2. Las propiedades con "Mostrar en web" apagado no se muestran
 *    a visitantes por ninguna vía: URL directa, listados, buscador,
 *    feeds, REST API ni sitemap.
 *
 * Los usuarios que pueden editar entradas siguen viendo todo.
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Tipos de contenido de uso interno.
 */
function crm_v3_tipos_internos() {

    return array(
        'clientes',
        'leads',
        'operaciones',
        'bancos',
        'notarias',
        'valuadores',
        'servicios',
    );
}


/**
 * ============================================================
 * 1. TIPOS INTERNOS: NUNCA PÚBLICOS
 * ============================================================
 *
 * Se fuerza desde código para que no dependa de cómo esté
 * configurado cada tipo en ACF. El panel de administración
 * no cambia: siguen visibles en el menú y se editan igual.
 */

function crm_v3_forzar_tipos_internos_privados($args, $post_type) {

    if (!in_array($post_type, crm_v3_tipos_internos(), true)) {
        return $args;
    }

    $args['public']              = false;
    $args['publicly_queryable']  = false;
    $args['exclude_from_search'] = true;
    $args['show_in_rest']        = false;
    $args['show_in_nav_menus']   = false;
    $args['has_archive']         = false;
    $args['rewrite']             = false;
    $args['query_var']           = false;

    // El panel de administración se conserva.
    $args['show_ui'] = true;

    return $args;
}

add_filter(
    'register_post_type_args',
    'crm_v3_forzar_tipos_internos_privados',
    99,
    2
);


/**
 * ============================================================
 * 2. PROPIEDADES NO VISIBLES EN WEB
 * ============================================================
 */

/**
 * ¿El usuario actual puede ver propiedades no publicadas en web?
 */
function crm_v3_puede_ver_propiedades_ocultas() {

    return current_user_can('edit_posts');
}


/**
 * IDs de propiedades publicadas con "Mostrar en web" apagado
 * o sin capturar. Se calcula una sola vez por petición.
 */
function crm_v3_propiedades_ocultas_ids() {

    static $ids = null;

    if ($ids !== null) {
        return $ids;
    }

    $ids = get_posts(array(
        'post_type'        => 'propiedades',
        'post_status'      => 'publish',
        'posts_per_page'   => -1,
        'fields'           => 'ids',
        'suppress_filters' => true,
        'no_found_rows'    => true,
        'meta_query'       => array(
            'relation' => 'OR',
            array(
                'key'     => 'mostrar_web',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => 'mostrar_web',
                'value'   => '1',
                'compare' => '!=',
            ),
        ),
    ));

    $ids = array_map('intval', $ids);

    return $ids;
}


/**
 * ¿Esta propiedad está oculta para visitantes?
 */
function crm_v3_propiedad_esta_oculta($post_id) {

    return in_array(
        (int) $post_id,
        crm_v3_propiedades_ocultas_ids(),
        true
    );
}


/**
 * Agrega IDs a la lista de exclusión de una consulta.
 */
function crm_v3_excluir_ids($actuales, $nuevos) {

    $actuales = is_array($actuales) ? $actuales : array();

    return array_values(
        array_unique(
            array_merge(
                array_map('intval', $actuales),
                $nuevos
            )
        )
    );
}


/**
 * 2a. Ficha pública: responde 404.
 */
function crm_v3_ocultar_ficha_propiedad() {

    if (!is_singular('propiedades')) {
        return;
    }

    if (crm_v3_puede_ver_propiedades_ocultas()) {
        return;
    }

    if (!crm_v3_propiedad_esta_oculta(get_queried_object_id())) {
        return;
    }

    global $wp_query;

    $wp_query->set_404();
    status_header(404);
    nocache_headers();
}

add_action(
    'template_redirect',
    'crm_v3_ocultar_ficha_propiedad',
    1
);


/**
 * 2b. Listados del sitio: archivo, taxonomías, buscador y feeds.
 */
function crm_v3_ocultar_propiedades_en_listados($query) {

    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->is_singular()) {
        return;
    }

    if (crm_v3_puede_ver_propiedades_ocultas()) {
        return;
    }

    $ocultas = crm_v3_propiedades_ocultas_ids();

    if (empty($ocultas)) {
        return;
    }

    $query->set(
        'post__not_in',
        crm_v3_excluir_ids(
            $query->get('post__not_in'),
            $ocultas
        )
    );
}

add_action(
    'pre_get_posts',
    'crm_v3_ocultar_propiedades_en_listados',
    20
);


/**
 * 2c. REST API: listado.
 */
function crm_v3_ocultar_propiedades_rest_listado($args) {

    if (crm_v3_puede_ver_propiedades_ocultas()) {
        return $args;
    }

    $ocultas = crm_v3_propiedades_ocultas_ids();

    if (empty($ocultas)) {
        return $args;
    }

    $args['post__not_in'] = crm_v3_excluir_ids(
        isset($args['post__not_in']) ? $args['post__not_in'] : array(),
        $ocultas
    );

    return $args;
}

add_filter(
    'rest_propiedades_query',
    'crm_v3_ocultar_propiedades_rest_listado'
);


/**
 * 2d. REST API: consulta directa por ID y buscador.
 */
function crm_v3_ocultar_propiedades_rest_directo($response, $handler, $request) {

    if (is_wp_error($response)) {
        return $response;
    }

    if (crm_v3_puede_ver_propiedades_ocultas()) {
        return $response;
    }

    if (
        preg_match(
            '#^/wp/v2/propiedades/(\d+)#',
            $request->get_route(),
            $coincidencia
        ) &&
        crm_v3_propiedad_esta_oculta($coincidencia[1])
    ) {
        return new WP_Error(
            'rest_post_invalid_id',
            'ID de entrada no válido.',
            array('status' => 404)
        );
    }

    return $response;
}

add_filter(
    'rest_request_before_callbacks',
    'crm_v3_ocultar_propiedades_rest_directo',
    10,
    3
);


function crm_v3_ocultar_propiedades_rest_busqueda($args) {

    if (crm_v3_puede_ver_propiedades_ocultas()) {
        return $args;
    }

    $ocultas = crm_v3_propiedades_ocultas_ids();

    if (empty($ocultas)) {
        return $args;
    }

    $args['post__not_in'] = crm_v3_excluir_ids(
        isset($args['post__not_in']) ? $args['post__not_in'] : array(),
        $ocultas
    );

    return $args;
}

add_filter(
    'rest_post_search_query',
    'crm_v3_ocultar_propiedades_rest_busqueda'
);


/**
 * 2e. Sitemap de WordPress.
 */
function crm_v3_ocultar_propiedades_sitemap($args, $post_type) {

    if ($post_type !== 'propiedades') {
        return $args;
    }

    $ocultas = crm_v3_propiedades_ocultas_ids();

    if (empty($ocultas)) {
        return $args;
    }

    $args['post__not_in'] = crm_v3_excluir_ids(
        isset($args['post__not_in']) ? $args['post__not_in'] : array(),
        $ocultas
    );

    return $args;
}

add_filter(
    'wp_sitemaps_posts_query_args',
    'crm_v3_ocultar_propiedades_sitemap',
    10,
    2
);


/**
 * 2f. Sitemap de Rank Math.
 */
function crm_v3_ocultar_propiedades_sitemap_rank_math($url, $tipo, $objeto) {

    if (
        $tipo === 'post' &&
        is_object($objeto) &&
        isset($objeto->post_type, $objeto->ID) &&
        $objeto->post_type === 'propiedades' &&
        crm_v3_propiedad_esta_oculta($objeto->ID)
    ) {
        return false;
    }

    return $url;
}

add_filter(
    'rank_math/sitemap/entry',
    'crm_v3_ocultar_propiedades_sitemap_rank_math',
    10,
    3
);


/**
 * ============================================================
 * 3. ACTUALIZAR LAS RUTAS UNA SOLA VEZ
 * ============================================================
 *
 * Al dejar de ser públicos, las direcciones /clientes/…, /leads/…
 * y similares deben desaparecer. WordPress guarda las rutas en la
 * base de datos, así que se regeneran una vez tras esta versión.
 */

function crm_v3_actualizar_rutas_visibilidad() {

    $version = '2026-10-visibilidad';

    if (get_option('crm_v3_rutas_version') === $version) {
        return;
    }

    flush_rewrite_rules(false);

    update_option('crm_v3_rutas_version', $version);
}

add_action(
    'init',
    'crm_v3_actualizar_rutas_visibilidad',
    999
);
