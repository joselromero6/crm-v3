<?php
/**
 * CRM V3 — Formulario de contacto del sitio web
 *
 * Recibe el formulario público, crea el Lead con los mismos campos
 * que usa CRM Leads y avisa por correo.
 *
 * - El visitante siempre recibe una respuesta (enviado / revisar
 *   datos / intentar más tarde).
 * - Tras procesar se redirige, así recargar la página no reenvía.
 * - Protección contra spam sin depender de un código que caduque
 *   con la caché: campo trampa, límite por visitante y descarte de
 *   envíos repetidos.
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Solo dígitos. Para teléfono, NSS y similares.
 */
function crm_v3_solo_digitos($valor) {

    return preg_replace('/\D+/', '', (string) $valor);
}


/**
 * Término "Sitio web" de la taxonomía Origen lead.
 *
 * Lo busca por nombre; si no existe, lo crea.
 */
function crm_v3_origen_lead_web_id() {

    $taxonomia = 'origen-lead';

    if (!taxonomy_exists($taxonomia)) {
        return 0;
    }

    $nombres = array(
        'Sitio web',
        'Página web',
        'Pagina web',
        'Web',
    );

    foreach ($nombres as $nombre) {

        $termino = get_term_by('name', $nombre, $taxonomia);

        if ($termino && !is_wp_error($termino)) {
            return (int) $termino->term_id;
        }
    }

    $nuevo = wp_insert_term('Sitio web', $taxonomia);

    if (is_wp_error($nuevo)) {
        return 0;
    }

    return (int) $nuevo['term_id'];
}


/**
 * Guarda un campo del Lead con ACF si está disponible.
 */
function crm_v3_lead_guardar_campo($lead_id, $campo, $valor) {

    if (function_exists('update_field')) {
        update_field($campo, $valor, $lead_id);
        return;
    }

    update_post_meta($lead_id, $campo, $valor);
}


/**
 * Crea un Lead a partir de los datos del formulario web.
 *
 * @return int ID del lead, o 0 si no se pudo crear.
 */
function crm_v3_crear_lead_desde_web($datos) {

    $lead_id = wp_insert_post(array(
        'post_type'   => 'leads',
        'post_status' => 'publish',
        'post_title'  => wp_slash($datos['nombre']),
    ));

    if (is_wp_error($lead_id) || !$lead_id) {
        return 0;
    }

    /*
     * Tipo de lead.
     *
     * El CRM maneja comprador / vendedor. Inversión y asesoría
     * no tienen equivalente, así que se anotan en las notas.
     */
    $tipos_crm = array(
        'comprador' => 'comprador',
        'vendedor'  => 'vendedor',
    );

    $intereses = array(
        'comprador' => 'Compra',
        'vendedor'  => 'Venta',
        'inversion' => 'Inversión',
        'asesoria'  => 'Asesoría',
    );

    $operacion = $datos['operacion'];

    $notas = $datos['mensaje'];

    if (isset($intereses[$operacion]) && !isset($tipos_crm[$operacion])) {

        $notas = trim(
            'Interés: ' . $intereses[$operacion] . "\n" . $notas
        );
    }

    crm_v3_lead_guardar_campo($lead_id, 'telefono', $datos['telefono']);
    crm_v3_lead_guardar_campo($lead_id, 'e-mail', $datos['email']);
    crm_v3_lead_guardar_campo($lead_id, 'notas', $notas);
    crm_v3_lead_guardar_campo($lead_id, 'estatus', 'en_proceso');

    if (isset($tipos_crm[$operacion])) {

        crm_v3_lead_guardar_campo(
            $lead_id,
            'tipo_de_lead',
            $tipos_crm[$operacion]
        );
    }

    $origen_id = crm_v3_origen_lead_web_id();

    if ($origen_id) {

        crm_v3_lead_guardar_campo(
            $lead_id,
            'origen_captacion',
            array($origen_id)
        );
    }

    return (int) $lead_id;
}


/**
 * Regresa al visitante a la página del formulario con el resultado.
 */
function crm_v3_contacto_web_regresar($resultado) {

    $destino = wp_get_referer();

    if (!$destino) {
        $destino = home_url('/contacto/');
    }

    $destino = remove_query_arg('contacto', $destino);

    wp_safe_redirect(
        add_query_arg('contacto', $resultado, $destino) . '#contacto-form'
    );

    exit;
}


/**
 * Procesa el formulario de contacto.
 */
function crm_v3_contacto_web_procesar() {

    $post = wp_unslash($_POST);

    /*
     * Campo trampa: invisible para las personas. Si llega con
     * contenido es un robot; se le responde como si todo salió bien.
     */
    if (!empty($post['crm_campo_extra'])) {
        crm_v3_contacto_web_regresar('ok');
    }

    $nombre = isset($post['nombre'])
        ? sanitize_text_field($post['nombre'])
        : '';

    $telefono = isset($post['telefono'])
        ? crm_v3_solo_digitos($post['telefono'])
        : '';

    $email = isset($post['email'])
        ? sanitize_email($post['email'])
        : '';

    $operacion = isset($post['operacion'])
        ? sanitize_key($post['operacion'])
        : '';

    $mensaje = isset($post['mensaje'])
        ? sanitize_textarea_field($post['mensaje'])
        : '';

    if (
        $nombre === '' ||
        strlen($telefono) < 7 ||
        !is_email($email) ||
        empty($post['aviso_privacidad'])
    ) {
        crm_v3_contacto_web_regresar('incompleto');
    }

    /*
     * Envío repetido: mismos datos en los últimos 10 minutos.
     * Se responde "enviado" sin crear otro Lead.
     */
    $huella = 'crm_v3_ctc_' . md5(
        strtolower($email) . '|' . $telefono . '|' . $mensaje
    );

    if (get_transient($huella)) {
        crm_v3_contacto_web_regresar('ok');
    }

    /*
     * Límite por visitante: 5 envíos por hora.
     */
    $ip = isset($_SERVER['REMOTE_ADDR'])
        ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
        : '';

    $limite = 'crm_v3_ctl_' . md5(wp_hash($ip));

    $envios = (int) get_transient($limite);

    if ($envios >= 5) {
        crm_v3_contacto_web_regresar('limite');
    }

    $lead_id = crm_v3_crear_lead_desde_web(array(
        'nombre'    => $nombre,
        'telefono'  => $telefono,
        'email'     => $email,
        'operacion' => $operacion,
        'mensaje'   => $mensaje,
    ));

    if (!$lead_id) {
        crm_v3_contacto_web_regresar('error');
    }

    set_transient($huella, 1, 10 * MINUTE_IN_SECONDS);
    set_transient($limite, $envios + 1, HOUR_IN_SECONDS);

    /*
     * Aviso por correo. El Lead ya está guardado: si el correo
     * falla, el visitante igual recibe "enviado".
     */
    $destino = apply_filters(
        'crm_v3_contacto_web_destino',
        'admin@cibr.com.mx'
    );

    $contenido =
        "Nombre: {$nombre}\n" .
        "Teléfono: {$telefono}\n" .
        "Email: {$email}\n" .
        "Operación: {$operacion}\n\n" .
        "Mensaje:\n{$mensaje}\n\n" .
        'Ver en el CRM: ' . admin_url('admin.php?page=crm-leads');

    wp_mail(
        $destino,
        'Nuevo contacto desde sitio web',
        $contenido,
        array('Reply-To: ' . $email)
    );

    crm_v3_contacto_web_regresar('ok');
}

add_action(
    'admin_post_nopriv_crm_v3_contacto_web',
    'crm_v3_contacto_web_procesar'
);

add_action(
    'admin_post_crm_v3_contacto_web',
    'crm_v3_contacto_web_procesar'
);


/**
 * Mensaje para el visitante según el resultado.
 *
 * @return array|null [ 'tipo' => 'ok'|'error', 'texto' => '...' ]
 */
function crm_v3_contacto_web_mensaje() {

    if (!isset($_GET['contacto'])) {
        return null;
    }

    $resultado = sanitize_key(wp_unslash($_GET['contacto']));

    $mensajes = array(
        'ok' => array(
            'tipo'  => 'ok',
            'texto' => 'Solicitud enviada correctamente. Nos pondremos en contacto contigo.',
        ),
        'incompleto' => array(
            'tipo'  => 'error',
            'texto' => 'Revisa tus datos: necesitamos tu nombre, un teléfono y un correo válidos, y que aceptes el Aviso de privacidad.',
        ),
        'limite' => array(
            'tipo'  => 'error',
            'texto' => 'Recibimos varias solicitudes desde tu conexión. Intenta más tarde o escríbenos por WhatsApp.',
        ),
        'error' => array(
            'tipo'  => 'error',
            'texto' => 'No pudimos registrar tu solicitud. Intenta de nuevo o escríbenos por WhatsApp.',
        ),
    );

    return isset($mensajes[$resultado])
        ? $mensajes[$resultado]
        : null;
}


/**
 * ============================================================
 * LEADS WEB ANTERIORES: COMPLETAR CAMPOS (una sola vez)
 * ============================================================
 *
 * El formulario anterior guardaba el correo, el tipo y el origen
 * con nombres que CRM Leads no lee. Se copian a los campos
 * correctos solo cuando el campo correcto está vacío.
 *
 * No se asigna estatus a los leads anteriores y no se borra nada.
 */

function crm_v3_migrar_leads_web() {

    $opcion = 'crm_v3_migracion_leads_web';

    if (get_option($opcion)) {
        return;
    }

    if (!post_type_exists('leads')) {
        return;
    }

    $leads = get_posts(array(
        'post_type'        => 'leads',
        'post_status'      => 'any',
        'posts_per_page'   => -1,
        'fields'           => 'ids',
        'suppress_filters' => true,
        'no_found_rows'    => true,
        'meta_query'       => array(
            'relation' => 'OR',
            array('key' => 'correo', 'compare' => 'EXISTS'),
            array('key' => 'tipo_de_cliente', 'compare' => 'EXISTS'),
        ),
    ));

    $resumen = array(
        'fecha'     => current_time('mysql'),
        'revisados' => count($leads),
        'correo'    => 0,
        'tipo'      => 0,
        'origen'    => 0,
    );

    $origen_id = $leads ? crm_v3_origen_lead_web_id() : 0;

    foreach ($leads as $lead_id) {

        $correo = sanitize_email(
            (string) get_post_meta($lead_id, 'correo', true)
        );

        if (
            $correo !== '' &&
            get_post_meta($lead_id, 'e-mail', true) === ''
        ) {
            crm_v3_lead_guardar_campo($lead_id, 'e-mail', $correo);
            $resumen['correo']++;
        }

        $tipo = sanitize_key(
            (string) get_post_meta($lead_id, 'tipo_de_cliente', true)
        );

        if (
            in_array($tipo, array('comprador', 'vendedor'), true) &&
            get_post_meta($lead_id, 'tipo_de_lead', true) === ''
        ) {
            crm_v3_lead_guardar_campo($lead_id, 'tipo_de_lead', $tipo);
            $resumen['tipo']++;
        }

        $origen_actual = get_post_meta(
            $lead_id,
            'origen_captacion',
            true
        );

        if ($origen_id && empty($origen_actual)) {

            crm_v3_lead_guardar_campo(
                $lead_id,
                'origen_captacion',
                array($origen_id)
            );

            $resumen['origen']++;
        }
    }

    update_option($opcion, $resumen, false);
}

add_action(
    'init',
    'crm_v3_migrar_leads_web',
    60
);
