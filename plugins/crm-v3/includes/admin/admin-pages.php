<?php
/**
 * Organización de Menús Admin CRM-V3
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Reorganiza menús ACF / CPT bajo CRM-V3
 */
add_action('admin_menu', 'crm_v3_admin_menu_structure', 999);

function crm_v3_admin_menu_structure() {

    // Menú principal CRM-V3
    
    add_menu_page(
        'CRM-V3',
        'CRM-V3 Captura',
        'manage_options',
        'crm-v3-dashboard',
        'crm_v3_dashboard_page',
        'dashicons-groups',
        4
    );

    add_submenu_page(
        'crm-v3-dashboard',
        'Escritorio',
        'Escritorio',
        'manage_options',
        'index.php'
    );



    // ==========================
    // FORMULARIOS PRINCIPALES
    // ==========================

    add_submenu_page(
            'crm-v3-dashboard',
            'Leads',
            'Leads',
            'manage_options',
            'edit.php?post_type=leads'
        );

    add_submenu_page(
        'crm-v3-dashboard',
        'Clientes',
        'Clientes',
        'manage_options',
        'edit.php?post_type=clientes'
    );

    add_submenu_page(
        'crm-v3-dashboard',
        'Propiedades',
        'Propiedades',
        'manage_options',
        'edit.php?post_type=propiedades'
    );

    add_submenu_page(
        'crm-v3-dashboard',
        'Operaciones',
        'Operaciones',
        'manage_options',
        'edit.php?post_type=operaciones'
    );


    // ==========================
    // CATÁLOGOS
    // ==========================


    //add_submenu_page(
    //    'crm-v3-dashboard',
    //    'Catálogos',
    //    '-  Catálogos',
    //    'manage_options',
    //    'crm-v3-catalogos',
    //    '__return_null'
    //);


    add_submenu_page(
        'crm-v3-dashboard',
        'Bancos',
        'Bancos',
        'manage_options',
        'edit.php?post_type=bancos'
    );

    add_submenu_page(
        'crm-v3-dashboard',
        'Notarías',
        'Notarías',
        'manage_options',
        'edit.php?post_type=notarias'
    );

     add_submenu_page(
        'crm-v3-dashboard',
        'Valuador',
        'Valuador',
        'manage_options',
        'edit.php?post_type=valuadores'
    );

    add_submenu_page(
        'crm-v3-dashboard',
        'Servicios',
        'Servicios',
        'manage_options',
        'edit.php?post_type=servicios'
    );

    remove_submenu_page(
        'crm-v3-dashboard',
        'crm-v3-dashboard'
    );


//                  MENUS CRM                   //
//==============================================//

add_menu_page(
    'CRM CIBR',
    'CRM CIBR',
    'manage_options',
    'crm-v3-cibr',
    '__return_null',
    'dashicons-database',
    4
);

add_submenu_page(
    'crm-v3-cibr',
    'Backoffice',
    'Backoffice',
    'manage_options',
    'crm-v3-cibr',
    'crm_v3_backoffice_page'
);

add_submenu_page(
    'crm-v3-cibr',
    'Nuevos registros',
    'Nuevos registros',
    'manage_options',
    'crm-nuevos-registros',
    'crm_v3_nuevos_registros_page'
);

add_submenu_page(
    'crm-v3-cibr',
    'CRM Leads',
    'CRM Leads',
    'manage_options',
    'crm-leads',
    'crm_v3_leads_page'
);

add_submenu_page(
    'crm-v3-cibr',
    'CRM Clientes',
    'CRM Clientes',
    'manage_options',
    'crm-clientes',
    'crm_v3_clientes_page'
);

add_submenu_page(
    'crm-v3-cibr',
    'CRM Propiedades',
    'CRM Propiedades',
    'manage_options',
    'crm-propiedades',
    'crm_v3_propiedades_page'
);

add_submenu_page(
    'crm-v3-cibr',
    'CRM Operaciones',
    'CRM Operaciones',
    'manage_options',
    'crm-operaciones',
    'crm_v3_operaciones_page'
);


add_submenu_page(
    'crm-v3-cibr',
    'Catálogos',
    'Catálogos',
    'manage_options',
    'crm-v3-catalogos',
    'crm_v3_catalogos_page'
);




//                  FICHA DEL CLIENTE             //
//================================================//

add_submenu_page(
    '',
    'Ficha del Cliente',
    'Ficha del Cliente',
    'manage_options',
    'crm-ficha-cliente',
    'crm_v3_ficha_cliente_page'
);


add_submenu_page(
    '',
    'Expediente',
    'Expediente',
    'manage_options',
    'crm-expediente',
    'crm_v3_expediente_page'
);




//                  TAXOMANIAS                    //
//================================================//

add_menu_page(
    'Taxonomías',
    'Taxonomías',
    'manage_options',
    'crm-v3-tax',
    '__return_null',
    'dashicons-database',
4
);


//      Clientes

add_submenu_page(
    'crm-v3-tax',
    'Origen lead',
    'Origen lead',
    'manage_options',
    'edit-tags.php?taxonomy=origen-lead'
);

add_submenu_page(
    'crm-v3-tax',
    'Origen recursos',
    'Origen recursos',
    'manage_options',
    'edit-tags.php?taxonomy=origen-de-recurso'
);

add_submenu_page(
    'crm-v3-tax',
    'Perfil clientes',
    'Perfil clientes',
    'manage_options',
    'edit-tags.php?taxonomy=perfil-de-cliente'
);

add_submenu_page(
    'crm-v3-tax',
    'Relación comercial',
    'Relación comercial',
    'manage_options',
    'edit-tags.php?taxonomy=relacion-comercial'
);

add_submenu_page(
    'crm-v3-tax',
    'Tipos crédito',
    'Tipos crédito',
    'manage_options',
    'edit-tags.php?taxonomy=tipo-de-creditos'
);


//      Propiedades
add_submenu_page(
    'crm-v3-tax',
    'Ciudades',
    'Ciudades',
    'manage_options',
    'edit-tags.php?taxonomy=ciudad'
);

add_submenu_page(
    'crm-v3-tax',
    'Estados',
    'Estados',
    'manage_options',
    'edit-tags.php?taxonomy=estado'
);

add_submenu_page(
    'crm-v3-tax',
    'Colonias',
    'Colonias',
    'manage_options',
    'edit-tags.php?taxonomy=fraccionamiento-o-colonia'
);

add_submenu_page(
    'crm-v3-tax',
    'Tipo propiedad',
    'Tipo propiedad',
    'manage_options',
    'edit-tags.php?taxonomy=tipo-de-propiedad'
);

add_submenu_page(
    'crm-v3-tax',
    'Tipo cartera',
    'Tipo cartera',
    'manage_options',
    'edit-tags.php?taxonomy=tipo-de-cartera'
);

add_submenu_page(
    'crm-v3-tax',
    'Tipo de operación',
    'Tipo de operación',
    'manage_options',
    'edit-tags.php?taxonomy=tipo-de-operacion'
);



    // ==========================
    // OCULTAR MENÚS DUPLICADOS
    // ==========================
    remove_menu_page('edit.php?post_type=clientes');
    remove_menu_page('edit.php?post_type=leads');
    remove_menu_page('edit.php?post_type=propiedades');
    remove_menu_page('edit.php?post_type=operaciones');
    remove_menu_page('edit.php?post_type=bancos');
    remove_menu_page('edit.php?post_type=notarias');
    remove_menu_page('edit.php?post_type=servicios');
    remove_menu_page('edit.php?post_type=valuadores');

}




/**
 * Dashboard principal
 */
function crm_v3_dashboard_page() {
    ?>
    <div class="wrap">
        <h1>CRM-V3 Dashboard</h1>
        <p>Panel central de administración del CRM inmobiliario.</p>
    </div>
    <?php
}



/**
 * Título de las páginas sin menú (Ficha del Cliente y Expediente).
 *
 * WordPress no les asigna título por no estar en ningún menú;
 * sin esto la pestaña del navegador queda sin nombre.
 */
function crm_v3_titulo_paginas_ocultas() {

    global $title;

    $titulos = array(
        'crm-ficha-cliente' => 'Ficha del Cliente',
        'crm-expediente'    => 'Expediente',
    );

    $pagina = isset($_GET['page'])
        ? sanitize_key($_GET['page'])
        : '';

    if (isset($titulos[$pagina])) {
        $title = $titulos[$pagina];
    }
}

add_action(
    'load-admin_page_crm-ficha-cliente',
    'crm_v3_titulo_paginas_ocultas'
);

add_action(
    'load-admin_page_crm-expediente',
    'crm_v3_titulo_paginas_ocultas'
);
