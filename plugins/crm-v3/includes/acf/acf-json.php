<?php

if (!defined('ABSPATH')) {
    exit;
}

define(
    'CRM_V3_ACF_JSON',
    dirname(__DIR__, 2) . '/acf-json'
);


/*
IMPORTANTE

Este archivo vive en:
includes/acf/

Por eso:
dirname(__DIR__, 2)

apunta al root del plugin.
No cambiar sin revisar rutas JSON.
*/




/*
|--------------------------------------
| Guardar
|--------------------------------------
*/

add_filter('acf/settings/save_json', function () {

    return CRM_V3_ACF_JSON;

});


/*
|--------------------------------------
| Cargar
|--------------------------------------
*/

add_filter('acf/settings/load_json', function ($paths) {

    $paths[] = CRM_V3_ACF_JSON;

    return $paths;

});