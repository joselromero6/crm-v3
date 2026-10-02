<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ============================================================
 * CRM V3 — CÁLCULO DE ISR POR ENAJENACIÓN
 * ============================================================
 *
 * Datos automáticos:
 * - Precio de cierre / venta
 * - Fecha de cierre
 * - Propiedad
 * - Valor catastral
 * - Comisión
 * - Vendedor
 *
 * Datos fiscales manuales:
 * - Fecha de adquisición
 * - Costo comprobado de adquisición
 * - Porcentaje del costo correspondiente al terreno
 * - Mejoras / ampliaciones
 * - Gastos notariales de adquisición
 * - Gastos notariales de venta
 * - Impuestos y derechos
 * - Impuesto local
 * - Avalúo
 * - Comisiones / mediaciones adicionales
 * - UDI aplicable
 * - Factor de actualización del costo
 * - Factor de actualización de mejoras
 * - Factor de actualización de deducciones
 * - Casa habitación / exención
 * - Exención utilizada en los últimos 3 años
 *
 * NOTA:
 * El resultado es una ESTIMACIÓN para análisis inmobiliario.
 * El cálculo definitivo y la retención formal corresponden al
 * fedatario cuando la operación se formaliza en escritura.
 */


/* ============================================================
 * HELPERS
 * ============================================================ */

function crm_v3_isr_numero($valor) {

    if ($valor === null || $valor === '') {
        return 0.0;
    }

    if (is_string($valor)) {
        $valor = str_replace(
            array(',', '$', ' '),
            '',
            $valor
        );
    }

    return (float) $valor;
}


function crm_v3_isr_fecha($fecha) {

    if (!$fecha) {
        return '';
    }

    if (preg_match('/^\d{8}$/', $fecha)) {
        $obj = DateTime::createFromFormat(
            'Ymd',
            $fecha
        );

        return $obj
            ? $obj->format('Y-m-d')
            : '';
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return $fecha;
    }

    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha)) {
        $obj = DateTime::createFromFormat(
            'd/m/Y',
            $fecha
        );

        return $obj
            ? $obj->format('Y-m-d')
            : '';
    }

    return '';
}


function crm_v3_isr_money($valor) {

    return crm_v3_format_money(
        crm_v3_isr_numero($valor)
    );
}


/* ============================================================
 * TARIFA ISR 2026
 *
 * Anexo 8 RMF 2026
 * Artículo 126 LISR
 * ============================================================ */

function crm_v3_isr_tarifa_2026() {

    return array(

        array(
            'li' => 0.01,
            'ls' => 10135.11,
            'cuota' => 0.00,
            'porcentaje' => 1.92,
        ),

        array(
            'li' => 10135.12,
            'ls' => 86022.11,
            'cuota' => 194.59,
            'porcentaje' => 6.40,
        ),

        array(
            'li' => 86022.12,
            'ls' => 151176.19,
            'cuota' => 5051.37,
            'porcentaje' => 10.88,
        ),

        array(
            'li' => 151176.20,
            'ls' => 175735.66,
            'cuota' => 12140.13,
            'porcentaje' => 16.00,
        ),

        array(
            'li' => 175735.67,
            'ls' => 210403.69,
            'cuota' => 16069.64,
            'porcentaje' => 17.92,
        ),

        array(
            'li' => 210403.70,
            'ls' => 424353.97,
            'cuota' => 22282.14,
            'porcentaje' => 21.36,
        ),

        array(
            'li' => 424353.98,
            'ls' => 668840.14,
            'cuota' => 67981.92,
            'porcentaje' => 23.52,
        ),

        array(
            'li' => 668840.15,
            'ls' => 1276925.98,
            'cuota' => 125485.07,
            'porcentaje' => 30.00,
        ),

        array(
            'li' => 1276925.99,
            'ls' => 1702567.97,
            'cuota' => 307910.81,
            'porcentaje' => 32.00,
        ),

        array(
            'li' => 1702567.98,
            'ls' => 5107703.92,
            'cuota' => 444116.23,
            'porcentaje' => 34.00,
        ),

        array(
            'li' => 5107703.93,
            'ls' => PHP_FLOAT_MAX,
            'cuota' => 1601862.46,
            'porcentaje' => 35.00,
        ),

    );
}


function crm_v3_isr_calcular_tarifa_2026($base) {

    $base = max(
        0,
        crm_v3_isr_numero($base)
    );

    if ($base <= 0) {
        return 0;
    }

    foreach (
        crm_v3_isr_tarifa_2026()
        as $rango
    ) {

        if (
            $base >= $rango['li'] &&
            $base <= $rango['ls']
        ) {

            return round(
                $rango['cuota']
                +
                (
                    ($base - $rango['li'])
                    *
                    ($rango['porcentaje'] / 100)
                ),
                2
            );
        }
    }

    return 0;
}


/* ============================================================
 * AÑOS TRANSCURRIDOS
 *
 * Para efectos de los artículos 120 y 126:
 * años transcurridos, máximo 20.
 * ============================================================ */

function crm_v3_isr_anios_transcurridos(
    $fecha_adquisicion,
    $fecha_enajenacion
) {

    $fecha_adquisicion =
        crm_v3_isr_fecha($fecha_adquisicion);

    $fecha_enajenacion =
        crm_v3_isr_fecha($fecha_enajenacion);

    if (
        !$fecha_adquisicion ||
        !$fecha_enajenacion
    ) {
        return 0;
    }

    try {

        $inicio = new DateTime(
            $fecha_adquisicion
        );

        $fin = new DateTime(
            $fecha_enajenacion
        );

    } catch (Exception $e) {

        return 0;
    }

    if ($inicio > $fin) {
        return 0;
    }

    $anios = (int) $inicio->diff($fin)->y;

    /*
     * Si aún no se cumple un año,
     * para el cálculo fiscal no dejamos el divisor en cero.
     */
    $anios = max(
        1,
        $anios
    );

    return min(
        20,
        $anios
    );
}


/* ============================================================
 * RENDER
 * ============================================================ */

function crm_v3_isr_operacion(
    $operacion_id
) {

    $operacion_id = absint(
        $operacion_id
    );

    if (!$operacion_id) {
        return;
    }


    /* --------------------------------------------------------
     * RELACIONES
     * -------------------------------------------------------- */

    $propiedad = get_field(
        'propiedad',
        $operacion_id
    );

    $propiedad_id =
        crm_v3_get_related_id(
            $propiedad
        );


    $vendedor_id = 0;

    if ($propiedad_id) {

        $vendedor = get_field(
            'cliente_propietario',
            $propiedad_id
        );

        $vendedor_id =
            crm_v3_get_related_id(
                $vendedor
            );
    }


    /* --------------------------------------------------------
     * DATOS AUTOMÁTICOS
     * -------------------------------------------------------- */

    $precio_cierre =
    crm_v3_isr_numero(
        get_field(
            'precio_de_cierre',
            $operacion_id
        )
    );

    $fecha_cierre =
        crm_v3_isr_fecha(
            get_field(
                'fecha_cierre',
                $operacion_id
            )
        );

    $precio_venta =
        $propiedad_id
            ? crm_v3_isr_numero(
                get_field(
                    'precio_de_venta',
                    $propiedad_id
                )
            )
            : 0;

    $valor_catastral =
        $propiedad_id
            ? crm_v3_isr_numero(
                get_field(
                    'valor_catastral',
                    $propiedad_id
                )
            )
            : 0;

    $comision =
        $propiedad_id
            ? crm_v3_isr_numero(
                get_field(
                    'comision',
                    $propiedad_id
                )
            )
            : 0;


    /*
     * Si existe precio de cierre usamos éste.
     * Si todavía no existe, mostramos precio de venta
     * como referencia, pero no calculamos ISR.
     */
    $ingreso =
        $precio_cierre > 0
            ? $precio_cierre
            : $precio_venta;


    /* --------------------------------------------------------
     * DATOS FISCALES GUARDADOS
     * -------------------------------------------------------- */

    $campos = array(
        'fecha_adquisicion',
        'costo_adquisicion',
        'porcentaje_terreno',
        'mejoras',
        'gastos_notariales_adquisicion',
        'gastos_notariales_venta',
        'impuestos_derechos',
        'impuesto_local',
        'avaluo',
        'comisiones_mediaciones',
        'udi',
        'factor_costo',
        'factor_mejoras',
        'factor_deducciones',
        'es_casa_habitacion',
        'exencion_3_anios',
        'otros_ingresos_anuales',
    );

    $datos = array();

    foreach ($campos as $campo) {

        $datos[$campo] =
            get_post_meta(
                $operacion_id,
                '_crm_v3_isr_' . $campo,
                true
            );
    }


    /* --------------------------------------------------------
     * NORMALIZACIÓN
     * -------------------------------------------------------- */

    $fecha_adquisicion =
        crm_v3_isr_fecha(
            $datos['fecha_adquisicion']
        );

    $costo_adquisicion =
        crm_v3_isr_numero(
            $datos['costo_adquisicion']
        );

    $porcentaje_terreno =
        crm_v3_isr_numero(
            $datos['porcentaje_terreno']
        );

    /*
     * Si no se captura separación del terreno,
     * se utilizará 20% conforme al artículo 124.
     */
    if (
        $porcentaje_terreno <= 0 ||
        $porcentaje_terreno > 100
    ) {
        $porcentaje_terreno = 20;
    }

    $mejoras =
        crm_v3_isr_numero(
            $datos['mejoras']
        );

    $gastos_notariales_adquisicion =
        crm_v3_isr_numero(
            $datos['gastos_notariales_adquisicion']
        );

    $gastos_notariales_venta =
        crm_v3_isr_numero(
            $datos['gastos_notariales_venta']
        );

    $impuestos_derechos =
        crm_v3_isr_numero(
            $datos['impuestos_derechos']
        );

    $impuesto_local =
        crm_v3_isr_numero(
            $datos['impuesto_local']
        );

    $avaluo =
        crm_v3_isr_numero(
            $datos['avaluo']
        );

    $comisiones_mediaciones =
        crm_v3_isr_numero(
            $datos['comisiones_mediaciones']
        );

    $udi =
        crm_v3_isr_numero(
            $datos['udi']
        );

    $factor_costo =
        crm_v3_isr_numero(
            $datos['factor_costo']
        );

    $factor_mejoras =
        crm_v3_isr_numero(
            $datos['factor_mejoras']
        );

    $factor_deducciones =
        crm_v3_isr_numero(
            $datos['factor_deducciones']
        );

    $es_casa_habitacion =
        $datos['es_casa_habitacion'] === '1';

    $exencion_3_anios =
        $datos['exencion_3_anios'] === '1';


/* --------------------------------------------------------
 * ISR EXENTO
 * -------------------------------------------------------- */

$isr_exento =
    get_post_meta(
        $operacion_id,
        '_crm_v3_isr_exento',
        true
    ) === '1';
    

    /* --------------------------------------------------------
     * COSTO DE ADQUISICIÓN
     * -------------------------------------------------------- */

    $costo_terreno =
        $costo_adquisicion
        * ($porcentaje_terreno / 100);

    $costo_construccion =
        $costo_adquisicion
        - $costo_terreno;


    $anios =
        crm_v3_isr_anios_transcurridos(
            $fecha_adquisicion,
            $fecha_cierre
        );


    /*
     * Disminución de construcción:
     * 3% anual, mínimo 20% del costo inicial.
     */
    $factor_disminucion =
        max(
            0.20,
            1 - (
                $anios * 0.03
            )
        );

    $costo_construccion_disminuido =
        $costo_construccion
        * $factor_disminucion;


    /*
     * Actualización manual.
     *
     * Si no se captura factor, se conserva
     * el importe histórico para evitar inventar INPC.
     */
    $factor_costo =
        $factor_costo > 0
            ? $factor_costo
            : 1;

    $factor_mejoras =
        $factor_mejoras > 0
            ? $factor_mejoras
            : 1;

    $factor_deducciones =
        $factor_deducciones > 0
            ? $factor_deducciones
            : 1;


    $costo_terreno_actualizado =
        $costo_terreno
        * $factor_costo;

    $costo_construccion_actualizado =
        $costo_construccion_disminuido
        * $factor_costo;

    $costo_adquisicion_actualizado =
        $costo_terreno_actualizado
        +
        $costo_construccion_actualizado;


    /*
     * Art. 121:
     * costo actualizado no menor al 10% del ingreso.
     */
    $minimo_costo =
        $ingreso * 0.10;

    if (
        $costo_adquisicion_actualizado > 0 &&
        $costo_adquisicion_actualizado < $minimo_costo
    ) {

        $costo_adquisicion_actualizado =
            $minimo_costo;
    }


    /* --------------------------------------------------------
     * MEJORAS
     * -------------------------------------------------------- */

    $mejoras_actualizadas =
        $mejoras
        * $factor_mejoras;


    /* --------------------------------------------------------
     * DEDUCCIONES ADICIONALES
     * -------------------------------------------------------- */

    $deducciones_adicionales_historicas =
        $gastos_notariales_adquisicion
        +
        $gastos_notariales_venta
        +
        $impuestos_derechos
        +
        $impuesto_local
        +
        $avaluo
        +
        $comisiones_mediaciones;


    $deducciones_adicionales_actualizadas =
        $deducciones_adicionales_historicas
        * $factor_deducciones;


    /* --------------------------------------------------------
     * GANANCIA
     * -------------------------------------------------------- */

    $deducciones_totales =
        $costo_adquisicion_actualizado
        +
        $mejoras_actualizadas
        +
        $deducciones_adicionales_actualizadas;


    $ganancia =
        max(
            0,
            $ingreso
            -
            $deducciones_totales
        );


    /* --------------------------------------------------------
     * EXENCIÓN CASA HABITACIÓN
     * -------------------------------------------------------- */

    $limite_exento =
        $udi > 0
            ? $udi * 700000
            : 0;

    $monto_exento =
        0;

    $monto_gravado =
        $ingreso;


    if (
        $es_casa_habitacion &&
        !$exencion_3_anios &&
        $limite_exento > 0
    ) {

        $monto_exento =
            min(
                $ingreso,
                $limite_exento
            );

        $monto_gravado =
            max(
                0,
                $ingreso
                -
                $monto_exento
            );
    }


    /*
     * Cuando existe excedente de casa habitación,
     * las deducciones se consideran en proporción al excedente.
     *
     * Art. 93 XIX a).
     */
    $proporcion_gravada =
        $ingreso > 0
            ? (
                $monto_gravado
                / $ingreso
            )
            : 0;

    $deducciones_gravadas =
        $deducciones_totales
        * $proporcion_gravada;

    $ganancia_gravada =
        max(
            0,
            $monto_gravado
            -
            $deducciones_gravadas
        );


    /* --------------------------------------------------------
     * ISR PROVISIONAL — ARTÍCULO 126
     * -------------------------------------------------------- */

    $base_anualizada =
        $anios > 0
            ? (
                $ganancia_gravada
                / $anios
            )
            : 0;

    $isr_anualizado =
        crm_v3_isr_calcular_tarifa_2026(
            $base_anualizada
        );

    $isr_federal =
        $isr_anualizado
        * max(
            1,
            $anios
        );


    /* --------------------------------------------------------
     * ISR ESTATAL — ARTÍCULO 127
     *
     * 5% sobre la ganancia.
     * Es acreditable contra el pago provisional federal.
     * -------------------------------------------------------- */

    $isr_estatal =
        $ganancia_gravada * 0.05;


    /*
     * El total que realmente se entera como pago provisional
     * no debe sumar federal + estatal.
     *
     * El estatal es acreditable contra el federal.
     */
    $isr_estatal_acreditable =
        min(
            $isr_estatal,
            $isr_federal
        );

    $isr_federal_neto =
        max(
            0,
            $isr_federal
            -
            $isr_estatal_acreditable
        );

    $isr_total_provisional =
        $isr_federal;


    /* --------------------------------------------------------
     * GUARDAR Y MOSTRAR
     * -------------------------------------------------------- */

    ?>

    <div class="crm-v3-isr">

        <div class="crm-v3-isr-title">
            CÁLCULO DE ISR
        </div>

        <div class="crm-v3-isr-note">
            Estimación de ISR por enajenación de inmueble.
        </div>


        <!-- DATOS AUTOMÁTICOS -->

        <div class="crm-v3-isr-section">

            <div class="crm-v3-isr-section-title">
                DATOS AUTOMÁTICOS
            </div>

            <div class="crm-v3-isr-grid">

                

                <div class="crm-v3-isr-item">
                    <span>Precio de venta</span>
                    <strong>
                        <?php echo esc_html(
                            crm_v3_isr_money(
                                $precio_venta
                            )
                        ); ?>
                    </strong>
                </div>
                
                
                <div class="crm-v3-isr-item">
                    <span>Precio de cierre</span>
                    <strong>
                        <?php echo esc_html(
                            crm_v3_isr_money(
                                $precio_cierre
                            )
                        ); ?>
                    </strong>
                </div>

                <div class="crm-v3-isr-item">
                    <span>Valor catastral</span>
                    <strong>
                        <?php echo esc_html(
                            crm_v3_isr_money(
                                $valor_catastral
                            )
                        ); ?>
                    </strong>
                </div>

                <div class="crm-v3-isr-item">
                    <span>Comisión Registrada</span>
                    <strong>
                        <?php echo esc_html(
                            crm_v3_isr_money(
                                $comision
                            )
                        ); ?>
                    </strong>
                </div>

            </div>

        </div>


        <!-- DATOS FISCALES -->

        <div class="crm-v3-isr-section">

            <div class="crm-v3-isr-section-title">
                DATOS FISCALES
            </div>

            <form
                method="post"
                class="crm-v3-isr-form"
            >

                <?php
                wp_nonce_field(
                    'crm_v3_guardar_isr',
                    'crm_v3_isr_nonce'
                );
                ?>

                <input
                    type="hidden"
                    name="crm_v3_isr_operacion_id"
                    value="<?php echo esc_attr(
                        $operacion_id
                    ); ?>"
                >

                <?php
                $campos_form = array(

    'fecha_adquisicion' => array(
        'label' => 'Fecha de adquisición',
        'type'  => 'date',
        'value' => $fecha_adquisicion,
    ),

    'costo_adquisicion' => array(
        'label' => 'Costo comprobado de adquisición',
        'type'  => 'text',
        'value' => crm_v3_isr_money(
        $datos['costo_adquisicion']
    ),
        'class' => 'crm-v3-isr-money-input',
    ),

);

                foreach (
                    $campos_form
                    as $nombre => $campo
                ) :
                ?>

                    <div class="crm-v3-isr-row">

                        <label>
                            <?php echo esc_html(
                                $campo['label']
                            ); ?>
                        </label>

                        <input
                            type="<?php echo esc_attr(
                                $campo['type']
                            ); ?>"
                            name="<?php echo esc_attr(
                                $nombre
                            ); ?>"
                            value="<?php echo esc_attr(
                                $campo['value']
                            ); ?>"
                            <?php if (isset($campo['step'])) : ?>
                                step="<?php echo esc_attr(
                                    $campo['step']
                                ); ?>"
                            <?php elseif ($campo['type'] === 'number') : ?>
                                step="0.01"
                            <?php endif; ?>
                        >

                    </div>

                <?php endforeach; ?>


                <div class="crm-v3-isr-actions">

    <label class="crm-v3-isr-exento">

        <span>
            ISR exento:
        </span>

        <select name="isr_exento">

            <option
                value="0"
                <?php selected(
                    $isr_exento,
                    false
                ); ?>
            >
                No
            </option>

            <option
                value="1"
                <?php selected(
                    $isr_exento,
                    true
                ); ?>
            >
                Sí
            </option>

        </select>

    </label>


    <button
        type="submit"
        name="crm_v3_guardar_isr"
        class="crm-v3-isr-save"
    >
        Guardar datos ISR
    </button>

</div>


        <!-- RESULTADO -->

        <div class="crm-v3-isr-section">

            <div class="crm-v3-isr-section-title">
                RESULTADO DEL CÁLCULO
            </div>

            <div class="crm-v3-isr-grid">

                <div class="crm-v3-isr-item">
                    <span>Años transcurridos</span>
                    <strong>
                        <?php echo esc_html(
                            $anios ?: '—'
                        ); ?>
                    </strong>
                </div>

                <div class="crm-v3-isr-item">
                    <span>Límite exento casa habitación</span>
                    <strong>
                        <?php echo esc_html(
                            $limite_exento > 0
                                ? crm_v3_isr_money(
                                    $limite_exento
                                )
                                : 'Capturar UDI'
                        ); ?>
                    </strong>
                </div>

                <div class="crm-v3-isr-item">
                    <span>Monto exento</span>
                    <strong>
                        <?php echo esc_html(
                            crm_v3_isr_money(
                                $monto_exento
                            )
                        ); ?>
                    </strong>
                </div>

                <div class="crm-v3-isr-item">
                    <span>Ganancia gravada</span>
                    <strong>
                        <?php echo esc_html(
                            crm_v3_isr_money(
                                $ganancia_gravada
                            )
                        ); ?>
                    </strong>
                </div>

                <div class="crm-v3-isr-item">
                    <span>ISR federal estimado</span>
                    <strong>
                        <?php echo esc_html(
                            crm_v3_isr_money(
                                $isr_federal
                            )
                        ); ?>
                    </strong>
                </div>

                <div class="crm-v3-isr-item">
                    <span>ISR estatal 5%</span>
                    <strong>
                        <?php echo esc_html(
                            crm_v3_isr_money(
                                $isr_estatal
                            )
                        ); ?>
                    </strong>
                </div>

                <div class="crm-v3-isr-item crm-v3-isr-total">

    <span>
        ISR provisional estimado
    </span>

    <strong>
        <?php echo esc_html(
            crm_v3_isr_money(
                $isr_total_provisional
            )
        ); ?>
    </strong>

</div>


<div class="crm-v3-isr-item crm-v3-isr-status">

    <span>
        Comentario
    </span>

    <strong>
        <?php
        echo esc_html(
            $isr_exento
                ? 'ISR exentado'
                : 'ISR provisional estimado a exentar'
        );
        ?>
    </strong>

</div>

            </div>

        </div>

        <div class="crm-v3-isr-warning">
            Este cálculo es estimativo y no sustituye la determinación
            y retención del fedatario público.
        </div>

    </div>

    <?php
}


/* ============================================================
 * GUARDAR DATOS
 * ============================================================ */

function crm_v3_guardar_isr_operacion() {

    if (
        !isset(
            $_POST['crm_v3_guardar_isr']
        )
    ) {
        return;
    }

    if (
        !isset(
            $_POST['crm_v3_isr_nonce']
        )
        ||
        !wp_verify_nonce(
            $_POST['crm_v3_isr_nonce'],
            'crm_v3_guardar_isr'
        )
    ) {
        return;
    }

    if (
        !current_user_can('edit_posts')
    ) {
        return;
    }

    $operacion_id =
        isset(
            $_POST['crm_v3_isr_operacion_id']
        )
            ? absint(
                $_POST['crm_v3_isr_operacion_id']
            )
            : 0;

    if (!$operacion_id) {
        return;
    }


         $campos_numericos = array(

            'costo_adquisicion',

        );


    foreach (
        $campos_numericos
        as $campo
    ) {

        $valor =
            isset(
                $_POST[$campo]
            )
                ? sanitize_text_field(
                    wp_unslash(
                        $_POST[$campo]
                    )
                )
                : '';

        update_post_meta(
            $operacion_id,
            '_crm_v3_isr_' . $campo,
            $valor
        );
    }


    $fecha =
        isset(
            $_POST['fecha_adquisicion']
        )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST['fecha_adquisicion']
                )
            )
            : '';

    update_post_meta(
        $operacion_id,
        '_crm_v3_isr_fecha_adquisicion',
        $fecha
    );


    $es_casa =
        isset(
            $_POST['es_casa_habitacion']
        )
        &&
        $_POST['es_casa_habitacion'] === '1'
            ? '1'
            : '0';

    update_post_meta(
        $operacion_id,
        '_crm_v3_isr_es_casa_habitacion',
        $es_casa
    );


    $exencion =
        isset(
            $_POST['exencion_3_anios']
        )
        &&
        $_POST['exencion_3_anios'] === '1'
            ? '1'
            : '0';

    update_post_meta(
        $operacion_id,
        '_crm_v3_isr_exencion_3_anios',
        $exencion
    );

/* --------------------------------------------------------
 * ISR EXENTO
 * -------------------------------------------------------- */

$isr_exento =
    isset(
        $_POST['isr_exento']
    )
    &&
    $_POST['isr_exento'] === '1'
        ? '1'
        : '0';

update_post_meta(
    $operacion_id,
    '_crm_v3_isr_exento',
    $isr_exento
);



    /*
     * Regresar a la ficha.
     */
    $cliente_id = 0;

    if (
        isset(
            $_GET['cliente_id']
        )
    ) {
        $cliente_id =
            absint(
                $_GET['cliente_id']
            );
    }

    if (!$cliente_id && isset($_POST['cliente_id'])) {
        $cliente_id =
            absint(
                $_POST['cliente_id']
            );
    }

    if ($cliente_id) {

        $url =
            add_query_arg(
                array(
                    'page'       => 'crm-ficha-cliente',
                    'cliente_id' => $cliente_id,
                ),
                admin_url('admin.php')
            );

    } else {

        $url =
            admin_url(
                'admin.php?page=crm-clientes'
            );
    }


    wp_safe_redirect($url);
    exit;
}

add_action(
    'admin_init',
    'crm_v3_guardar_isr_operacion'
);
