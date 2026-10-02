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



/*
|--------------------------------------------------------------------------
| Estilos y scripts de las pantallas del CRM
|--------------------------------------------------------------------------
|
| Una sola lista indica qué archivos carga cada pantalla. Para agregar un
| archivo nuevo basta con añadir una línea a la lista.
|
| Cada línea: nombre => array(archivo, dependencias).
| En los scripts se puede añadir 'datos' => array(variable JS, nonce): esos
| scripts (y los estilos de la misma pantalla marcada con 'solo_admin')
| solo se entregan a administradores, porque llevan un código de seguridad.
*/

function crm_v3_assets_por_pantalla() {

    return array(

        'crm-ficha-cliente' => array(
            'css' => array(
                'crm-v3-ficha-cliente'       => array('ficha-cliente.css', array()),
                'crm-v3-isr'                 => array('crm-isr.css', array('crm-v3-ficha-cliente')),
                'crm-v3-pipeline'            => array('pipeline.css', array('crm-v3-ficha-cliente')),
                'crm-v3-analisis-financiero' => array('analisis-financiero.css', array('crm-v3-pipeline', 'crm-v3-isr')),
            ),
        ),

        'crm-clientes' => array(
            'css' => array(
                'crm-v3-clientes' => array('clientes.css', array()),
            ),
        ),

        'crm-propiedades' => array(
            'solo_admin' => true,
            'css' => array(
                'crm-v3-propiedades' => array('propiedades.css', array()),
            ),
            'js' => array(
                'crm-v3-propiedades' => array(
                    'propiedades.js',
                    array('jquery'),
                    'datos' => array('crmPropiedades', 'crm_v3_propiedades_nonce'),
                ),
            ),
        ),

        'crm-operaciones' => array(
            'solo_admin' => true,
            'css' => array(
                'crm-v3-operaciones' => array('operaciones.css', array()),
            ),
            'js' => array(
                'crm-v3-operaciones' => array(
                    'operaciones.js',
                    array('jquery'),
                    'datos' => array('crmOperaciones', 'crm_v3_operaciones_nonce'),
                ),
            ),
        ),

        'crm-v3-catalogos' => array(
            'css' => array(
                'crm-v3-catalogos' => array('catalogos.css', array()),
            ),
        ),

        'crm-v3-cibr' => array(
            'css' => array(
                'crm-v3-backoffice'       => array('backoffice.css', array(), 'solo_admin' => true),
                'crm-v3-analisis-mercado' => array('crm-analisis-mercado.css', array('crm-v3-backoffice')),
            ),
            'js' => array(
                'crm-v3-backoffice' => array(
                    'backoffice.js',
                    array(),
                    'solo_admin' => true,
                    'datos' => array('crmBackoffice', 'crm_v3_backoffice_nonce'),
                ),
            ),
        ),

        'crm-nuevos-registros' => array(
            'css' => array(
                'crm-v3-formulario' => array('crm-formulario.css', array()),
            ),
            'js' => array(
                'crm-v3-formulario' => array('crm-formulario.js', array('jquery')),
            ),
        ),

        'crm-leads' => array(
            'solo_admin' => true,
            'css' => array(
                'crm-v3-leads' => array('crm-leads.css', array()),
            ),
            'js' => array(
                'crm-v3-leads' => array(
                    'crm-leads.js',
                    array(),
                    'datos' => array('crmLeadsData', 'crm_v3_lead_notas'),
                ),
            ),
        ),

        'crm-expediente' => array(
            'css' => array(
                'crm-v3-expediente' => array('crm-expediente.css', array()),
            ),
        ),
    );
}


/**
 * Versión de un archivo: su fecha de modificación, para que el navegador
 * descargue la versión nueva cuando el archivo cambia.
 */
function crm_v3_asset_version($ruta_relativa) {

    $archivo = CRM_V3_PATH . $ruta_relativa;

    return file_exists($archivo) ? filemtime($archivo) : '3.0.0';
}


/**
 * Cargar los estilos y scripts de la pantalla del CRM que se está viendo.
 */
function crm_v3_enqueue_assets($hook) {

    // Widget del CRM en el Escritorio de WordPress.
    if ($hook === 'index.php') {

        wp_enqueue_style(
            'crm-v3-dashboard',
            CRM_V3_URL . 'includes/assets/css/crm-dashboard.css',
            array(),
            crm_v3_asset_version('includes/assets/css/crm-dashboard.css')
        );
    }

    $pantallas = crm_v3_assets_por_pantalla();
    $pagina    = isset($_GET['page']) ? $_GET['page'] : '';

    if (!is_string($pagina) || !isset($pantallas[$pagina])) {
        return;
    }

    $pantalla = $pantallas[$pagina];
    $es_admin = current_user_can('manage_options');

    if (!empty($pantalla['solo_admin']) && !$es_admin) {
        return;
    }

    $css = isset($pantalla['css']) ? $pantalla['css'] : array();
    $js  = isset($pantalla['js']) ? $pantalla['js'] : array();

    foreach ($css as $nombre => $estilo) {

        if (!empty($estilo['solo_admin']) && !$es_admin) {
            continue;
        }

        $ruta = 'includes/assets/css/' . $estilo[0];

        wp_enqueue_style(
            $nombre,
            CRM_V3_URL . $ruta,
            $estilo[1],
            crm_v3_asset_version($ruta)
        );
    }

    foreach ($js as $nombre => $script) {

        if (!empty($script['solo_admin']) && !$es_admin) {
            continue;
        }

        $ruta = 'includes/assets/js/' . $script[0];

        wp_enqueue_script(
            $nombre,
            CRM_V3_URL . $ruta,
            $script[1],
            crm_v3_asset_version($ruta),
            true
        );

        if (!empty($script['datos'])) {

            wp_localize_script(
                $nombre,
                $script['datos'][0],
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce'   => wp_create_nonce($script['datos'][1]),
                )
            );
        }
    }
}

add_action('admin_enqueue_scripts', 'crm_v3_enqueue_assets');


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


