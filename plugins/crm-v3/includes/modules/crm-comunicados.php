<?php
/**
 * ============================================================
 * COMUNICADOS INMOBILIARIOS
 * ============================================================
 *
 * Revisa periódicamente los comunicados de Infonavit y los avisos
 * oficiales que afectan o benefician a los derechohabientes, y los
 * muestra en el Backoffice como una lista: fecha, título y enlace.
 *
 * Cómo funciona:
 *
 * 1. Dos veces al día (y cuando se pulsa "Actualizar ahora") se
 *    consulta cada fuente.
 * 2. Lo nuevo se guarda en la lista; lo que ya estaba no se repite.
 * 3. El Backoffice solo lee la lista guardada: nunca espera a las
 *    páginas externas para cargar.
 *
 * La lectura del portal de Infonavit vive en comunicados.php.
 * ============================================================
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Fuentes que se revisan.
 *
 * Para agregar o quitar una fuente basta con modificar esta lista.
 * Cada fuente indica su nombre visible y la función que la consulta.
 */
function crm_v3_comunicados_fuentes() {

    $fuentes = array(

        'infonavit' => array(
            'nombre'  => 'Infonavit',
            'funcion' => 'crm_v3_comunicados_leer_infonavit',
        ),

        'oficial' => array(
            'nombre'  => 'Gobierno',
            'funcion' => 'crm_v3_comunicados_leer_avisos_oficiales',
        ),
    );

    return apply_filters('crm_v3_comunicados_fuentes', $fuentes);
}


/**
 * Palabras que debe mencionar un aviso oficial para mostrarse.
 */
function crm_v3_comunicados_palabras_clave() {

    return apply_filters(
        'crm_v3_comunicados_palabras_clave',
        array(
            'infonavit',
            'fovissste',
            'derechohabiente',
            'crédito hipotecario',
            'credito hipotecario',
            'vivienda',
        )
    );
}


/**
 * ¿El título es de una página que no es un comunicado?
 *
 * Los sitios oficiales también publican páginas de "archivo" o de
 * categoría ("Presupuesto Archives » Fondo de la Vivienda…"): son
 * índices, no avisos, y no deben aparecer en la lista.
 */
function crm_v3_comunicados_es_ruido($titulo) {

    $titulo = trim((string) $titulo);

    if ($titulo === '') {
        return true;
    }

    $senales = apply_filters(
        'crm_v3_comunicados_senales_ruido',
        array(
            'Archives',
            'Archivos »',
            '»',
            'Página ',
            'Inicio |',
            'Aviso de privacidad',
        )
    );

    foreach ($senales as $senal) {
        if ($senal !== '' && stripos($titulo, $senal) !== false) {
            return true;
        }
    }

    // Un título de comunicado real tiene al menos unas cuantas palabras.
    return str_word_count(remove_accents($titulo)) < 4;
}


/**
 * Página de la sala de prensa de Infonavit (enlace de respaldo).
 */
function crm_v3_comunicados_url_sala_prensa() {

    return 'https://portalmx.infonavit.org.mx/wps/portal/infonavitmx/mx2/el-instituto/el-infonavit/sala_prensa/';
}


/**
 * Convertir una fecha escrita ("2 de octubre de 2026") o en otro
 * formato a AAAA-MM-DD. Devuelve '' si no se reconoce.
 */
function crm_v3_comunicados_fecha($fecha) {

    $fecha = trim((string) $fecha);

    if ($fecha === '') {
        return '';
    }

    $meses = array(
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
        'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
        'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10,
        'noviembre' => 11, 'diciembre' => 12,
    );

    if (
        preg_match(
            '/(\d{1,2})\s+de\s+([a-záéíóú]+)\s+de\s+(\d{4})/iu',
            $fecha,
            $partes
        )
    ) {

        $mes = function_exists('mb_strtolower')
            ? mb_strtolower($partes[2], 'UTF-8')
            : strtolower($partes[2]);

        if (
            isset($meses[$mes]) &&
            checkdate($meses[$mes], (int) $partes[1], (int) $partes[3])
        ) {
            return sprintf(
                '%04d-%02d-%02d',
                $partes[3],
                $meses[$mes],
                $partes[1]
            );
        }

        return '';
    }

    $tiempo = strtotime($fecha);

    return $tiempo
        ? wp_date('Y-m-d', $tiempo)
        : '';
}


/**
 * Dejar un comunicado en el formato único de la lista.
 * Devuelve null si no tiene título.
 */
function crm_v3_comunicados_normalizar($item, $fuente) {

    $titulo = isset($item['titulo'])
        ? trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) $item['titulo'])))
        : '';

    if ($titulo === '' || crm_v3_comunicados_es_ruido($titulo)) {
        return null;
    }

    $url = isset($item['url'])
        ? esc_url_raw(trim((string) $item['url']))
        : '';

    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        $url = '';
    }

    $fecha = crm_v3_comunicados_fecha(
        isset($item['fecha']) ? $item['fecha'] : ''
    );

    $minusculas = function_exists('mb_strtolower')
        ? mb_strtolower($titulo, 'UTF-8')
        : strtolower($titulo);

    return array(
        // Un mismo comunicado siempre produce la misma clave,
        // así no se repite aunque se detecte muchas veces.
        'id'        => md5($minusculas),
        'titulo'    => $titulo,
        'fecha'     => $fecha,
        'url'       => $url,
        'fuente'    => $fuente,
        'detectado' => time(),
    );
}


/**
 * ============================================================
 * FUENTE 1 — BOLETINES DE INFONAVIT
 * ============================================================
 */

function crm_v3_comunicados_leer_infonavit() {

    if (!function_exists('crm_infonavit_gwt_leer_comunicados')) {
        return new WP_Error(
            'sin_lector',
            'No está disponible el lector de Infonavit.'
        );
    }

    $resultado = crm_infonavit_gwt_leer_comunicados();

    if (empty($resultado['ok'])) {

        $detalle = !empty($resultado['error'])
            ? $resultado['error']
            : 'sin respuesta';

        if (!empty($resultado['http'])) {
            $detalle .= ' (código ' . (int) $resultado['http'] . ')';
        }

        return new WP_Error(
            'infonavit',
            'El portal de Infonavit no respondió: ' . $detalle
        );
    }

    $lista = array();

    $comunicados = !empty($resultado['comunicados']) && is_array($resultado['comunicados'])
        ? $resultado['comunicados']
        : array();

    foreach ($comunicados as $comunicado) {

        $lista[] = array(
            'titulo' => isset($comunicado['titulo']) ? $comunicado['titulo'] : '',
            'fecha'  => isset($comunicado['fecha']) ? $comunicado['fecha'] : '',
            'url'    => !empty($comunicado['url'])
                ? $comunicado['url']
                : crm_v3_comunicados_url_sala_prensa(),
        );
    }

    if (empty($lista)) {
        return new WP_Error(
            'infonavit_vacio',
            'El portal de Infonavit respondió, pero no se reconoció ningún boletín. Es probable que Infonavit haya cambiado su página.'
        );
    }

    return $lista;
}


/**
 * ============================================================
 * FUENTE 2 — AVISOS OFICIALES (GOBIERNO)
 * ============================================================
 *
 * Busca publicaciones recientes en sitios oficiales (gob.mx,
 * Diario Oficial e Infonavit) que mencionen a Infonavit o Fovissste.
 */

function crm_v3_comunicados_url_avisos_oficiales() {

    $busqueda =
        '(infonavit OR fovissste) ' .
        '(site:gob.mx OR site:dof.gob.mx OR site:infonavit.org.mx) ' .
        'when:30d';

    $url = add_query_arg(
        array(
            'q'    => rawurlencode($busqueda),
            'hl'   => 'es-419',
            'gl'   => 'MX',
            'ceid' => 'MX:es-419',
        ),
        'https://news.google.com/rss/search'
    );

    return apply_filters('crm_v3_comunicados_url_avisos_oficiales', $url);
}


function crm_v3_comunicados_leer_avisos_oficiales() {

    $respuesta = wp_remote_get(
        crm_v3_comunicados_url_avisos_oficiales(),
        array(
            'timeout'    => 20,
            'user-agent' => 'Mozilla/5.0 (compatible; CRM-CIBR)',
        )
    );

    if (is_wp_error($respuesta)) {
        return new WP_Error(
            'oficial',
            'No se pudo consultar los avisos oficiales: ' . $respuesta->get_error_message()
        );
    }

    $codigo = (int) wp_remote_retrieve_response_code($respuesta);

    if ($codigo !== 200) {
        return new WP_Error(
            'oficial',
            'Los avisos oficiales no respondieron (código ' . $codigo . ').'
        );
    }

    return crm_v3_comunicados_leer_rss(
        wp_remote_retrieve_body($respuesta)
    );
}


/**
 * Leer una lista RSS y quedarse con lo que menciona las palabras clave.
 */
function crm_v3_comunicados_leer_rss($xml) {

    if (!function_exists('simplexml_load_string') || trim((string) $xml) === '') {
        return new WP_Error('rss', 'La respuesta de avisos oficiales llegó vacía.');
    }

    $anterior = libxml_use_internal_errors(true);
    $rss      = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($anterior);

    if (!$rss || !isset($rss->channel)) {
        return new WP_Error('rss', 'La respuesta de avisos oficiales no se pudo leer.');
    }

    $palabras = crm_v3_comunicados_palabras_clave();
    $lista    = array();

    foreach ($rss->channel->item as $item) {

        $titulo = trim((string) $item->title);
        $medio  = trim((string) $item->source);

        // Los títulos llegan como "Título - Nombre del sitio".
        if ($medio !== '' && substr($titulo, -strlen(' - ' . $medio)) === ' - ' . $medio) {
            $titulo = substr($titulo, 0, -strlen(' - ' . $medio));
        }

        $minusculas = function_exists('mb_strtolower')
            ? mb_strtolower($titulo, 'UTF-8')
            : strtolower($titulo);

        $relevante = false;

        foreach ($palabras as $palabra) {
            if ($palabra !== '' && strpos($minusculas, $palabra) !== false) {
                $relevante = true;
                break;
            }
        }

        if (!$relevante) {
            continue;
        }

        $lista[] = array(
            'titulo' => $titulo,
            'fecha'  => (string) $item->pubDate,
            'url'    => (string) $item->link,
        );
    }

    return $lista;
}


/**
 * ============================================================
 * LISTA GUARDADA
 * ============================================================
 */

function crm_v3_comunicados_lista() {

    $lista = get_option('crm_v3_comunicados', array());

    return is_array($lista) ? $lista : array();
}


function crm_v3_comunicados_estado() {

    $estado = get_option('crm_v3_comunicados_estado', array());

    return is_array($estado) ? $estado : array();
}


/**
 * Ordenar: lo más reciente primero. Si un comunicado no trae
 * fecha, se usa el día en que el sistema lo detectó.
 */
function crm_v3_comunicados_ordenar($lista) {

    usort(
        $lista,
        function ($a, $b) {

            $fecha_a = !empty($a['fecha']) ? $a['fecha'] : wp_date('Y-m-d', $a['detectado']);
            $fecha_b = !empty($b['fecha']) ? $b['fecha'] : wp_date('Y-m-d', $b['detectado']);

            if ($fecha_a === $fecha_b) {
                return $b['detectado'] <=> $a['detectado'];
            }

            return strcmp($fecha_b, $fecha_a);
        }
    );

    return $lista;
}


/**
 * Comunicados que se muestran en la tarjeta.
 *
 * Se reparte el espacio entre las fuentes (por ejemplo, 4 y 4) y,
 * si a una le sobran lugares, los aprovecha la otra. El resultado
 * se muestra ordenado por fecha.
 */
function crm_v3_comunicados_visibles($limite = 8) {

    $lista      = crm_v3_comunicados_lista();
    $por_fuente = array();

    foreach ($lista as $comunicado) {
        $por_fuente[$comunicado['fuente']][] = $comunicado;
    }

    if (count($por_fuente) < 2) {
        return array_slice($lista, 0, $limite);
    }

    $visibles = array();

    // Se toma uno de cada fuente por turno hasta llenar la tarjeta.
    while (count($visibles) < $limite && !empty($por_fuente)) {

        foreach (array_keys($por_fuente) as $clave) {

            if (count($visibles) >= $limite) {
                break;
            }

            $visibles[] = array_shift($por_fuente[$clave]);

            if (empty($por_fuente[$clave])) {
                unset($por_fuente[$clave]);
            }
        }
    }

    return crm_v3_comunicados_ordenar($visibles);
}


/**
 * ============================================================
 * REVISAR LAS FUENTES Y GUARDAR LO NUEVO
 * ============================================================
 */

function crm_v3_comunicados_actualizar() {

    $guardados = array();

    foreach (crm_v3_comunicados_lista() as $comunicado) {

        // Lo guardado que hoy se considera "ruido" se descarta.
        if (
            !empty($comunicado['id']) &&
            !crm_v3_comunicados_es_ruido($comunicado['titulo'])
        ) {
            $guardados[$comunicado['id']] = $comunicado;
        }
    }

    // En la primera revisión todo es "nuevo"; no tiene caso resaltarlo.
    $primera_vez = empty($guardados);

    $estado = array(
        'revisado' => time(),
        'nuevos'   => 0,
        'fuentes'  => array(),
    );

    foreach (crm_v3_comunicados_fuentes() as $clave => $fuente) {

        $resultado = is_callable($fuente['funcion'])
            ? call_user_func($fuente['funcion'])
            : new WP_Error('fuente', 'Fuente no disponible.');

        if (is_wp_error($resultado)) {

            $estado['fuentes'][$clave] = array(
                'ok'    => false,
                'error' => $resultado->get_error_message(),
            );

            continue;
        }

        $encontrados = 0;

        foreach ((array) $resultado as $item) {

            $comunicado = crm_v3_comunicados_normalizar($item, $clave);

            if (!$comunicado) {
                continue;
            }

            $encontrados++;

            if (isset($guardados[$comunicado['id']])) {
                continue;
            }

            if ($primera_vez) {
                $comunicado['inicial'] = true;
            }

            $guardados[$comunicado['id']] = $comunicado;
            $estado['nuevos']++;
        }

        $estado['fuentes'][$clave] = array(
            'ok'          => true,
            'encontrados' => $encontrados,
        );
    }

    // Se conservan los 40 más recientes de cada fuente, para que una
    // fuente con muchas publicaciones no desplace a las demás.
    $lista      = array();
    $por_fuente = array();

    foreach (crm_v3_comunicados_ordenar(array_values($guardados)) as $comunicado) {

        $clave = $comunicado['fuente'];

        $por_fuente[$clave] = isset($por_fuente[$clave])
            ? $por_fuente[$clave] + 1
            : 1;

        if ($por_fuente[$clave] <= 40) {
            $lista[] = $comunicado;
        }
    }

    update_option('crm_v3_comunicados', $lista, false);
    update_option('crm_v3_comunicados_estado', $estado, false);

    return $estado;
}


/**
 * ============================================================
 * REVISIÓN AUTOMÁTICA (dos veces al día)
 * ============================================================
 */

function crm_v3_comunicados_programar() {

    if (!wp_next_scheduled('crm_v3_comunicados_revisar')) {

        wp_schedule_event(
            time() + 5 * MINUTE_IN_SECONDS,
            'twicedaily',
            'crm_v3_comunicados_revisar'
        );
    }
}

add_action('init', 'crm_v3_comunicados_programar');
add_action('crm_v3_comunicados_revisar', 'crm_v3_comunicados_actualizar');


/**
 * Al desactivar el plugin se cancela la revisión automática.
 */
function crm_v3_comunicados_desprogramar() {
    wp_clear_scheduled_hook('crm_v3_comunicados_revisar');
}

register_deactivation_hook(
    CRM_V3_PATH . 'crm-v3.php',
    'crm_v3_comunicados_desprogramar'
);


/**
 * ============================================================
 * BOTÓN "ACTUALIZAR AHORA"
 * ============================================================
 */

function crm_v3_comunicados_actualizar_manual() {

    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para esta acción.');
    }

    check_admin_referer('crm_v3_comunicados_actualizar');

    crm_v3_comunicados_actualizar();

    wp_safe_redirect(
        admin_url('admin.php?page=crm-v3-cibr&comunicados=1#crm-comunicados')
    );

    exit;
}

add_action(
    'admin_post_crm_v3_comunicados_actualizar',
    'crm_v3_comunicados_actualizar_manual'
);


/**
 * ============================================================
 * TARJETA DEL BACKOFFICE
 * ============================================================
 */

function crm_v3_comunicados_render($limite = 8) {

    $lista   = crm_v3_comunicados_visibles($limite);
    $estado  = crm_v3_comunicados_estado();
    $fuentes = crm_v3_comunicados_fuentes();

    ?>

    <div class="crm-bo-comunicados" id="crm-comunicados">

        <?php if (empty($lista)) : ?>

            <p class="crm-bo-comunicados-vacio">
                <?php echo empty($estado['revisado'])
                    ? 'Aún no se ha hecho la primera revisión. Pulsa "Actualizar ahora".'
                    : 'No se encontraron comunicados en la última revisión.'; ?>
            </p>

        <?php else : ?>

            <ul class="crm-bo-comunicados-lista">

                <?php foreach ($lista as $comunicado) : ?>

                    <?php
                    $fecha = !empty($comunicado['fecha'])
                        ? $comunicado['fecha']
                        : wp_date('Y-m-d', $comunicado['detectado']);

                    $fuente = isset($fuentes[$comunicado['fuente']])
                        ? $fuentes[$comunicado['fuente']]['nombre']
                        : $comunicado['fuente'];

                    // "Nuevo": detectado en los últimos 3 días.
                    $es_nuevo =
                        empty($comunicado['inicial']) &&
                        (time() - (int) $comunicado['detectado']) < 3 * DAY_IN_SECONDS;
                    ?>

                    <li class="crm-bo-comunicado<?php echo $es_nuevo ? ' is-nuevo' : ''; ?>">

                        <span class="crm-bo-comunicado-fecha">
                            <?php echo esc_html(crm_v3_format_date($fecha)); ?>
                        </span>

                        <span class="crm-bo-comunicado-fuente crm-bo-comunicado-fuente-<?php echo esc_attr($comunicado['fuente']); ?>">
                            <?php echo esc_html($fuente); ?>
                        </span>

                        <?php if (!empty($comunicado['url'])) : ?>

                            <a
                                class="crm-bo-comunicado-titulo"
                                href="<?php echo esc_url($comunicado['url']); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                title="<?php echo esc_attr($comunicado['titulo']); ?>"
                            ><?php echo esc_html($comunicado['titulo']); ?></a>

                        <?php else : ?>

                            <span
                                class="crm-bo-comunicado-titulo"
                                title="<?php echo esc_attr($comunicado['titulo']); ?>"
                            ><?php echo esc_html($comunicado['titulo']); ?></span>

                        <?php endif; ?>

                    </li>

                <?php endforeach; ?>

            </ul>

        <?php endif; ?>


        <?php if (!empty($estado['fuentes'])) : ?>

            <?php foreach ($estado['fuentes'] as $clave => $resultado) : ?>

                <?php if (empty($resultado['ok'])) : ?>

                    <p class="crm-bo-comunicados-aviso">
                        <strong><?php echo esc_html(
                            isset($fuentes[$clave]) ? $fuentes[$clave]['nombre'] : $clave
                        ); ?>:</strong>
                        <?php echo esc_html($resultado['error']); ?>
                    </p>

                <?php endif; ?>

            <?php endforeach; ?>

        <?php endif; ?>


        <div class="crm-bo-comunicados-pie">

            <span>
                <?php if (!empty($estado['revisado'])) : ?>

                    Última revisión:
                    <?php echo esc_html(wp_date('d/m/Y H:i', $estado['revisado'])); ?>

                    <?php
                    $resumen = array();

                    if (!empty($estado['fuentes'])) {

                        foreach ($estado['fuentes'] as $clave => $resultado) {

                            if (!empty($resultado['ok'])) {
                                $resumen[] =
                                    (isset($fuentes[$clave]) ? $fuentes[$clave]['nombre'] : $clave) .
                                    ' ' . (int) $resultado['encontrados'];
                            }
                        }
                    }
                    ?>

                    <?php if (!empty($resumen)) : ?>
                        · <?php echo esc_html(implode(', ', $resumen)); ?>
                    <?php endif; ?>

                    <?php if (isset($_GET['comunicados'])) : ?>
                        ·
                        <?php echo esc_html(
                            !empty($estado['nuevos'])
                                ? (int) $estado['nuevos'] . ' nuevo(s)'
                                : 'sin novedades'
                        ); ?>
                    <?php endif; ?>

                <?php else : ?>

                    Revisión automática dos veces al día.

                <?php endif; ?>
            </span>

            <form
                method="post"
                action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
            >
                <input type="hidden" name="action" value="crm_v3_comunicados_actualizar">

                <?php wp_nonce_field('crm_v3_comunicados_actualizar'); ?>

                <button type="submit" class="crm-bo-comunicados-btn">
                    Actualizar ahora
                </button>
            </form>

        </div>

    </div>

    <?php
}
