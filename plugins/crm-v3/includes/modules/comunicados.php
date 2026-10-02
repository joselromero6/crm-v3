<?php

/**
 * ============================================================
 * CIBR CRM
 * LECTOR GWT — COMUNICADOS INFONAVIT
 * ============================================================
 *
 * BASE FUNCIONAL DE RESPALDO
 *
 * Esta versión únicamente:
 *
 * 1. Consulta ConsultNewsService de Infonavit.
 * 2. Recibe la respuesta GWT-RPC.
 * 3. Extrae los strings.
 * 4. Detecta los bloques HTML de las publicaciones.
 * 5. Extrae título y fecha básica.
 *
 * NO guarda en base de datos.
 * NO envía emails.
 * NO modifica Backoffice.
 * NO genera registros.
 *
 * Punto de partida para el parser definitivo.
 * ============================================================
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * ============================================================
 * LECTOR GWT INFONAVIT
 * ============================================================
 */

function crm_infonavit_gwt_leer_comunicados() {

    /**
     * --------------------------------------------------------
     * ENDPOINT
     * --------------------------------------------------------
     */

    $endpoint =
        'https://portalmx.infonavit.org.mx/wps/PA_ConsultaNoticias/consnews/ConsultNewsService';


    /**
     * --------------------------------------------------------
     * PAYLOAD GWT
     * --------------------------------------------------------
     *
     * Este es el payload que ya comprobamos
     * que devuelve las publicaciones reales.
     * --------------------------------------------------------
     */

    $payload =
        '7|0|8|https://portalmx.infonavit.org.mx/wps/PA_ConsultaNoticias/consnews/|C3F84E9569601788ED503DA541E91589|mx.org.infonavit.consultnews.client.ConsultNewsService|getNews|I|java.util.Date/3385151746|java.lang.String/2004016611|desc|1|2|3|4|6|5|5|5|6|6|7|17|0|-1|0|0|8|';


    /**
     * --------------------------------------------------------
     * HEADERS
     * --------------------------------------------------------
     */

    $headers = array(

        'Accept: */*',

        'Content-Type: text/x-gwt-rpc; charset=UTF-8',

        'Origin: https://portalmx.infonavit.org.mx',

        'Referer: https://portalmx.infonavit.org.mx/wps/portal/infonavitmx/mx2/el-instituto/el-infonavit/sala_prensa/',

        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/150.0.0.0 Safari/537.36',

        'X-GWT-Module-Base: https://portalmx.infonavit.org.mx/wps/PA_ConsultaNoticias/consnews/',

        'X-GWT-Permutation: 1318A09CA8FC9014D138F06C592A9338',

    );


    /**
     * --------------------------------------------------------
     * CURL
     * --------------------------------------------------------
     */

    $ch = curl_init(
        $endpoint
    );


    curl_setopt_array(
        $ch,

        array(

            CURLOPT_POST =>
                true,

            CURLOPT_POSTFIELDS =>
                $payload,

            CURLOPT_HTTPHEADER =>
                $headers,

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_CONNECTTIMEOUT =>
                10,

            CURLOPT_TIMEOUT =>
                30,

            CURLOPT_HTTP_VERSION =>
                CURL_HTTP_VERSION_1_1,

            CURLOPT_ENCODING =>
                '',

        )
    );


    $respuesta =
        curl_exec($ch);


    $error =
        curl_error($ch);


    $http =
        (int) curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    curl_close($ch);


    /**
     * --------------------------------------------------------
     * VALIDAR RESPUESTA
     * --------------------------------------------------------
     */

    if (
        $error ||
        $http !== 200 ||
        !$respuesta
    ) {

        return array(

            'ok' =>
                false,

            'error' =>
                $error
                    ?: 'Respuesta HTTP inválida.',

            'http' =>
                $http,

            'strings' =>
                0,

            'comunicados' =>
                array(),

        );
    }


    /**
     * --------------------------------------------------------
     * QUITAR CABECERA //OK
     * --------------------------------------------------------
     */

    $respuesta_limpia =
        preg_replace(

            '/^\s*\/\/OK\s*/',

            '',

            $respuesta,

            1

        );


    /**
     * --------------------------------------------------------
     * EXTRAER STRINGS GWT
     * --------------------------------------------------------
     */

    preg_match_all(

        '/"((?:\\\\.|[^"\\\\])*)"/s',

        $respuesta_limpia,

        $coincidencias

    );


    $strings =
        $coincidencias[1]
            ?? array();


    /**
     * --------------------------------------------------------
     * DETECTAR PUBLICACIONES
     * --------------------------------------------------------
     *
     * Las publicaciones reales aparecen como strings
     * que contienen bloques HTML grandes.
     * --------------------------------------------------------
     */

        $comunicados =
        array();


    foreach (
        $strings
        as $indice => $valor
    ) {

        $html =
            stripcslashes(
                $valor
            );

        /**
         * ----------------------------------------------------
         * IDENTIFICAR EL HTML REAL DEL COMUNICADO
         * ----------------------------------------------------
         *
         * Los comunicados reales contienen HTML y la palabra
         * INFONAVIT. Los carruseles de imágenes también contienen
         * HTML, pero no contienen el texto editorial.
         */

        if (
    stripos($html, '<div') === false ||
    stripos($html, 'INFONAVIT') === false ||
    stripos($html, 'carousel slide') === false
) {

    continue;
}


        /**
         * Evitar fragmentos pequeños.
         */

        if (
            strlen($html) <= 300
        ) {

            continue;
        }


        /**
         * Obtener texto limpio.
         */

       $texto =
    html_entity_decode(
        strip_tags($html),
        ENT_QUOTES |
        ENT_HTML5,
        'UTF-8'
    );

$texto =
    preg_replace(
        '/\s+/u',
        ' ',
        $texto
    );

$texto =
    trim(
        $texto
    );


        if (
            mb_strlen($texto) < 80
        ) {

            continue;
        }


                /**
         * ----------------------------------------------------
         * RESUMEN
         * ----------------------------------------------------
         *
         * Se conserva aproximadamente una muestra de
         * 100 palabras del contenido.
         *
         * No se utilizará como título.
         * ----------------------------------------------------
         */

        $resumen =
            wp_trim_words(
                $texto,
                100,
                '...'
            );


/**
 * ----------------------------------------------------
 * URL
 * ----------------------------------------------------
 *
 * NO se captura todavía.
 *
 * Las URLs que aparecen como strings independientes
 * de GWT no están necesariamente asociadas al
 * comunicado actual.
 *
 * Se deja vacío hasta identificar una relación
 * inequívoca con el comunicado.
 * ----------------------------------------------------
 */

$url = '';


/**
 * ----------------------------------------------------
 * TÍTULO
 * ----------------------------------------------------
 *
 * El título puede venir:
 *
 * 1. Dentro del HTML.
 * 2. Como string independiente de GWT.
 * 3. En una posición cercana al comunicado.
 * 4. Como primera línea editorial del contenido.
 *
 * IMPORTANTE:
 *
 * - Nunca utilizar URL.
 * - Nunca utilizar Previous / Next.
 * - Nunca utilizar la fecha.
 * - Nunca utilizar textos técnicos de GWT.
 * - Nunca utilizar el contenido completo como título.
 * ----------------------------------------------------
 */

$titulo = '';


/**
 * ----------------------------------------------------
 * CÓDIGO OFICIAL DEL COMUNICADO
 * ----------------------------------------------------
 *
 * En la respuesta GWT actual, el código aparece
 * inmediatamente después del HTML del comunicado
 * y tiene exactamente 3 dígitos.
 *
 * Ejemplo comprobado:
 *
 * String #4 → contenido
 * String #5 → 045
 *
 * No se buscan códigos en posiciones lejanas.
 * ----------------------------------------------------
 */

$codigo = '';

$indice_siguiente =
    $indice + 1;

if (
    isset(
        $strings[$indice_siguiente]
    )
) {

    $candidato_codigo =
        trim(
            stripcslashes(
                $strings[$indice_siguiente]
            )
        );

    if (
        preg_match(
            '/^\d{3}$/',
            $candidato_codigo
        )
    ) {

        $codigo =
            $candidato_codigo;
    }
}


/**
 * ----------------------------------------------------
 * TÍTULO OFICIAL DEL STRING-RESUMEN
 * ----------------------------------------------------
 *
 * Se revisa solamente el string inmediatamente posterior
 * al código oficial.
 *
 * No se buscan títulos en posiciones lejanas.
 * ----------------------------------------------------
 */

$titulo_oficial =
    '';

$indice_resumen =
    $indice + 2;

if (
    isset(
        $strings[$indice_resumen]
    )
) {

    $html_resumen =
        stripcslashes(
            $strings[$indice_resumen]
        );

    if (
        preg_match(
            '/<(?:strong|b)[^>]*>\s*(.*?)\s*<\/(?:strong|b)>/is',
            $html_resumen,
            $match_titulo_oficial
        )
    ) {

        $titulo_oficial =
            trim(
                html_entity_decode(
                    strip_tags(
                        $match_titulo_oficial[1]
                    ),
                    ENT_QUOTES |
                    ENT_HTML5,
                    'UTF-8'
                )
            );

        $titulo_oficial =
            trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    $titulo_oficial
                )
            );

        if (
            stripos(
                $titulo_oficial,
                'INFONAVIT'
            ) === false
            ||
            mb_strlen(
                $titulo_oficial
            ) < 20
        ) {

            $titulo_oficial =
                '';
        }
    }
}


/**
 * ----------------------------------------------------
 * MÉTODO 1–3
 * TÍTULO OFICIAL / ASOCIACIÓN GWT
 * ----------------------------------------------------
 *
 * El título oficial puede venir:
 * 1. Como un string independiente.
 * 2. Como el encabezado de un string-resumen.
 * 3. Dentro del HTML mediante una etiqueta <strong>.
 *
 * NO se usan posiciones fijas como único criterio.
 * Cuando el string cercano contiene título + resumen, se extrae
 * únicamente el encabezado antes del texto descriptivo.
 * ----------------------------------------------------
 */

$titulo = '';


/**
 * ----------------------------------------------------
 * MÉTODO 1
 * TÍTULO DENTRO DEL HTML
 * ----------------------------------------------------
 */

if (
    preg_match_all(
        '/<(?:strong|b)[^>]*>\\s*(.*?)\\s*<\\/(?:strong|b)>/is',
        $html,
        $matches_titulos_html
    )
) {

    foreach (
        $matches_titulos_html[1] as $titulo_html
    ) {

        $candidato =
            trim(
                html_entity_decode(
                    strip_tags($titulo_html),
                    ENT_QUOTES |
                    ENT_HTML5,
                    'UTF-8'
                )
            );

        $candidato =
            trim(
                preg_replace(
                    '/\\s+/u',
                    ' ',
                    $candidato
                )
            );

        if (
            stripos($candidato, 'INFONAVIT') !== false &&
            mb_strlen($candidato) >= 20 &&
            mb_strlen($candidato) <= 180 &&
            !preg_match('~https?://~i', $candidato) &&
            !preg_match('/\\bPrevious\\b|\\bNext\\b/i', $candidato)
        ) {

            $titulo =
                $candidato;

            break;
        }
    }
}


/**
 * ----------------------------------------------------
 * MÉTODOS 2 Y 3 — DESACTIVADOS
 * ----------------------------------------------------
 *
 * IMPORTANTE:
 *
 * No buscar títulos en posiciones vecinas del array
 * GWT (+1, +2, +3).
 *
 * Esos strings pueden pertenecer a otro comunicado.
 *
 * El título solamente puede venir:
 *
 * 1. Del HTML del comunicado actual.
 * 2. Del contenido del comunicado actual.
 *
 * Si no existe una relación inequívoca,
 * se deja vacío para que actúe el respaldo
 * específico correspondiente.
 * ----------------------------------------------------
 */


/**
 * ----------------------------------------------------
 * RESPALDO ESPECÍFICO — ENTREGA DE VIVIENDAS
 * ----------------------------------------------------
 *
 * Para comunicados donde GWT no entrega el título
 * como string independiente.
 * ----------------------------------------------------
 */

if (
    !$titulo &&
    preg_match(
        '/entregaron\s+(?:las\s+)?(\d[\d\s]*)\s+Viviendas del Bienestar\s+del fraccionamiento\s+[“"](.+?)[”"]/iu',
        $texto,
        $match_entrega
    )
) {

    $cantidad =
        trim(
            $match_entrega[1]
        );

    $fraccionamiento =
        trim(
            $match_entrega[2]
        );

    $titulo =
        'INFONAVIT ENTREGA ' .
        $cantidad .
        ' VIVIENDAS DEL BIENESTAR DEL FRACCIONAMIENTO “' .
        $fraccionamiento .
        '”';
}


/**
 * ----------------------------------------------------
 * VALIDACIÓN FINAL
 * ----------------------------------------------------
 */

if (
    $titulo &&
    (
        stripos($titulo, 'INFONAVIT') === false ||
        preg_match('~https?://~i', $titulo) ||
        preg_match('/\\bPrevious\\b|\\bNext\\b/i', $titulo)
    )
) {

    $titulo = '';
}


/**
 * ----------------------------------------------------
 * MÉTODO 4
 * RESPALDO DESDE EL CONTENIDO
 * ----------------------------------------------------
 *
 * Algunos comunicados no entregan el título
 * como string independiente.
 *
 * En esos casos se toma solamente la primera
 * frase que comienza con INFONAVIT.
 * ----------------------------------------------------
 */

if (
    false
) {

    $texto_titulo =
        trim(
            $texto
        );


    /**
     * Quitar Previous / Next.
     */

    $texto_titulo =
        preg_replace(
            '/^\s*(Previous\s+Next)\s*/i',
            '',
            $texto_titulo
        );


    $texto_titulo =
        trim(
            $texto_titulo
        );


    /**
     * Si comienza directamente con INFONAVIT,
     * intentar separar el encabezado del contenido.
     */

    if (
        stripos(
            $texto_titulo,
            'INFONAVIT'
        ) === 0
    ) {

        $candidato =
            $texto_titulo;


        /**
         * Separadores frecuentes entre título
         * y contenido editorial.
         */

        $candidato =
            preg_replace(
                '/\s+(La meta|A nivel|El Instituto|Se informó|Se informo|Con una|Para ello|En este|Durante|Se tiene|Se tienen|La construcción|La entrega)\b.*$/iu',
                '',
                $candidato
            );


        $candidato =
            trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    $candidato
                )
            );


        if (
            mb_strlen($candidato) >= 20 &&
            mb_strlen($candidato) <= 180
        ) {

            $titulo =
                $candidato;
        }
    }
}


/**
 * ----------------------------------------------------
 * MÉTODO 5
 * LIMPIAR TÍTULO
 * ----------------------------------------------------
 */

if (
    false
) {

    /**
     * Nunca permitir Previous / Next.
     */

    if (
        preg_match(
            '/\bPrevious\b|\bNext\b/i',
            $titulo
        )
    ) {

        $titulo = '';
    }


    /**
     * Nunca permitir URL.
     */

    if (
        preg_match(
            '~https?://~i',
            $titulo
        )
    ) {

        $titulo = '';
    }


    /**
     * Nunca permitir textos técnicos.
     */

    if (
        preg_match(
            '/(?:mx\.org\.infonavit|NewsModel|client\.model|java\.lang|java\.util|\/\d+$)/i',
            $titulo
        )
    ) {

        $titulo = '';
    }


    /**
     * Limpieza final.
     */

    $titulo =
        trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $titulo
            )
        );
}


        /**
         * ----------------------------------------------------
         * FECHA
         * ----------------------------------------------------
         *
         * Primero se busca en los strings cercanos porque GWT
         * entrega la fecha como string independiente.
         */

        $fecha = '';


        $inicio =
            max(
                0,
                $indice - 3
            );


        $fin =
            min(
                count($strings) - 1,
                $indice + 3
            );


        for (
            $j = $inicio;
            $j <= $fin;
            $j++
        ) {

            if (
                $j === $indice
            ) {

                continue;
            }


            $candidato =
                trim(
                    stripcslashes(
                        $strings[$j]
                    )
                );


            if (
                preg_match(
                    '/^\d{1,2}\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\s+de\s+\d{4}$/iu',
                    $candidato
                )
            ) {

                $fecha =
                    $candidato;

                break;
            }
        }


        /**
         * Respaldo: si la fecha no vino como string separado,
         * obtenerla del propio contenido.
         */

        if (
            !$fecha &&
            preg_match(
                '/\b\d{1,2}\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\s+de\s+\d{4}\b/iu',
                $texto,
                $match_fecha
            )
        ) {

            $fecha =
                $match_fecha[0];
        }


/**
 * ----------------------------------------------------
 * RESPALDO DEL TÍTULO DESDE EL TEXTO
 * ----------------------------------------------------
 *
 * Solo aceptar como título un texto que realmente
 * comience con INFONAVIT.
 *
 * No utilizar el contenido completo del comunicado
 * como título.
 * ----------------------------------------------------
 */

if (
    !$titulo
) {

    if (
        preg_match(
            '/^(INFONAVIT\b[^.!?]{20,180})/iu',
            $texto,
            $match_titulo_texto
        )
    ) {

        $titulo =
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $match_titulo_texto[1]
                )
            );
    }
}


        /**
         * ----------------------------------------------------
         * GUARDAR PUBLICACIÓN EN MEMORIA
         * ----------------------------------------------------
         */

        $comunicados[] =
            array(

                'indice' =>
                    $indice,

                'codigo' =>
                    $codigo,

                'titulo_oficial' =>
                    $titulo_oficial,

                'titulo' =>
                    $titulo,

                'fecha' =>
                    $fecha,

                'html' =>
                    $html,

                'texto' =>
                    $texto,

                'resumen' =>
                    $resumen,


                /* DIAGNÓSTICO TEMPORAL: strings alrededor del comunicado */
                'vecinos' =>
                    array_slice(
                        $strings,
                        max(0, $indice - 3),
                        7,
                        true
                    ),

                'url' =>
                    $url,
            );
    }


/**
 * ============================================================
 * RESPALDO FINAL — TÍTULO DESDE EL PROPIO COMUNICADO
 * ============================================================
 *
 * IMPORTANTE:
 *
 * NO relacionar un comunicado con otro.
 * NO copiar títulos de registros vecinos.
 *
 * Si GWT no entregó el título del comunicado actual,
 * solamente se intenta obtenerlo del propio contenido.
 *
 * ============================================================
 */

foreach (
    $comunicados as &$comunicado_sin_titulo
) {

    if (
        !empty(
            trim(
                $comunicado_sin_titulo['titulo']
            )
        )
    ) {
        continue;
    }


    $texto_titulo =
        trim(
            $comunicado_sin_titulo['texto']
        );


    /**
     * Quitar Previous / Next del comienzo.
     */
    $texto_titulo =
        preg_replace(
            '/^\s*(Previous\s+Next)\s*/i',
            '',
            $texto_titulo
        );


    $texto_titulo =
        trim(
            $texto_titulo
        );


    /**
     * --------------------------------------------------------
     * BUSCAR UNA FRASE QUE COMIENCE CON INFONAVIT
     * --------------------------------------------------------
     *
     * No se utiliza otro comunicado.
     * Solamente el texto del registro actual.
     *
     * Se corta antes de los separadores editoriales
     * conocidos.
     * --------------------------------------------------------
     */

    if (
        preg_match(
            '/\b(INFONAVIT\b.{20,180}?)(?=\s+(?:La meta|La construcción|Se informó|Se informo|A nivel nacional|El director|El ingeniero|Enlace|Durante|Esta es|Este desarrollo|La entidad|La inversión)\b|[.!?]\s|$)/iu',
            $texto_titulo,
            $match_titulo
        )
    ) {

        $titulo_local =
            trim(
                $match_titulo[1]
            );


        $titulo_local =
            preg_replace(
                '/\s+/u',
                ' ',
                $titulo_local
            );


        /**
         * Limpiar ubicación + fecha si quedaron al final.
         */
        $titulo_local =
            preg_replace(
                '/\s+(?:[A-ZÁÉÍÓÚÑ][^,]{2,40},\s*)?(?:a\s+)?\d{1,2}\s+de\s+(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\s+de\s+\d{4}\.?$/iu',
                '',
                $titulo_local
            );


        $titulo_local =
            trim(
                $titulo_local
            );


        /**
         * Validación estricta.
         */
        if (
            mb_strlen($titulo_local) >= 20 &&
            mb_strlen($titulo_local) <= 180 &&
            stripos($titulo_local, 'INFONAVIT') === 0 &&
            !preg_match('~https?://~i', $titulo_local) &&
            !preg_match('/\bPrevious\b|\bNext\b/i', $titulo_local)
        ) {

            $comunicado_sin_titulo['titulo'] =
                $titulo_local;
        }
    }
}


unset(
    $comunicado_sin_titulo
);


    /**
     * --------------------------------------------------------
     * RESULTADO
     * --------------------------------------------------------
     */

    return array(
    'ok' =>
        true,

    'http' =>
        $http,

    'strings' =>
        count($strings),

    'comunicados' =>
        $comunicados,

    'debug_strings' =>
        $strings,

    'error' =>
        '',
);
}


/**
 * ============================================================
 * PRUEBA MANUAL
 * ============================================================
 *
 * URL:
 *
 * ?crm_capturar_infonavit_gwt=1
 *
 * ============================================================
 */

function crm_infonavit_gwt_prueba_base() {

    if (
        !is_admin()
    ) {

        return;
    }


    if (
        !current_user_can(
            'manage_options'
        )
    ) {

        return;
    }


    if (
        !isset(
            $_GET['crm_capturar_infonavit_gwt']
        )
        ||
        $_GET['crm_capturar_infonavit_gwt'] !== '1'
    ) {

        return;
    }


    /**
     * --------------------------------------------------------
     * EJECUTAR LECTOR
     * --------------------------------------------------------
     */

    $resultado =
        crm_infonavit_gwt_leer_comunicados();


    /**
     * --------------------------------------------------------
     * ERROR
     * --------------------------------------------------------
     */

    if (
        empty(
            $resultado['ok']
        )
    ) {

        wp_die(

            '<h2>Error GWT Infonavit</h2>' .

            '<p><strong>HTTP:</strong> ' .
            esc_html(
                $resultado['http']
                    ?? 0
            ) .
            '</p>' .

            '<p><strong>Error:</strong> ' .
            esc_html(
                $resultado['error']
                    ?? ''
            ) .
            '</p>'

        );
    }


    /**
     * --------------------------------------------------------
     * MOSTRAR RESULTADO
     * --------------------------------------------------------
     */

    echo
        '<div style="
            max-width:1100px;
            margin:40px auto;
            font-family:Arial,sans-serif;
        ">';


    echo
        '<h1>
            Lector GWT Infonavit
        </h1>';


    echo
        '<p>
            <strong>HTTP:</strong> ' .
        esc_html(
            $resultado['http']
        ) .
        '</p>';


    echo
        '<p>
            <strong>Strings:</strong> ' .
        esc_html(
            $resultado['strings']
        ) .
        '</p>';


    echo
        '<p>
            <strong>Comunicados encontrados:</strong> ' .
        esc_html(
            count(
                $resultado['comunicados']
            )
        ) .
        '</p>';


    echo '<hr>';


    /**
     * --------------------------------------------------------
     * MOSTRAR PUBLICACIONES
     * --------------------------------------------------------
     */

    foreach (
        $resultado['comunicados']
        as $numero => $comunicado
    ) {

        echo
            '<div style="
                margin:20px 0;
                padding:20px;
                border:1px solid #ccd0d4;
                background:#fff;
            ">';


        echo
            '<h2 style="margin-top:0;">
                Comunicado #' .
            esc_html(
                $numero + 1
            ) .
            '</h2>';


        echo
            '<p>
                <strong>String:</strong> #' .
            esc_html(
                $comunicado['indice']
            ) .
            '</p>';

            echo
    '<p>
        <strong>Código oficial:</strong> ' .
    esc_html(
        $comunicado['codigo']
            ?: 'No identificado'
    ) .
    '</p>';

echo
    '<p>
        <strong>Título oficial:</strong><br>' .
    esc_html(
        $comunicado['titulo_oficial']
            ?: 'No identificado'
    ) .
    '</p>';


/**
 * ============================================================
 * DIAGNÓSTICO TEMPORAL DE STRINGS
 * ============================================================
 */

if (
    isset($_GET['crm_debug_titulos']) &&
    $_GET['crm_debug_titulos'] === '1'
) {

    $debug_strings =
        $resultado['debug_strings']
        ?? array();

    $debug_indice =
        (int) $comunicado['indice'];

    $debug_inicio =
        max(
            0,
            $debug_indice - 8
        );

    $debug_fin =
        min(
            count($debug_strings) - 1,
            $debug_indice + 8
        );

    echo
        '<div style="
            margin:15px 0;
            padding:15px;
            background:#fff8e5;
            border:1px solid #dba617;
            font-family:monospace;
            font-size:12px;
            line-height:1.5;
        ">';

    echo
        '<strong>
            DIAGNÓSTICO — STRINGS ALREDEDOR DEL #'
        .
        esc_html(
            $debug_indice
        )
        .
        '</strong>';

    echo '<hr style="margin:10px 0;">';

    for (
        $d = $debug_inicio;
        $d <= $debug_fin;
        $d++
    ) {

        $debug_texto =
            trim(
                stripcslashes(
                    $debug_strings[$d]
                )
            );

        if (
            $d === $debug_indice
        ) {

            echo
                '<div style="
                    margin:5px 0;
                    padding:6px;
                    background:#dff0d8;
                    border:1px solid #8fbc8f;
                ">
                    <strong>
                        >>> STRING #'
                    .
                    esc_html($d)
                    .
                    ' &lt;=== COMUNICADO
                    </strong><br>'
                    .
                    esc_html(
                        $debug_texto
                    )
                    .
                '</div>';

        } else {

            echo
                '<div style="
                    margin:4px 0;
                ">
                    #'
                    .
                    esc_html($d)
                    .
                    ' → '
                    .
                    esc_html(
                        $debug_texto
                    )
                    .
                '</div>';
        }
    }

    echo '</div>';
}


        /* DIAGNÓSTICO TEMPORAL: mostrar strings vecinos solo en los primeros 10 */
        if ($numero < 10 && !empty($comunicado['vecinos'])) {

            echo '<div style="margin:12px 0;padding:12px;background:#fff8e5;border:1px solid #e0b849;">';
            echo '<strong>DIAGNÓSTICO — strings alrededor:</strong>';

            foreach ($comunicado['vecinos'] as $numero_string => $valor_string) {

                $valor_string_limpio = trim(
                    html_entity_decode(
                        stripcslashes($valor_string),
                        ENT_QUOTES | ENT_HTML5,
                        'UTF-8'
                    )
                );

                echo '<div style="margin-top:8px;padding:8px;background:#fff;border:1px solid #ddd;">';
                echo '<strong>String #' . esc_html($numero_string) . ':</strong><br>';
                echo esc_html($valor_string_limpio);
                echo '</div>';
            }

            echo '</div>';
        }

        echo
            '<p>
                <strong>Título:</strong><br>' .
            esc_html(
                $comunicado['titulo']
                    ?: 'No identificado'
            ) .
            '</p>';


        echo
            '<p>
                <strong>Fecha:</strong><br>' .
            esc_html(
                $comunicado['fecha']
                    ?: 'No identificada'
            ) .
            '</p>';


                    if (
            !empty(
                $comunicado['url']
            )
        ) {

            echo
                '<p>
                    <strong>URL original:</strong><br>
                    <a href="' .
                esc_url(
                    $comunicado['url']
                ) .
                '" target="_blank" rel="noopener">
                    ' .
                esc_html(
                    $comunicado['url']
                ) .
                '
                    </a>
                </p>';
        }

                if (
            !empty(
                trim(
                    $comunicado['texto']
                )
            )
        ) {

            echo
                '<p>
                    <strong>Contenido:</strong>
                </p>';


            echo
                '<div style="
                    padding:15px;
                    background:#f7f7f7;
                    border:1px solid #ddd;
                    white-space:pre-wrap;
                    line-height:1.6;
                ">' .
                esc_html(
                    $comunicado['texto']
                ) .
                '</div>';
        }


        echo
            '</div>';
    }


    echo
        '<p style="margin-top:30px;">
            <a href="' .
        esc_url(
            admin_url()
        ) .
        '">
            Regresar al escritorio
        </a>
        </p>';


    echo
        '</div>';


    exit;
}


add_action(
    'admin_init',
    'crm_infonavit_gwt_prueba_base'
);