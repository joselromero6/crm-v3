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
            // Infonavit entrega siempre su lista completa del año:
            // lo que ya no venga en ella se quita de la tarjeta.
            'completa' => true,
        ),

        'noticias' => array(
            'nombre'  => 'Noticias',
            'funcion' => 'crm_v3_comunicados_leer_noticias',
        ),
    );

    return apply_filters('crm_v3_comunicados_fuentes', $fuentes);
}


/**
 * ============================================================
 * QUÉ SE CONSIDERA RELEVANTE
 * ============================================================
 *
 * Solo interesan los cambios de reglas, políticas, impuestos o
 * requisitos que afectan o benefician al sector inmobiliario.
 * Ejemplos: sube el predial, Infonavit cambia cómo consulta el buró,
 * nuevas tasas, nuevos requisitos de crédito.
 *
 * NO interesan las notas de eventos: entregas de casas, arranques de
 * obra, ferias, cifras de créditos entregados.
 *
 * Un texto es relevante cuando cumple las tres condiciones:
 *
 * 1. Habla de un TEMA inmobiliario.
 * 2. Habla de un CAMBIO.
 * 3. No es una nota de EVENTO.
 *
 * Las tres listas se pueden ajustar aquí mismo. Se escriben en
 * minúsculas y sin acentos; basta con el inicio de la palabra
 * ("requisit" reconoce requisito y requisitos).
 */

function crm_v3_comunicados_palabras_tema() {

    return apply_filters(
        'crm_v3_comunicados_palabras_tema',
        array(
            'infonavit', 'fovissste', 'derechohabient', 'predial',
            'catastr', 'hipotec', 'vivienda', 'inmobiliari', 'inmueble',
            'escritura', 'notari', 'isr', 'isai', 'plusvalia',
            'arrendamiento', 'renta de casa', 'uso de suelo',
            'credito para casa', 'subcuenta', 'buro de credito',
            'mejoravit', 'unamos creditos', 'cofinavit',
        )
    );
}


function crm_v3_comunicados_palabras_cambio() {

    return apply_filters(
        'crm_v3_comunicados_palabras_cambio',
        array(
            'cambi', 'modific', 'reform', 'nueva regla', 'nuevas reglas',
            'nuevo esquema', 'nuevo programa', 'nuevo requisito',
            'nuevos requisitos', 'nueva ley', 'nuevo modelo',
            'regla', 'requisit', 'lineamient', 'decreto',
            'iniciativa', 'aprueb', 'aprob', 'entra en vigor', 'vigor',
            'a partir de', 'ya no', 'ahora', 'dejara de', 'deja de',
            'elimin', 'desaparec', 'sustitu', 'aument', 'increment',
            'sube', 'subira', 'alza', 'bajan', 'bajara', 'reduc',
            'descuento', 'condon', 'congel', 'tope', 'tasa',
            'puntos', 'puntaje', 'buro', 'actualiz', 'ajust', 'tarifa',
            'impuesto', 'tabla de valores', 'valores catastrales',
            'ley de ingresos', 'miscelanea', 'prohib', 'obligatori',
            'requerira', 'permitira', 'podran', 'anuncia cambios',
            'reestructura', 'quitan', 'quitara', 'nuev', 'ya se',
            'se pueden', 'se podra', 'lanza', 'amplia', 'extiende',
            'prorrog', 'flexibiliz', 'facilidades', 'esquema',
        )
    );
}


function crm_v3_comunicados_palabras_evento() {

    return apply_filters(
        'crm_v3_comunicados_palabras_evento',
        array(
            'entrega de vivienda', 'entregan vivienda', 'entrega vivienda',
            'entregaron', 'entrega de llaves', 'entregan llaves',
            'entrega de escrituras', 'entregan escrituras',
            'viviendas del bienestar', 'primera piedra', 'arranca',
            'arranque', 'inicia construccion', 'inicio de construccion',
            'construccion de', 'construira', 'se construyen',
            'fraccionamiento', 'conjunto habitacional', 'recorrido',
            'supervisa', 'visita', 'gira', 'inaugura', 'feria',
            'jornada', 'sorteo', 'liquida', 'familias beneficiadas',
            'da a conocer intervencion', 'convenio de colaboracion',
            'firma convenio', 'reunion', 'se reune', 'conmemora',
            'aniversario', 'reconocimiento', 'premio', 'torneo',
            'presenta avances', 'mananera', 'ha contratado', 'entregado',
            'entrega en', 'entrega escrituras', 'entrega primeras',
            'entrega de', 'trabajos de limpieza', 'reitera',
            'incendio', 'conato', 'falla en', 'accidente', 'muere',
            'fallece', 'detienen', 'robo', 'asalto', 'balacera',
            'inundacion', 'explosion', 'desalojo', 'fraude',
        )
    );
}


/**
 * Frases de las notas tipo "guía" o "paso a paso": explican un
 * trámite que ya existe, no anuncian un cambio.
 */
function crm_v3_comunicados_palabras_guia() {

    return apply_filters(
        'crm_v3_comunicados_palabras_guia',
        array(
            'paso a paso', 'como solicitar', 'como sacar', 'como tramitar',
            'como obtener', 'como consultar', 'como saber', 'como puedo',
            'como pedir', 'como usar', 'como funciona', 'como hacer',
            'asi puedes', 'asi puede', 'te decimos', 'te contamos',
            'te explicamos', 'guia para', 'guia completa', 'que es el',
            'que es la', 'que significa', 'cuanto presta', 'cuanto te presta',
            'cuantos puntos necesit', 'donde consultar', 'tutorial',
            // Notas hechas para atraer clics.
            'cuanto', 'cuanta', 'deposita', 'depositara', 'requisitos y pasos',
            'pasos para', 'quien puede', 'quienes pueden', 'si cumples',
            'estos son los requisitos', 'esto es lo que', 'checa',
            'te conviene', 'tips', 'calendario', 'fechas de pago',
            'pensionados', 'jubilados', 'que pasa si', 'esto debes',
        )
    );
}


/**
 * Medios de los que se aceptan noticias.
 *
 * Solo fuentes oficiales y medios serios; así se evita la mayor parte
 * de las notas hechas para atraer clics. Se escribe la dirección del
 * sitio sin "www". Un sitio de la lista incluye sus secciones
 * (por ejemplo "gob.mx" incluye "jalisco.gob.mx").
 */
function crm_v3_comunicados_medios_confiables() {

    return apply_filters(
        'crm_v3_comunicados_medios_confiables',
        array(
            // Oficiales
            'gob.mx', 'infonavit.org.mx', 'fovissste.gob.mx',
            'diputados.gob.mx', 'senado.gob.mx', 'banxico.org.mx',
            'shf.gob.mx', 'congresojal.gob.mx',

            // Economía y negocios
            'eleconomista.com.mx', 'elfinanciero.com.mx', 'expansion.mx',
            'forbes.com.mx', 'elceo.com',

            // Sector inmobiliario
            'realestatemarket.com.mx', 'inmobiliare.com', 'centrourbano.com',

            // Nacionales
            'eluniversal.com.mx', 'milenio.com', 'excelsior.com.mx',
            'reforma.com', 'jornada.com.mx', 'proceso.com.mx',
            'animalpolitico.com', 'elsoldemexico.com.mx',

            // Jalisco
            'informador.mx', 'mural.com.mx', 'eloccidental.com.mx',
            'ntrguadalajara.com', 'udgtv.com',
        )
    );
}


/**
 * ¿La dirección pertenece a un medio de la lista?
 */
function crm_v3_comunicados_es_medio_confiable($url) {

    $dominio = strtolower((string) wp_parse_url((string) $url, PHP_URL_HOST));
    $dominio = preg_replace('/^www\./', '', $dominio);

    if ($dominio === '') {
        return false;
    }

    foreach (crm_v3_comunicados_medios_confiables() as $medio) {

        $medio = strtolower(trim($medio));

        if (
            $dominio === $medio ||
            substr($dominio, -strlen('.' . $medio)) === '.' . $medio
        ) {
            return true;
        }
    }

    return false;
}


/**
 * Palabras que indican que la nota es de otro país.
 */
function crm_v3_comunicados_palabras_otro_pais() {

    return apply_filters(
        'crm_v3_comunicados_palabras_otro_pais',
        array(
            'espana', 'junts', 'vox', 'psoe', 'pp', 'podemos',
            'real decreto', 'decreto ley', 'decretos ley',
            'congreso de los diputados', 'generalitat', 'moncloa',
            'madrid', 'barcelona', 'cataluna', 'andalucia', 'valencia',
            'euskadi', 'alquiler', 'ibi', 'colombia', 'bogota',
            'argentina', 'buenos aires', 'chile', 'peru', 'ecuador',
            'venezuela', 'uruguay', 'paraguay', 'bolivia', 'guatemala',
            'costa rica', 'panama', 'honduras', 'el salvador',
        )
    );
}


/**
 * Texto en minúsculas y sin acentos, para comparar.
 */
function crm_v3_comunicados_texto_plano($texto) {

    $texto = remove_accents(
        wp_strip_all_tags((string) $texto)
    );

    $texto = function_exists('mb_strtolower')
        ? mb_strtolower($texto, 'UTF-8')
        : strtolower($texto);

    return ' ' . trim(preg_replace('/[^a-z0-9ñ]+/u', ' ', $texto)) . ' ';
}


/**
 * ¿Aparece alguna palabra de la lista? Las palabras se buscan al
 * inicio de palabra, para que "isr" no coincida dentro de otra.
 */
function crm_v3_comunicados_contiene($texto_plano, $palabras) {

    foreach ($palabras as $palabra) {

        $palabra = trim((string) $palabra);

        if ($palabra === '') {
            continue;
        }

        // Las siglas cortas deben ser la palabra completa.
        $buscar = strlen($palabra) <= 3
            ? ' ' . $palabra . ' '
            : ' ' . $palabra;

        if (strpos($texto_plano, $buscar) !== false) {
            return true;
        }
    }

    return false;
}


/**
 * ¿El comunicado trata de un cambio que afecta al sector?
 *
 * $titulo  Encabezado.
 * $extracto  Primeras líneas del texto, si se tienen.
 */
function crm_v3_comunicados_es_relevante($titulo, $extracto = '') {

    // Un título con una cantidad en pesos ("te deposita $170,000")
    // casi siempre es una nota para atraer clics.
    if (preg_match('/\$\s?\d/', (string) $titulo)) {
        return false;
    }

    // "Nuevo León" no es una novedad.
    $plano_titulo = str_replace(
        ' nuevo leon ',
        ' ',
        crm_v3_comunicados_texto_plano($titulo)
    );

    $plano_todo = $plano_titulo . crm_v3_comunicados_texto_plano($extracto);

    // Las notas de evento y las de otros países se reconocen
    // por su encabezado.
    if (
        crm_v3_comunicados_contiene($plano_titulo, crm_v3_comunicados_palabras_evento()) ||
        crm_v3_comunicados_contiene($plano_titulo, crm_v3_comunicados_palabras_guia()) ||
        crm_v3_comunicados_contiene($plano_titulo, crm_v3_comunicados_palabras_otro_pais())
    ) {
        return false;
    }

    // El tema puede estar en el encabezado o en el resumen;
    // el cambio debe anunciarse en el encabezado.
    return
        crm_v3_comunicados_contiene($plano_todo, crm_v3_comunicados_palabras_tema()) &&
        crm_v3_comunicados_contiene($plano_titulo, crm_v3_comunicados_palabras_cambio());
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

    $extracto = isset($item['extracto'])
        ? wp_html_excerpt(wp_strip_all_tags((string) $item['extracto']), 400)
        : '';

    if (
        $titulo === '' ||
        crm_v3_comunicados_es_ruido($titulo) ||
        !crm_v3_comunicados_es_relevante($titulo, $extracto)
    ) {
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
        'extracto'  => $extracto,
        'cuerpo'    => isset($item['cuerpo']) ? (string) $item['cuerpo'] : '',
        'medio'     => isset($item['medio']) ? sanitize_text_field((string) $item['medio']) : '',
        'detectado' => time(),
    );
}


/**
 * Palabras importantes de un título (para comparar dos títulos).
 */
function crm_v3_comunicados_palabras_de($titulo) {

    $palabras = array();

    foreach (explode(' ', crm_v3_comunicados_texto_plano($titulo)) as $palabra) {

        // Se ignoran las palabras cortas (el, de, para, que…).
        // De cada palabra cuentan sus primeras 5 letras, para que
        // "quién puede" y "quiénes pueden" se consideren iguales.
        if (strlen($palabra) >= 4) {
            $palabras[substr($palabra, 0, 5)] = true;
        }
    }

    return $palabras;
}


/**
 * ¿Dos títulos hablan de lo mismo?
 *
 * Varios medios publican la misma noticia con el título casi igual.
 * Se consideran repetidos cuando comparten la gran mayoría de sus
 * palabras importantes.
 */
function crm_v3_comunicados_son_parecidos($titulo_a, $titulo_b) {

    $a = crm_v3_comunicados_palabras_de($titulo_a);
    $b = crm_v3_comunicados_palabras_de($titulo_b);

    if (count($a) < 3 || count($b) < 3) {
        return false;
    }

    // Si una habla de Infonavit y la otra de Fovissste (o de
    // municipios distintos), son noticias diferentes.
    $claves = array_fill_keys(
        array(
            'infon', 'fovis', 'predi', 'guada', 'zapop',
            'tlajo', 'tlaqu', 'tonal', 'jalis', 'banco', 'notar',
        ),
        true
    );

    if (array_intersect_key($a, $claves) != array_intersect_key($b, $claves)) {
        return false;
    }

    $comunes = count(array_intersect_key($a, $b));

    return ($comunes / min(count($a), count($b))) >= 0.6;
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

    $lista = crm_v3_comunicados_boletines_infonavit(
        !empty($resultado['debug_strings']) && is_array($resultado['debug_strings'])
            ? $resultado['debug_strings']
            : array()
    );

    if (empty($lista)) {
        return new WP_Error(
            'infonavit_vacio',
            'El portal de Infonavit respondió, pero no se reconoció ningún boletín. Es probable que Infonavit haya cambiado su página.'
        );
    }

    return $lista;
}


/**
 * Texto limpio de un fragmento de la respuesta de Infonavit:
 * sin etiquetas, sin espacios especiales ni caracteres dañados.
 */
function crm_v3_comunicados_texto_infonavit($html) {

    $html = (string) $html;

    // Caracteres que no son UTF-8 válido (espacios especiales sueltos).
    if (function_exists('mb_scrub')) {
        $anterior = mb_substitute_character();
        mb_substitute_character(0x20);
        $html = mb_scrub($html, 'UTF-8');
        mb_substitute_character($anterior);
    } else {
        $html = wp_check_invalid_utf8($html, true);
    }

    // Algunos boletines traen su propio HTML escrito como texto.
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Cada párrafo o viñeta termina una frase.
    $html = preg_replace('#</(p|li|div|h[1-6])>|<br\s*/?>#i', ' | ', $html);

    $texto = html_entity_decode(
        wp_strip_all_tags($html),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    $texto = str_replace(
        array("\xC2\xA0", "\xEF\xBF\xBD"),
        ' ',
        $texto
    );

    $texto = preg_replace('/\s+/u', ' ', $texto);
    $texto = preg_replace('/(\s*\|\s*)+/u', ' | ', $texto);

    return trim($texto, " |\t\n");
}


/**
 * Texto completo de un boletín, en párrafos separados por línea
 * en blanco. Se quitan las fotos y los controles del carrusel.
 */
function crm_v3_comunicados_cuerpo_infonavit($crudo) {

    $html = stripcslashes((string) $crudo);

    // Todo lo anterior al último control del carrusel son fotos.
    $corte = strripos($html, 'carousel-control-next');

    if ($corte !== false) {

        $fin = stripos($html, '</a>', $corte);

        $html = $fin !== false
            ? substr($html, $fin + 4)
            : substr($html, $corte);
    }

    $texto = crm_v3_comunicados_texto_infonavit($html);

    $parrafos = array();

    foreach (explode('|', $texto) as $parrafo) {

        $parrafo = trim($parrafo, " *\t");

        if ($parrafo !== '') {
            $parrafos[] = $parrafo;
        }
    }

    $cuerpo = implode("\n\n", $parrafos);

    // Un boletín muy largo se recorta para no cargar la base de datos.
    return function_exists('mb_substr')
        ? mb_substr($cuerpo, 0, 8000)
        : substr($cuerpo, 0, 8000);
}


/**
 * Reconocer los boletines dentro de la respuesta de Infonavit.
 *
 * Cada boletín llega como una serie de textos seguidos:
 *
 *   fecha  →  cuerpo  →  número de boletín  →  resumen
 *
 * El resumen empieza con el encabezado (en negritas rojas) y sigue
 * con dos o tres viñetas. De ahí se toman el título y el extracto.
 */
function crm_v3_comunicados_boletines_infonavit($strings) {

    $patron_fecha =
        '/\b(\d{1,2})\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\s+de\s+(\d{4})\b/iu';

    $strings = array_values($strings);
    $total   = count($strings);
    $lista   = array();

    for ($i = 0; $i < $total; $i++) {

        $crudo = stripcslashes((string) $strings[$i]);

        // El resumen es un texto con negritas que no es el cuerpo
        // (el cuerpo trae el carrusel de fotos o es mucho más largo).
        if (
            stripos($crudo, 'strong') === false ||
            stripos($crudo, 'carousel') !== false ||
            strlen($crudo) > 2500
        ) {
            continue;
        }

        // Justo antes del resumen va el número de boletín.
        $anterior = $i > 0
            ? trim(stripcslashes((string) $strings[$i - 1]))
            : '';

        if (!preg_match('/^\d{1,4}$/', $anterior)) {
            continue;
        }

        $texto  = crm_v3_comunicados_texto_infonavit($crudo);
        $partes = array_values(array_filter(array_map('trim', explode('|', $texto))));

        if (empty($partes)) {
            continue;
        }

        $titulo = array_shift($partes);

        // La fecha puede venir en el propio resumen
        // ("Ciudad de México, a 4 de agosto de 2026")…
        $fecha    = '';
        $extracto = array();

        foreach ($partes as $parte) {

            if (mb_strlen($parte) < 90 && preg_match($patron_fecha, $parte, $coincide)) {

                if ($fecha === '') {
                    $fecha = $coincide[0];
                }

                continue;
            }

            $extracto[] = $parte;
        }

        // …o como texto suelto dos lugares antes del número.
        if ($fecha === '' && $i >= 3) {

            $candidata = trim(stripcslashes((string) $strings[$i - 3]));

            if (mb_strlen($candidata) < 40 && preg_match($patron_fecha, $candidata, $coincide)) {
                $fecha = $coincide[0];
            }
        }

        // Último recurso: la misma fecha del boletín anterior de la lista.
        if ($fecha === '' && !empty($lista)) {
            $fecha = $lista[count($lista) - 1]['fecha'];
        }

        // Dos lugares antes del resumen va el texto completo.
        $cuerpo = $i >= 2
            ? crm_v3_comunicados_cuerpo_infonavit($strings[$i - 2])
            : '';

        $lista[] = array(
            'titulo'   => $titulo,
            'fecha'    => $fecha,
            'extracto' => implode(' ', $extracto),
            'cuerpo'   => $cuerpo,
            'url'      => crm_v3_comunicados_url_sala_prensa(),
        );
    }

    return $lista;
}


/**
 * ============================================================
 * FUENTE 2 — NOTICIAS DEL SECTOR
 * ============================================================
 *
 * Busca noticias recientes (últimos 30 días) sobre cambios en
 * Infonavit, Fovissste, predial e impuestos de la vivienda.
 * Cada línea de la lista es una búsqueda; se pueden agregar más.
 */

function crm_v3_comunicados_busquedas() {

    $zona = '(Jalisco OR Guadalajara OR Zapopan OR Tlajomulco OR Tlaquepaque OR Tonalá)';

    return apply_filters(
        'crm_v3_comunicados_busquedas',
        array(
            'Infonavit (cambios OR "nuevas reglas" OR reforma OR requisitos OR buró OR tasa OR "a partir de" OR "ya no")',
            'Fovissste (cambios OR "nuevas reglas" OR reforma OR requisitos OR tasa OR "a partir de")',
            'predial (aumento OR incremento OR descuento OR tarifas OR "valores catastrales") ' . $zona,
            '(escrituración OR "crédito hipotecario" OR "ley de vivienda" OR ISR OR notarios) vivienda México (reforma OR cambios OR "nuevas reglas" OR decreto)',
            '(Infonavit OR Fovissste OR vivienda) (decreto OR acuerdo OR reforma) (site:dof.gob.mx OR site:gob.mx)',
        )
    );
}


function crm_v3_comunicados_url_busqueda($busqueda) {

    return add_query_arg(
        array(
            'q'    => rawurlencode($busqueda . ' when:30d'),
            'hl'   => 'es-419',
            'gl'   => 'MX',
            'ceid' => 'MX:es-419',
        ),
        'https://news.google.com/rss/search'
    );
}


function crm_v3_comunicados_leer_noticias() {

    $lista   = array();
    $errores = array();

    foreach (crm_v3_comunicados_busquedas() as $busqueda) {

        $respuesta = wp_remote_get(
            crm_v3_comunicados_url_busqueda($busqueda),
            array(
                'timeout'    => 20,
                'user-agent' => 'Mozilla/5.0 (compatible; CRM-CIBR)',
            )
        );

        if (is_wp_error($respuesta)) {
            $errores[] = $respuesta->get_error_message();
            continue;
        }

        $codigo = (int) wp_remote_retrieve_response_code($respuesta);

        if ($codigo !== 200) {
            $errores[] = 'código ' . $codigo;
            continue;
        }

        $resultado = crm_v3_comunicados_leer_rss(
            wp_remote_retrieve_body($respuesta)
        );

        if (is_wp_error($resultado)) {
            $errores[] = $resultado->get_error_message();
            continue;
        }

        // Solo se aceptan fuentes oficiales y medios de la lista.
        foreach ($resultado as $noticia) {

            if (crm_v3_comunicados_es_medio_confiable($noticia['medio_url'])) {
                $lista[] = $noticia;
            }
        }
    }

    // Solo es un error si no respondió ninguna búsqueda.
    if (!empty($errores) && count($errores) === count(crm_v3_comunicados_busquedas())) {
        return new WP_Error(
            'noticias',
            'No se pudieron consultar las noticias: ' . $errores[0]
        );
    }

    return $lista;
}


/**
 * Leer una lista RSS. La relevancia se decide después, al guardar.
 */
function crm_v3_comunicados_leer_rss($xml) {

    if (!function_exists('simplexml_load_string') || trim((string) $xml) === '') {
        return new WP_Error('rss', 'La respuesta de noticias llegó vacía.');
    }

    $anterior = libxml_use_internal_errors(true);
    $rss      = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($anterior);

    if (!$rss || !isset($rss->channel)) {
        return new WP_Error('rss', 'La respuesta de noticias no se pudo leer.');
    }

    $lista = array();

    foreach ($rss->channel->item as $item) {

        $titulo = trim((string) $item->title);
        $medio  = trim((string) $item->source);

        // Los títulos llegan como "Título - Nombre del medio".
        if ($medio !== '' && substr($titulo, -strlen(' - ' . $medio)) === ' - ' . $medio) {
            $titulo = substr($titulo, 0, -strlen(' - ' . $medio));
        }

        $lista[] = array(
            'titulo'    => $titulo,
            'fecha'     => (string) $item->pubDate,
            'url'       => (string) $item->link,
            'medio'     => $medio,
            'medio_url' => isset($item->source['url']) ? (string) $item->source['url'] : '',
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
    $fuentes   = crm_v3_comunicados_fuentes();

    foreach (crm_v3_comunicados_lista() as $comunicado) {

        // Lo guardado que hoy ya no pasa los filtros se descarta:
        // así, al ajustar las listas de palabras, la tarjeta se limpia sola.
        if (
            !empty($comunicado['id']) &&
            isset($fuentes[$comunicado['fuente']]) &&
            // Las noticias guardadas antes de la lista de medios se descartan.
            ($comunicado['fuente'] !== 'noticias' || !empty($comunicado['medio'])) &&
            !crm_v3_comunicados_es_ruido($comunicado['titulo']) &&
            crm_v3_comunicados_es_relevante(
                $comunicado['titulo'],
                isset($comunicado['extracto']) ? $comunicado['extracto'] : ''
            )
        ) {
            $guardados[$comunicado['id']] = $comunicado;
        }
    }

    // En la primera revisión todo es "nuevo"; no tiene caso resaltarlo.
    $primera_vez  = empty($guardados);
    $ids_antes    = array_fill_keys(array_keys($guardados), true);

    $estado = array(
        'revisado' => time(),
        'nuevos'   => 0,
        'fuentes'  => array(),
    );

    foreach ($fuentes as $clave => $fuente) {

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
        $vistos      = array();

        foreach ((array) $resultado as $item) {

            $comunicado = crm_v3_comunicados_normalizar($item, $clave);

            if (!$comunicado) {
                continue;
            }

            // Una misma noticia puede salir en varias búsquedas.
            if (isset($vistos[$comunicado['id']])) {
                continue;
            }

            $vistos[$comunicado['id']] = true;
            $encontrados++;

            // Ya estaba: se actualizan sus datos, pero conserva
            // el día en que se detectó.
            if (isset($guardados[$comunicado['id']])) {

                $comunicado['detectado'] = $guardados[$comunicado['id']]['detectado'];

                if (!empty($guardados[$comunicado['id']]['inicial'])) {
                    $comunicado['inicial'] = true;
                }

                $guardados[$comunicado['id']] = $comunicado;

                continue;
            }

            if ($primera_vez) {
                $comunicado['inicial'] = true;
            }

            $guardados[$comunicado['id']] = $comunicado;
        }

        // Fuente con lista completa: lo que ya no trae, se quita.
        if (!empty($fuente['completa'])) {

            foreach ($guardados as $id => $guardado) {

                if ($guardado['fuente'] === $clave && !isset($vistos[$id])) {
                    unset($guardados[$id]);
                }
            }
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

        if ($por_fuente[$clave] > 40) {
            continue;
        }

        // Si ya hay uno más reciente que dice lo mismo, este se omite.
        $repetido = false;

        foreach ($lista as $aceptado) {

            if (crm_v3_comunicados_son_parecidos($aceptado['titulo'], $comunicado['titulo'])) {
                $repetido = true;
                break;
            }
        }

        if ($repetido) {
            $por_fuente[$clave]--;
            continue;
        }

        $lista[] = $comunicado;

        if (!isset($ids_antes[$comunicado['id']])) {
            $estado['nuevos']++;
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

                    // En las noticias se muestra el nombre del medio.
                    if (!empty($comunicado['medio'])) {
                        $fuente = $comunicado['medio'];
                    }

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

                        <?php
                        // Los boletines de Infonavit no tienen página propia:
                        // se abren dentro del CRM con su texto completo.
                        $enlace = !empty($comunicado['cuerpo'])
                            ? admin_url('admin.php?page=crm-comunicado&id=' . $comunicado['id'])
                            : $comunicado['url'];
                        ?>

                        <?php if (!empty($enlace)) : ?>

                            <a
                                class="crm-bo-comunicado-titulo"
                                href="<?php echo esc_url($enlace); ?>"
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


/**
 * ============================================================
 * DIAGNÓSTICO (temporal)
 * ============================================================
 *
 * Descarga en un archivo de texto la respuesta completa del portal
 * de Infonavit, para poder ajustar la lectura de títulos y enlaces.
 *
 * Solo administradores:
 * wp-admin/admin-post.php?action=crm_v3_comunicados_diagnostico
 */

function crm_v3_comunicados_diagnostico() {

    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para esta acción.');
    }

    $resultado = function_exists('crm_infonavit_gwt_leer_comunicados')
        ? crm_infonavit_gwt_leer_comunicados()
        : array('ok' => false, 'error' => 'Lector no disponible.');

    $lineas = array(
        'DIAGNÓSTICO COMUNICADOS INFONAVIT',
        'Fecha: ' . wp_date('Y-m-d H:i:s'),
        'ok: ' . (!empty($resultado['ok']) ? 'sí' : 'no'),
        'http: ' . (isset($resultado['http']) ? $resultado['http'] : ''),
        'error: ' . (isset($resultado['error']) ? $resultado['error'] : ''),
        '',
        '===== COMUNICADOS RECONOCIDOS =====',
    );

    $comunicados = !empty($resultado['comunicados'])
        ? $resultado['comunicados']
        : array();

    foreach ($comunicados as $comunicado) {

        $lineas[] = sprintf(
            '[string %s] codigo=%s | fecha=%s | titulo=%s | titulo_oficial=%s | url=%s',
            isset($comunicado['indice']) ? $comunicado['indice'] : '',
            isset($comunicado['codigo']) ? $comunicado['codigo'] : '',
            isset($comunicado['fecha']) ? $comunicado['fecha'] : '',
            isset($comunicado['titulo']) ? $comunicado['titulo'] : '',
            isset($comunicado['titulo_oficial']) ? $comunicado['titulo_oficial'] : '',
            isset($comunicado['url']) ? $comunicado['url'] : ''
        );
    }

    $lineas[] = '';
    $lineas[] = '===== TODOS LOS TEXTOS DE LA RESPUESTA =====';

    $strings = !empty($resultado['debug_strings'])
        ? $resultado['debug_strings']
        : array();

    foreach ($strings as $indice => $string) {

        $lineas[] = '';
        $lineas[] = '--- #' . $indice . ' ---';
        $lineas[] = stripcslashes($string);
    }

    nocache_headers();
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="diagnostico-infonavit.txt"');

    echo implode("\n", $lineas);

    exit;
}

add_action(
    'admin_post_crm_v3_comunicados_diagnostico',
    'crm_v3_comunicados_diagnostico'
);



/**
 * ============================================================
 * PÁGINA DE UN COMUNICADO
 * ============================================================
 *
 * Muestra el texto completo de un boletín de Infonavit.
 */

function crm_v3_comunicado_registrar_pagina() {

    add_submenu_page(
        '',
        'Comunicado',
        'Comunicado',
        'manage_options',
        'crm-comunicado',
        'crm_v3_comunicado_page'
    );
}

add_action('admin_menu', 'crm_v3_comunicado_registrar_pagina', 20);


function crm_v3_comunicado_titulo_pagina() {

    global $title;

    $title = 'Comunicado';
}

add_action('load-admin_page_crm-comunicado', 'crm_v3_comunicado_titulo_pagina');


function crm_v3_comunicado_page() {

    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para acceder a esta sección.');
    }

    $id = isset($_GET['id'])
        ? sanitize_key($_GET['id'])
        : '';

    $comunicado = null;

    foreach (crm_v3_comunicados_lista() as $item) {

        if ($item['id'] === $id) {
            $comunicado = $item;
            break;
        }
    }

    $fuentes = crm_v3_comunicados_fuentes();

    ?>

    <div class="wrap crm-comunicado-wrap">

        <?php if (!$comunicado) : ?>

            <h1>Comunicado</h1>

            <p>Este comunicado ya no está en la lista.</p>

        <?php else : ?>

            <p class="crm-comunicado-meta">
                <?php echo esc_html(
                    isset($fuentes[$comunicado['fuente']])
                        ? $fuentes[$comunicado['fuente']]['nombre']
                        : $comunicado['fuente']
                ); ?>
                <?php if (!empty($comunicado['fecha'])) : ?>
                    · <?php echo esc_html(crm_v3_format_date($comunicado['fecha'])); ?>
                <?php endif; ?>
            </p>

            <h1><?php echo esc_html($comunicado['titulo']); ?></h1>

            <?php if (!empty($comunicado['extracto'])) : ?>
                <p class="crm-comunicado-extracto">
                    <?php echo esc_html($comunicado['extracto']); ?>
                </p>
            <?php endif; ?>

            <div class="crm-comunicado-cuerpo">
                <?php
                $cuerpo = !empty($comunicado['cuerpo']) ? $comunicado['cuerpo'] : '';

                foreach (preg_split('/\n\s*\n/', $cuerpo) as $parrafo) {

                    if (trim($parrafo) !== '') {
                        echo '<p>' . make_clickable(esc_html(trim($parrafo))) . '</p>';
                    }
                }
                ?>
            </div>

            <?php if (!empty($comunicado['url'])) : ?>
                <p>
                    <a
                        class="button"
                        href="<?php echo esc_url($comunicado['url']); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >Ver en la página de origen</a>
                </p>
            <?php endif; ?>

        <?php endif; ?>

        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=crm-v3-cibr#crm-comunicados')); ?>">
                ← Regresar al Backoffice
            </a>
        </p>

    </div>

    <style>
        .crm-comunicado-wrap { max-width: 820px; }
        .crm-comunicado-wrap h1 { font-size: 22px; line-height: 1.3; margin: 4px 0 14px; }
        .crm-comunicado-meta { margin: 14px 0 0; color: #7b8794; font-size: 12px; }
        .crm-comunicado-extracto { padding: 10px 14px; border-left: 3px solid #6f9baa; background: #fff; color: #263238; font-size: 14px; line-height: 1.6; }
        .crm-comunicado-cuerpo { margin: 16px 0; padding: 6px 20px; background: #fff; border: 1px solid #e3e8ee; border-radius: 10px; }
        .crm-comunicado-cuerpo p { font-size: 14px; line-height: 1.7; color: #263238; }
    </style>

    <?php
}
