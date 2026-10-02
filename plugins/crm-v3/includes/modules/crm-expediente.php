<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ============================================================
 * CRM V3 — EXPEDIENTE DE OPERACIÓN
 * ============================================================
 */

function crm_v3_expediente_page() {

    if (!current_user_can('manage_options')) {
        wp_die('Lo siento, no tienes permisos para acceder a esta página.');
    }

    $operacion_id = isset($_GET['operacion_id'])
        ? absint($_GET['operacion_id'])
        : 0;

    $cliente_id = isset($_GET['cliente_id'])
        ? absint($_GET['cliente_id'])
        : 0;

    if (!$operacion_id) {
        wp_die('No se encontró la operación.');
    }


    /*
     * ========================================================
     * OPERACIÓN
     * ========================================================
     */

    $operacion = get_post($operacion_id);

    if (
        !$operacion ||
        $operacion->post_type !== 'operaciones'
    ) {
        wp_die('La operación no existe.');
    }


    /*
     * ========================================================
     * RELACIONES
     * ========================================================
     */

    $comprador = get_field(
        'comprador',
        $operacion_id
    );

    $propiedad = get_field(
        'propiedad',
        $operacion_id
    );


    $comprador_id =
        crm_v3_get_related_id($comprador);

    $propiedad_id =
        crm_v3_get_related_id($propiedad);


    /*
     * VENDEDOR
     *
     * El vendedor sale del propietario
     * de la propiedad relacionada.
     */

    $vendedor = $propiedad_id
        ? get_field(
            'cliente_propietario',
            $propiedad_id
        )
        : null;

    $vendedor_id =
        crm_v3_get_related_id($vendedor);


    /*
     * ========================================================
     * DATOS VENDEDOR
     * ========================================================
     */

    $vendedor_nombre =
        $vendedor_id
            ? get_the_title($vendedor_id)
            : '—';

    $vendedor_direccion =
        $vendedor_id
            ? get_field(
                'domicilio',
                $vendedor_id
            )
            : '';

    $vendedor_origen =
        $vendedor_id
            ? get_field(
                'origen_recursos',
                $vendedor_id
            )
            : '';

    $vendedor_tipo_credito =
        $vendedor_id
            ? get_field(
                'tipo_credito',
                $vendedor_id
            )
            : '';

    $vendedor_adeudo =
        $vendedor_id
            ? get_field(
                'adeudo_hipoteca',
                $vendedor_id
            )
            : '';


    /*
     * ========================================================
     * DATOS PROPIEDAD
     * ========================================================
     */

    $propiedad_nombre =
        $propiedad_id
            ? get_the_title($propiedad_id)
            : '—';

    $propiedad_direccion =
        $propiedad_id
            ? get_field(
                'direccion',
                $propiedad_id
            )
            : '';

    $precio_venta =
        $propiedad_id
            ? get_field(
                'precio_de_venta',
                $propiedad_id
            )
            : '';

    $valor_catastral =
        $propiedad_id
            ? get_field(
                'valor_catastral',
                $propiedad_id
            )
            : '';


    /*
     * ========================================================
     * DATOS OPERACIÓN
     * ========================================================
     */

    $valor_mercado =
        get_field(
            'valor_mercado',
            $operacion_id
        );

    $tipo_avaluo =
        get_field(
            'tipo_de_avaluo',
            $operacion_id
        );

    $valuador =
        get_field(
            'valuador',
            $operacion_id
        );

    $valuador_id =
        crm_v3_get_related_id($valuador);


    /*
     * ========================================================
     * DATOS COMPRADOR
     * ========================================================
     */

    $comprador_nombre =
        $comprador_id
            ? get_the_title($comprador_id)
            : '—';

    $comprador_direccion =
        $comprador_id
            ? get_field(
                'domicilio',
                $comprador_id
            )
            : '';

    $comprador_origen =
        $comprador_id
            ? get_field(
                'origen_recursos',
                $comprador_id
            )
            : '';

    $comprador_tipo_credito =
        $comprador_id
            ? get_field(
                'tipo_credito',
                $comprador_id
            )
            : '';

    $comprador_presupuesto =
        $comprador_id
            ? get_field(
                'presupuesto',
                $comprador_id
            )
            : '';


    /*
     * ========================================================
     * DATOS PROPIOS DEL EXPEDIENTE
     * ========================================================
     */

    $expediente =
        get_post_meta(
            $operacion_id,
            '_crm_expediente',
            true
        );

    if (!is_array($expediente)) {
        $expediente = [];
    }


    $vendedor_documentos =
        isset($expediente['vendedor_documentos'])
            ? $expediente['vendedor_documentos']
            : [];

    $comprador_documentos =
        isset($expediente['comprador_documentos'])
            ? $expediente['comprador_documentos']
            : [];

    $vendedor_notas =
        isset($expediente['vendedor_notas'])
            ? $expediente['vendedor_notas']
            : '';

    $comprador_notas =
        isset($expediente['comprador_notas'])
            ? $expediente['comprador_notas']
            : '';

    $vendedor_manual =
        isset($expediente['vendedor_manual'])
            ? $expediente['vendedor_manual']
            : [];

    $comprador_manual =
        isset($expediente['comprador_manual'])
            ? $expediente['comprador_manual']
            : [];


   /*
 * ========================================================
 * DOCUMENTOS
 * ========================================================
 *
 * doble = permite marcar PRINCIPAL + CÓNYUGE
 * simple = solamente PRINCIPAL
 * ========================================================
 */

$documentos_vendedor = crm_v3_expediente_documentos('vendedor');

$documentos_comprador = crm_v3_expediente_documentos('comprador');


    /*
 * ========================================================
 * PORCENTAJES
 * ========================================================
 */

$vendedor_total = 0;
$vendedor_completos = 0;

foreach ($documentos_vendedor as $key => $documento) {

    // Los títulos de sección no son documentos.
    if (($documento['tipo'] ?? '') === 'titulo') {
        continue;
    }

    $vendedor_total++;

    $estado = $vendedor_documentos[$key] ?? [];

    if (!is_array($estado)) {
        $estado = [
            'principal' => !empty($estado) ? 1 : 0,
            'conyuge'   => 0,
        ];
    }

    if (!empty($estado['principal'])) {
        $vendedor_completos++;
    }

    if (!empty($documento['doble'])) {

        $vendedor_total++;

        if (!empty($estado['conyuge'])) {
            $vendedor_completos++;
        }
    }
}


$comprador_total = 0;
$comprador_completos = 0;

foreach ($documentos_comprador as $key => $documento) {

    // Los títulos de sección no son documentos.
    if (($documento['tipo'] ?? '') === 'titulo') {
        continue;
    }

    $comprador_total++;

    $estado = $comprador_documentos[$key] ?? [];

    if (!is_array($estado)) {
        $estado = [
            'principal' => !empty($estado) ? 1 : 0,
            'conyuge'   => 0,
        ];
    }

    if (!empty($estado['principal'])) {
        $comprador_completos++;
    }

    if (!empty($documento['doble'])) {

        $comprador_total++;

        if (!empty($estado['conyuge'])) {
            $comprador_completos++;
        }
    }
}


$porcentaje_vendedor =
    $vendedor_total
        ? round(
            ($vendedor_completos / $vendedor_total) * 100
        )
        : 0;


$porcentaje_comprador =
    $comprador_total
        ? round(
            ($comprador_completos / $comprador_total) * 100
        )
        : 0;


    /*
     * ========================================================
     * AVALÚO SOLICITADO
     *
     * Se considera solicitado cuando existe valuador.
     * ========================================================
     */

    $avaluo_solicitado =
        $valuador_id ? true : false;


/**
 * ========================================================
 * CARPETA GOOGLE DRIVE
 * ========================================================
 */

$carpeta_google_drive = get_field(
    'carpeta_google_drive',
    $operacion_id
);


    /*
     * ========================================================
     * AVISO DE GUARDADO
     *
     * El guardado se procesa antes de mostrar la página
     * (crm_v3_expediente_guardar) y regresa aquí con ?guardado=1,
     * así la pantalla siempre muestra lo que quedó guardado.
     * ========================================================
     */

    if (isset($_GET['guardado'])) {

        echo '<div class="crm-expediente-message">
        <span>Expediente guardado correctamente.</span>
        <button type="button" class="crm-expediente-message-close" aria-label="Cerrar">×</button>
        </div>';
    }


    ?>

    <div class="wrap crm-v3-expediente">

        <form method="post">

            <?php
            wp_nonce_field(
                'crm_guardar_expediente_' . $operacion_id
            );
            ?>

<!-- ==================================================
     HEADER
================================================== -->

<div class="crm-expediente-header">

    <div class="crm-expediente-header-info">
        <h1>CRM EXPEDIENTE</h1>

        <p>
            CIBR Asesoría Análisis y Gestión Inmobiliaria
        </p>
    </div>

    <?php if ($cliente_id) : ?>

        <a
            href="<?php echo esc_url(
                admin_url(
                    'admin.php?page=crm-ficha-cliente&cliente_id=' . $cliente_id
                )
            ); ?>"
            class="crm-expediente-back"
        >
            ← Ficha del cliente
        </a>

    <?php endif; ?>

</div>


<!-- ==================================================
     CONTROLES DEL EXPEDIENTE
================================================== -->

<div class="crm-expediente-controls">

    <div class="crm-expediente-drive-box">

        <?php if (!empty($carpeta_google_drive)) : ?>

            <a
                href="<?php echo esc_url($carpeta_google_drive); ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="crm-expediente-drive"
            >
                📁 Link expediente
            </a>

        <?php endif; ?>

        <input
            type="url"
            name="carpeta_google_drive"
            value="<?php echo esc_attr($carpeta_google_drive); ?>"
            placeholder="Pegar link de Google Drive"
            class="crm-expediente-drive-input"
        >

    </div>


    <div class="crm-expediente-progress">

        <span>% Exp Vendedor</span>

        <strong>
            <?php echo esc_html($porcentaje_vendedor); ?>%
        </strong>

    </div>


    <div class="crm-expediente-progress">

        <span>% Exp Comprador</span>

        <strong>
            <?php echo esc_html($porcentaje_comprador); ?>%
        </strong>

    </div>


    <div class="crm-expediente-avaluo">

        <span class="crm-expediente-check">
            <?php echo $avaluo_solicitado ? 'X' : ''; ?>
        </span>

        <span>Avalúo solicitado</span>

    </div>


    <button
        type="submit"
        name="crm_expediente_guardar"
        class="crm-expediente-save"
    >
        Guardar
    </button>

</div>



            <!-- ==================================================
                 COLUMNAS
            ================================================== -->

            <div class="crm-expediente-columns">


                <!-- ==================================================
                     VENDEDOR
                ================================================== -->

                <section class="crm-expediente-column">

                    <div class="crm-expediente-card">

                        <h2>VENDEDOR</h2>

                        <div class="crm-expediente-data-grid">

                            <div>
                                <strong>Nombre</strong>
                                <span>
                                    <?php echo esc_html($vendedor_nombre); ?>
                                </span>
                            </div>

                            <div>
                                <strong>Adeudo Hipoteca</strong>
                                <span>
                                    <?php echo esc_html(
                                        $vendedor_adeudo ?: '—'
                                    ); ?>
                                </span>
                            </div>

                            <div>
                                <strong>Dirección</strong>
                                <span>
                                    <?php echo esc_html(
                                        $vendedor_direccion ?: '—'
                                    ); ?>
                                </span>
                            </div>

                            <div>
                                <strong>Precio mercado</strong>
                                <span>
                                    <?php echo esc_html(
                                        $valor_mercado ?: '—'
                                    ); ?>
                                </span>
                            </div>

                            <div>
                                <strong>Propiedad</strong>
                                <span>
                                    <?php echo esc_html($propiedad_nombre); ?>
                                </span>
                            </div>

                            <div>
                                <strong>Precio de venta</strong>
                                <span>
                                    <?php echo esc_html(
                                        $precio_venta ?: '—'
                                    ); ?>
                                </span>
                            </div>

                            <div>
                                <strong>Dirección</strong>
                                <span>
                                    <?php echo esc_html(
                                        $propiedad_direccion ?: '—'
                                    ); ?>
                                </span>
                            </div>

                            <div>
                                <strong>CURP</strong>
                                <input
                                    type="text"
                                    name="vendedor_curp"
                                    value="<?php echo esc_attr(
                                        $vendedor_manual['curp'] ?? ''
                                    ); ?>"
                                >
                            </div>

                            <div>
                                <strong>Origen de recursos</strong>
                                <span>
                                    <?php echo esc_html(
                                        crm_v3_display_value(
                                            $vendedor_origen
                                        )
                                    ); ?>
                                </span>
                            </div>

                            <div>
                                <strong>RFC</strong>
                                <input
                                    type="text"
                                    name="vendedor_rfc"
                                    value="<?php echo esc_attr(
                                        $vendedor_manual['rfc'] ?? ''
                                    ); ?>"
                                >
                            </div>

                            <div>
                                <strong>Tipo de crédito</strong>
                                <span>
                                    <?php echo esc_html(
                                        crm_v3_display_value(
                                            $vendedor_tipo_credito
                                        )
                                    ); ?>
                                </span>
                            </div>

                        </div>

                    </div>


                    <!-- DOCUMENTOS VENDEDOR -->
                     <div class="crm-expediente-documents-title">
                        EXPEDIENTE VENDEDOR</div>

                     <div class="crm-expediente-documents">

                     <div class="crm-expediente-documents-header">
                    <span>C</span>
                    <span>P</span>
                    <span>Documentos "C = Conyuge, P = Principal" </span>
                </div>

                    <?php foreach ($documentos_vendedor as $key => $documento) : ?>

    <?php
    $estado = $vendedor_documentos[$key] ?? [];

    if (!is_array($estado)) {
        $estado = [
            'principal' => !empty($estado) ? 1 : 0,
            'conyuge'   => 0,
        ];
    }
    ?>

    <?php if (($documento['tipo'] ?? '') === 'titulo') : ?>

    <div class="crm-expediente-document-row crm-expediente-document-title">
        <?php echo esc_html($documento['label']); ?>
    </div>

<?php else : ?>

    <label class="crm-expediente-document-row">

        <span class="crm-expediente-document-checks">

            <?php if (!empty($documento['doble'])) : ?>

                <input
                    type="checkbox"
                    name="vendedor_documentos[<?php echo esc_attr($key); ?>][conyuge]"
                    value="1"
                    <?php checked(!empty($estado['conyuge'])); ?>
                >

            <?php else : ?>

                <span class="crm-expediente-check-placeholder"></span>

            <?php endif; ?>

            <input
                type="checkbox"
                name="vendedor_documentos[<?php echo esc_attr($key); ?>][principal]"
                value="1"
                <?php checked(!empty($estado['principal'])); ?>
            >

        </span>

        <span>
            <?php echo esc_html($documento['label']); ?>
        </span>

    </label>

<?php endif; ?>

<?php endforeach; ?>

                    </div>


                    <!-- NOTAS VENDEDOR -->

                    <div class="crm-expediente-notes">

                        <label>Notas</label>

                        <textarea
                            name="vendedor_notas"
                            placeholder="Agregar comentarios..."
                        ><?php echo esc_textarea($vendedor_notas); ?></textarea>

                    </div>

                </section>


                <!-- ==================================================
                     COMPRADOR
                ================================================== -->

                <section class="crm-expediente-column">

                    <div class="crm-expediente-card">

                        <h2>COMPRADOR</h2>

                        <div class="crm-expediente-data-grid">

                            <div>
                                <strong>Nombre</strong>
                                <span>
                                    <?php echo esc_html($comprador_nombre); ?>
                                </span>
                            </div>

                            <div>
                                <strong>NSS</strong>
                                <input
                                    type="text"
                                    name="comprador_nss"
                                    value="<?php echo esc_attr(
                                        $comprador_manual['nss'] ?? ''
                                    ); ?>"
                                >
                            </div>

                            <div>
                                <strong>Dirección</strong>
                                <span>
                                    <?php echo esc_html(
                                        $comprador_direccion ?: '—'
                                    ); ?>
                                </span>
                            </div>

                            <div>
                                <strong>CURP</strong>
                                <input
                                    type="text"
                                    name="comprador_curp"
                                    value="<?php echo esc_attr(
                                        $comprador_manual['curp'] ?? ''
                                    ); ?>"
                                >
                            </div>

                            <div>
                                <strong>Nombre cónyuge</strong>
                                <input
                                    type="text"
                                    name="comprador_conyuge"
                                    value="<?php echo esc_attr(
                                        $comprador_manual['conyuge'] ?? ''
                                    ); ?>"
                                >
                            </div>

                            <div>
                                <strong>RFC</strong>
                                <input
                                    type="text"
                                    name="comprador_rfc"
                                    value="<?php echo esc_attr(
                                        $comprador_manual['rfc'] ?? ''
                                    ); ?>"
                                >
                            </div>

                            <div>
                                <strong>Origen de recursos</strong>
                                <span>
                                    <?php echo esc_html(
                                        crm_v3_display_value(
                                            $comprador_origen
                                        )
                                    ); ?>
                                </span>
                            </div>

                            <div>
                                <strong>NSS cónyuge</strong>
                                <input
                                    type="text"
                                    name="comprador_conyuge_nss"
                                    value="<?php echo esc_attr(
                                        $comprador_manual['conyuge_nss'] ?? ''
                                    ); ?>"
                                >
                            </div>

                            <div>
                                <strong>Tipo de crédito</strong>
                                <span>
                                    <?php echo esc_html(
                                        crm_v3_display_value(
                                            $comprador_tipo_credito
                                        )
                                    ); ?>
                                </span>
                            </div>

                            <div>
                                <strong>CURP cónyuge</strong>
                                <input
                                    type="text"
                                    name="comprador_conyuge_curp"
                                    value="<?php echo esc_attr(
                                        $comprador_manual['conyuge_curp'] ?? ''
                                    ); ?>"
                                >
                            </div>

                            <div>
                                <strong>Presupuesto</strong>
                                <span>
                                    <?php echo esc_html(
                                        $comprador_presupuesto ?: '—'
                                    ); ?>
                                </span>
                            </div>

                            <div>
                                <strong>RFC cónyuge</strong>
                                <input
                                    type="text"
                                    name="comprador_conyuge_rfc"
                                    value="<?php echo esc_attr(
                                        $comprador_manual['conyuge_rfc'] ?? ''
                                    ); ?>"
                                >
                            </div>


                        </div>

                    </div>


                    <!-- DOCUMENTOS COMPRADOR -->
                    <div class="crm-expediente-documents-title">
                        EXPEDIENTE COMPRADOR</div>

                    <div class="crm-expediente-documents">

                    <div class="crm-expediente-documents-header">
                        <span>C</span>
                        <span>P</span>
                        <span>Documentos "C = Conyuge, P = Principal"</span>
                    </div>

                        <?php foreach ($documentos_comprador as $key => $documento) : ?>

    <?php
    $estado = $comprador_documentos[$key] ?? [];

    if (!is_array($estado)) {
        $estado = [
            'principal' => !empty($estado) ? 1 : 0,
            'conyuge'   => 0,
        ];
    }
    ?>

    <label class="crm-expediente-document-row">

        <span class="crm-expediente-document-checks">

            <?php if (!empty($documento['doble'])) : ?>

                <input
                    type="checkbox"
                    name="comprador_documentos[<?php echo esc_attr($key); ?>][conyuge]"
                    value="1"
                    <?php checked(!empty($estado['conyuge'])); ?>
                >

            <?php else : ?>

                <span class="crm-expediente-check-placeholder"></span>

            <?php endif; ?>

            <input
                type="checkbox"
                name="comprador_documentos[<?php echo esc_attr($key); ?>][principal]"
                value="1"
                <?php checked(!empty($estado['principal'])); ?>
            >

        </span>

        <span>
            <?php echo esc_html($documento['label']); ?>
        </span>

    </label>

<?php endforeach; ?>

                    </div>


                    <!-- NOTAS COMPRADOR -->

                    <div class="crm-expediente-notes">

                        <label>Notas</label>

                        <textarea
                            name="comprador_notas"
                            placeholder="Agregar comentarios..."
                        ><?php echo esc_textarea($comprador_notas); ?></textarea>

                    </div>

                </section>

            </div>

        </form>

    </div>

    <?php
}


/**
 * ============================================================
 * LISTA DE DOCUMENTOS DEL EXPEDIENTE
 * ============================================================
 *
 * doble = permite marcar PRINCIPAL + CÓNYUGE
 * simple = solamente PRINCIPAL
 * tipo "titulo" = encabezado de sección, no es un documento
 */

function crm_v3_expediente_documentos($quien) {

    $vendedor = [
    
        'estado_cuenta_credito' => [
            'label' => 'Estado de cuenta del crédito, si aplica',
            'doble' => false,
        ],
    
        'identificacion' => [
            'label' => 'Identificación',
            'doble' => true,
        ],
    
        'comprobante_domicilio' => [
            'label' => 'Comprobante de domicilio',
            'doble' => false,
        ],
    
        'acta_nacimiento' => [
            'label' => 'Acta de nacimiento',
            'doble' => true,
        ],
    
        'acta_matrimonio' => [
            'label' => 'Acta de Matrimonio',
            'doble' => false,
        ],
    
        'curp' => [
            'label' => 'CURP',
            'doble' => true,
        ],
    
        'rfc' => [
            'label' => 'RFC',
            'doble' => true,
        ],
    
        'factura_compra' => [
            'label' => 'Factura de la compra de la propiedad 2014 en adelante',
            'doble' => false,
        ],
    
        'estado_cuenta_bancario' => [
            'label' => 'Estado de cuenta bancario para el deposito',
            'doble' => false,
        ],
    
        'escritura' => [
            'label' => 'Escritura, si es 2do testimonio validar la boleta',
            'doble' => false,
        ],
    
        'boleta_registral' => [
            'label' => 'Boleta Registral',
            'doble' => false,
        ],
    
        'carta_uso_suelo' => [
            'label' => 'Carta tipo de uso de suelo',
            'doble' => false,
        ],
    
        'alineamiento' => [
            'label' => 'Alineamiento de numero oficial, si aplica',
            'doble' => false,
        ],
    
        'no_adeudo_predial' => [
            'label' => 'Constancia de no adeudo predial',
            'doble' => false,
        ],
    
        'no_adeudo_agua' => [
            'label' => 'Constancia de no adeudo Agua',
            'doble' => false,
        ],
    
        'isr' => [
            'label' => 'Para exentar ISR se requiere:',
            'tipo' => 'titulo',
        ],
    
        'ine_domicilio' => [
            'label' => '- INE con domicilio de la casa en venta',
            'doble' => false,
        ],
    
        'recibos_cfe' => [
            'label' => '- 3 recibos del CFE con la dirección de la casa en venta y el RFC del dueño en el recibo',
            'doble' => false,
        ],
    ];

    $comprador = [
    
        'solicitud_inscripcion' => [
            'label' => 'Solicitud de Inscripción',
            'doble' => false,
        ],
    
        'identificacion' => [
            'label' => 'Identificación',
            'doble' => true,
        ],
    
        'comprobante_domicilio' => [
            'label' => 'Comprobante de domicilio',
            'doble' => false,
        ],
    
        'acta_nacimiento' => [
            'label' => 'Acta de nacimiento',
            'doble' => true,
        ],
    
        'acta_matrimonio' => [
            'label' => 'Acta de Matrimonio',
            'doble' => false,
        ],
    
        'curp' => [
            'label' => 'CURP',
            'doble' => true,
        ],
    
        'rfc' => [
            'label' => 'RFC',
            'doble' => true,
        ],
    
        'constancia_taller' => [
            'label' => 'Constancia del taller saber para decidir mejor',
            'doble' => true,
        ],
    
        'pago_avaluo' => [
            'label' => 'Pago de Avalúo',
            'doble' => false,
        ],
    ];

    return $quien === 'comprador'
        ? $comprador
        : $vendedor;
}


/**
 * ============================================================
 * GUARDAR EXPEDIENTE
 * ============================================================
 *
 * Se procesa antes de mostrar la página y se redirige, para que:
 * - la pantalla muestre siempre lo guardado (porcentajes, link),
 * - recargar no reenvíe el formulario,
 * - los textos con comillas no acumulen barras invertidas.
 */

function crm_v3_expediente_guardar() {

    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'crm-expediente' ||
        !isset($_POST['crm_expediente_guardar'])
    ) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    $operacion_id = isset($_GET['operacion_id'])
        ? absint($_GET['operacion_id'])
        : 0;

    if (
        !$operacion_id ||
        get_post_type($operacion_id) !== 'operaciones'
    ) {
        return;
    }

    check_admin_referer(
        'crm_guardar_expediente_' . $operacion_id
    );

    $post = wp_unslash($_POST);

    $texto = function ($clave) use ($post) {

        return isset($post[$clave])
            ? sanitize_text_field($post[$clave])
            : '';
    };

    /*
     * Documentos: solo se aceptan las claves de la lista.
     */
    $documentos = function ($quien) use ($post) {

        $recibidos = isset($post[$quien . '_documentos']) &&
            is_array($post[$quien . '_documentos'])
                ? $post[$quien . '_documentos']
                : array();

        $resultado = array();

        foreach (crm_v3_expediente_documentos($quien) as $clave => $documento) {

            if (($documento['tipo'] ?? '') === 'titulo') {
                continue;
            }

            if (
                !isset($recibidos[$clave]) ||
                !is_array($recibidos[$clave])
            ) {
                continue;
            }

            $resultado[$clave] = array(
                'principal' => !empty($recibidos[$clave]['principal']) ? 1 : 0,
                'conyuge'   => !empty($recibidos[$clave]['conyuge']) ? 1 : 0,
            );
        }

        return $resultado;
    };

    $expediente = array(

        'vendedor_documentos'  => $documentos('vendedor'),
        'comprador_documentos' => $documentos('comprador'),

        'vendedor_notas' => isset($post['vendedor_notas'])
            ? sanitize_textarea_field($post['vendedor_notas'])
            : '',

        'comprador_notas' => isset($post['comprador_notas'])
            ? sanitize_textarea_field($post['comprador_notas'])
            : '',

        'vendedor_manual' => array(
            'curp' => $texto('vendedor_curp'),
            'rfc'  => $texto('vendedor_rfc'),
        ),

        'comprador_manual' => array(
            'nss'          => $texto('comprador_nss'),
            'curp'         => $texto('comprador_curp'),
            'rfc'          => $texto('comprador_rfc'),
            'conyuge'      => $texto('comprador_conyuge'),
            'conyuge_nss'  => $texto('comprador_conyuge_nss'),
            'conyuge_curp' => $texto('comprador_conyuge_curp'),
            'conyuge_rfc'  => $texto('comprador_conyuge_rfc'),
        ),
    );

    // update_post_meta espera datos con barras, como llegan de $_POST.
    update_post_meta(
        $operacion_id,
        '_crm_expediente',
        wp_slash($expediente)
    );

    if (isset($post['carpeta_google_drive'])) {

        update_field(
            'carpeta_google_drive',
            esc_url_raw(trim($post['carpeta_google_drive'])),
            $operacion_id
        );
    }

    $destino = array(
        'page'         => 'crm-expediente',
        'operacion_id' => $operacion_id,
        'guardado'     => 1,
    );

    if (!empty($_GET['cliente_id'])) {
        $destino['cliente_id'] = absint($_GET['cliente_id']);
    }

    wp_safe_redirect(
        add_query_arg($destino, admin_url('admin.php'))
    );

    exit;
}

add_action(
    'admin_init',
    'crm_v3_expediente_guardar'
);

