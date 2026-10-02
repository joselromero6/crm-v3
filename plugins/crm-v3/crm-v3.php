<?php
/**
 * Plugin Name: CRM-V3
 * Description: CRM inmobiliario personalizado V3
 * Version: 3.0.0
 * Author: Jose Luis Romero
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Constantes del plugin
|--------------------------------------------------------------------------
*/

define('CRM_V3_PATH', plugin_dir_path(__FILE__));
define('CRM_V3_URL', plugin_dir_url(__FILE__));



/**
 * Cargar estilos de la ficha del cliente.
 */
function crm_v3_enqueue_ficha_cliente_styles($hook) {

    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'crm-ficha-cliente'
    ) {
        return;
    }

    $css_file = CRM_V3_PATH . 'includes/assets/css/ficha-cliente.css';

    wp_enqueue_style(
        'crm-v3-ficha-cliente',
        CRM_V3_URL . 'includes/assets/css/ficha-cliente.css',
        array(),
        file_exists($css_file) ? filemtime($css_file) : '3.0.0'
    );
}

add_action(
    'admin_enqueue_scripts',
    'crm_v3_enqueue_ficha_cliente_styles'
);


/**
 * Cargar estilos del cálculo ISR.
 */
function crm_v3_enqueue_isr_styles($hook) {

    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'crm-ficha-cliente'
    ) {
        return;
    }

    $css_file =
        CRM_V3_PATH .
        'includes/assets/css/crm-isr.css';

    wp_enqueue_style(
        'crm-v3-isr',
        CRM_V3_URL .
        'includes/assets/css/crm-isr.css',
        array('crm-v3-ficha-cliente'),
        file_exists($css_file)
            ? filemtime($css_file)
            : '1.0.0'
    );
}

add_action(
    'admin_enqueue_scripts',
    'crm_v3_enqueue_isr_styles'
);



/**
 * Cargar estilos del Pipeline y Análisis Financiero.
 */
function crm_v3_enqueue_pipeline_financiero_styles($hook) {

    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'crm-ficha-cliente'
    ) {
        return;
    }

    $pipeline_css =
        CRM_V3_PATH .
        'includes/assets/css/pipeline.css';

    $financiero_css =
        CRM_V3_PATH .
        'includes/assets/css/analisis-financiero.css';

    wp_enqueue_style(
        'crm-v3-pipeline',
        CRM_V3_URL .
        'includes/assets/css/pipeline.css',
        array('crm-v3-ficha-cliente'),
        file_exists($pipeline_css)
            ? filemtime($pipeline_css)
            : '1.0.0'
    );

    wp_enqueue_style(
        'crm-v3-analisis-financiero',
        CRM_V3_URL .
        'includes/assets/css/analisis-financiero.css',
        array('crm-v3-pipeline', 'crm-v3-isr'),
        file_exists($financiero_css)
            ? filemtime($financiero_css)
            : '1.0.0'
    );
}

add_action(
    'admin_enqueue_scripts',
    'crm_v3_enqueue_pipeline_financiero_styles'
);




/**
 * Cargar estilos de la lista de crm-clientes.
 */
function crm_v3_enqueue_clientes_styles($hook) {

    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'crm-clientes'
    ) {
        return;
    }

    $css_file = CRM_V3_PATH . 'includes/assets/css/clientes.css';

    wp_enqueue_style(
        'crm-v3-clientes',
        CRM_V3_URL . 'includes/assets/css/clientes.css',
        array(),
        file_exists($css_file) ? filemtime($css_file) : '3.0.0'
    );
}

add_action(
    'admin_enqueue_scripts',
    'crm_v3_enqueue_clientes_styles'
);

function crm_v3_enqueue_propiedades_assets($hook) {

    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'crm-propiedades'
    ) {
        return;
    }

    $css_file = CRM_V3_PATH . 'includes/assets/css/propiedades.css';
    $js_file  = CRM_V3_PATH . 'includes/assets/js/propiedades.js';

    wp_enqueue_style(
        'crm-v3-propiedades',
        CRM_V3_URL . 'includes/assets/css/propiedades.css',
        array(),
        file_exists($css_file) ? filemtime($css_file) : '1.0.0'
    );

    wp_enqueue_script(
        'crm-v3-propiedades',
        CRM_V3_URL . 'includes/assets/js/propiedades.js',
        array('jquery'),
        file_exists($js_file) ? filemtime($js_file) : '1.0.0',
        true
    );

    wp_localize_script(
        'crm-v3-propiedades',
        'crmPropiedades',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce(
                'crm_v3_propiedades_nonce'
            ),
        )
    );
}

add_action(
    'admin_enqueue_scripts',
    'crm_v3_enqueue_propiedades_assets'
);

function crm_v3_enqueue_operaciones_styles($hook) {

    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'crm-operaciones'
    ) {
        return;
    }

    $css_file = CRM_V3_PATH . 'includes/assets/css/operaciones.css';
    $js_file  = CRM_V3_PATH . 'includes/assets/js/operaciones.js';

    wp_enqueue_style(
        'crm-v3-operaciones',
        CRM_V3_URL . 'includes/assets/css/operaciones.css',
        array(),
        file_exists($css_file) ? filemtime($css_file) : '1.0.0'
    );

    wp_enqueue_script(
        'crm-v3-operaciones',
        CRM_V3_URL . 'includes/assets/js/operaciones.js',
        array('jquery'),
        file_exists($js_file) ? filemtime($js_file) : '1.0.0',
        true
    );

    wp_localize_script(
        'crm-v3-operaciones',
        'crmOperaciones',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce(
                'crm_v3_operaciones_nonce'
            ),
        )
    );
}


add_action(
    'admin_enqueue_scripts',
    'crm_v3_enqueue_operaciones_styles'
);

/**
 * Cargar estilos de Catálogos.
 */
function crm_v3_enqueue_catalogos_styles($hook) {

    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'crm-v3-catalogos'
    ) {
        return;
    }

    $css_file = CRM_V3_PATH . 'includes/assets/css/catalogos.css';

    wp_enqueue_style(
        'crm-v3-catalogos',
        CRM_V3_URL . 'includes/assets/css/catalogos.css',
        array(),
        file_exists($css_file) ? filemtime($css_file) : '1.0.0'
    );
}

add_action(
    'admin_enqueue_scripts',
    'crm_v3_enqueue_catalogos_styles'
);

/**
 * Cargar estilos del Backoffice.
 */

function crm_v3_enqueue_backoffice_styles($hook) {

    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'crm-v3-cibr'
    ) {
        return;
    }

    $css_file = CRM_V3_PATH . 'includes/assets/css/backoffice.css';
    $js_file  = CRM_V3_PATH . 'includes/assets/js/backoffice.js';

    wp_enqueue_style(
        'crm-v3-backoffice',
        CRM_V3_URL . 'includes/assets/css/backoffice.css',
        array(),
        file_exists($css_file)
            ? filemtime($css_file)
            : '1.0.0'
    );

    wp_enqueue_script(
        'crm-v3-backoffice',
        CRM_V3_URL . 'includes/assets/js/backoffice.js',
        array(),
        file_exists($js_file)
            ? filemtime($js_file)
            : '1.0.0',
        true
    );

    wp_localize_script(
        'crm-v3-backoffice',
        'crmBackoffice',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce(
                'crm_v3_backoffice_nonce'
            ),
        )
    );
}

add_action(
    'admin_enqueue_scripts',
    'crm_v3_enqueue_backoffice_styles'
);


/**
 * Cargar estilos del widget CRM en el Escritorio WordPress.
 */
function crm_v3_enqueue_dashboard_styles($hook) {

    if ($hook !== 'index.php') {
        return;
    }

    $css_file = CRM_V3_PATH . 'includes/assets/css/crm-dashboard.css';

    wp_enqueue_style(
        'crm-v3-dashboard',
        CRM_V3_URL . 'includes/assets/css/crm-dashboard.css',
        array(),
        file_exists($css_file)
            ? filemtime($css_file)
            : '1.0.0'
    );
}

add_action(
    'admin_enqueue_scripts',
    'crm_v3_enqueue_dashboard_styles'
);


/**
 * Cargar estilos del Análisis de Mercado.
 */
function crm_v3_enqueue_analisis_mercado_styles($hook) {

    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'crm-v3-cibr'
    ) {
        return;
    }

    $css_file =
        CRM_V3_PATH .
        'includes/assets/css/crm-analisis-mercado.css';

    wp_enqueue_style(
        'crm-v3-analisis-mercado',
        CRM_V3_URL .
        'includes/assets/css/crm-analisis-mercado.css',
        array('crm-v3-backoffice'),
        file_exists($css_file)
            ? filemtime($css_file)
            : '1.0.0'
    );
}

add_action(
    'admin_enqueue_scripts',
    'crm_v3_enqueue_analisis_mercado_styles'
);


/**
 * Cargar estilos y scripts de Nuevos Registros.
 */
function crm_v3_enqueue_formulario_styles($hook) {

    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'crm-nuevos-registros'
    ) {
        return;
    }

    $css_file = CRM_V3_PATH . 'includes/assets/css/crm-formulario.css';
    $js_file  = CRM_V3_PATH . 'includes/assets/js/crm-formulario.js';

    wp_enqueue_style(
        'crm-v3-formulario',
        CRM_V3_URL . 'includes/assets/css/crm-formulario.css',
        array(),
        file_exists($css_file)
            ? filemtime($css_file)
            : '1.0.0'
    );

    wp_enqueue_script(
        'crm-v3-formulario',
        CRM_V3_URL . 'includes/assets/js/crm-formulario.js',
        array('jquery'),
        file_exists($js_file)
            ? filemtime($js_file)
            : '1.0.0',
        true
    );
}

add_action(
    'admin_enqueue_scripts',
    'crm_v3_enqueue_formulario_styles'
);


/**
 * Cargar estilos de CRM Leads.
 */
function crm_v3_enqueue_leads_styles($hook) {

    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'crm-leads'
    ) {
        return;
    }

    $css_file = CRM_V3_PATH . 'includes/assets/css/crm-leads.css';
    $js_file = CRM_V3_PATH . 'includes/assets/js/crm-leads.js';

    wp_enqueue_style(
        'crm-v3-leads',
        CRM_V3_URL . 'includes/assets/css/crm-leads.css',
        array(),
        file_exists($css_file)
            ? filemtime($css_file)
            : '1.0.0'
    );
    wp_enqueue_script(
    'crm-v3-leads',
    CRM_V3_URL . 'includes/assets/js/crm-leads.js',
    [],
        file_exists($js_file) ? filemtime($js_file) : null,
        true
    );

    wp_localize_script(
    'crm-v3-leads',
    'crmLeadsData',
    [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('crm_v3_lead_notas'),
    ]
    );
}

add_action(
    'admin_enqueue_scripts',
    'crm_v3_enqueue_leads_styles'
);


/**
 * Cargar estilos del Expediente.
 */
function crm_v3_enqueue_expediente_styles($hook) {

    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'crm-expediente'
    ) {
        return;
    }

    $css_file = CRM_V3_PATH . 'includes/assets/css/crm-expediente.css';

    wp_enqueue_style(
        'crm-v3-expediente',
        CRM_V3_URL . 'includes/assets/css/crm-expediente.css',
        array(),
        file_exists($css_file)
            ? filemtime($css_file)
            : '1.0.0'
    );
}

add_action(
    'admin_enqueue_scripts',
    'crm_v3_enqueue_expediente_styles'
);








/*
|--------------------------------------------------------------------------
| Carga principal
|--------------------------------------------------------------------------
*/

require_once CRM_V3_PATH . 'includes/acf/acf-json.php';
require_once CRM_V3_PATH . 'includes/helpers/helpers.php';
require_once CRM_V3_PATH . 'includes/helpers/visibilidad.php';
require_once CRM_V3_PATH . 'includes/helpers/migraciones.php';
require_once CRM_V3_PATH . 'includes/ajax/crm-ajax.php';
require_once CRM_V3_PATH . 'includes/admin/admin-pages.php';
require_once CRM_V3_PATH . 'includes/admin/catalogos.php';
require_once CRM_V3_PATH . 'includes/admin/backoffice.php';
require_once CRM_V3_PATH . 'includes/admin/crm-dashboard.php';
require_once CRM_V3_PATH . 'includes/modules/crm-clientes.php';
require_once CRM_V3_PATH . 'includes/modules/crm-leads.php';
require_once CRM_V3_PATH . 'includes/modules/crm-ficha-cliente.php';
require_once CRM_V3_PATH . 'includes/modules/crm-expediente.php';
require_once CRM_V3_PATH . 'includes/modules/crm-analisis-financiero.php';
require_once CRM_V3_PATH . 'includes/modules/crm-isr.php';
require_once CRM_V3_PATH . 'includes/modules/crm-analisis-mercado.php';
require_once CRM_V3_PATH . 'includes/modules/crm-pipeline.php';
require_once CRM_V3_PATH . 'includes/modules/crm-propiedades.php';
require_once CRM_V3_PATH . 'includes/modules/crm-operaciones.php';
require_once CRM_V3_PATH . 'includes/modules/crm-formulario.php';
require_once CRM_V3_PATH . 'includes/modules/crm-contacto-web.php';
require_once CRM_V3_PATH . 'includes/modules/comunicados.php';


