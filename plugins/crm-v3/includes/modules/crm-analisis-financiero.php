<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * ============================================================
 * CRM V3 — ANÁLISIS FINANCIERO
 * ============================================================
 *
 * El análisis se construye con información existente en:
 *
 * CLIENTE
 * - Tipo de cliente
 * - Presupuesto
 * - Adeudo hipotecario
 *
 * PROPIEDAD
 * - Valor catastral
 * - Precio de venta
 * - Adeudo agua
 * - Adeudo predial
 * - Costo rehabilitación
 * - Comisión
 *
 * OPERACIÓN
 * - Valor de mercado
 * - Precio de cierre
 *
 * No crea ni modifica campos ACF.
 */


/**
 * ============================================================
 * ANÁLISIS FINANCIERO DE UNA OPERACIÓN
 * ============================================================
 */

function crm_v3_analisis_financiero($operacion_id) {

    $operacion_id = (int) $operacion_id;

    if (!$operacion_id) {
        return;
    }


    /* ========================================================
     * PROPIEDAD RELACIONADA
     * ======================================================== */

    $propiedad = get_field(
        'propiedad',
        $operacion_id
    );

    $propiedad_id = crm_v3_get_related_id(
        $propiedad
    );


    /* ========================================================
     * COMPRADOR
     * ======================================================== */

    $comprador = get_field(
        'comprador',
        $operacion_id
    );

    $comprador_id = crm_v3_get_related_id(
        $comprador
    );


    /* ========================================================
     * VENDEDOR / PROPIETARIO
     * ======================================================== */

    $vendedor_id = 0;

    if ($propiedad_id) {

        $vendedor = get_field(
            'cliente_propietario',
            $propiedad_id
        );

        $vendedor_id = crm_v3_get_related_id(
            $vendedor
        );
    }


    /* ========================================================
     * DATOS DEL COMPRADOR
     * ======================================================== */

    $presupuesto = null;


    if ($comprador_id) {

        $presupuesto = get_field(
            'presupuesto',
            $comprador_id
        );
    }


    /* ========================================================
     * DATOS DEL VENDEDOR
     * ======================================================== */

    $adeudo_hipoteca = null;


    if ($vendedor_id) {

        $adeudo_hipoteca = get_field(
            'adeudo_hipoteca',
            $vendedor_id
        );
    }


    /* ========================================================
     * DATOS DE LA PROPIEDAD
     * ======================================================== */

    $valor_catastral       = null;
    $precio_venta          = null;
    $adeudo_agua           = null;
    $adeudo_predial        = null;
    $costo_rehabilitacion  = null;
    $comision              = null;


    if ($propiedad_id) {

        $valor_catastral = get_field(
            'valor_catastral',
            $propiedad_id
        );

        $precio_venta = get_field(
            'precio_de_venta',
            $propiedad_id
        );

        $adeudo_agua = get_field(
            'adeudo_agua',
            $propiedad_id
        );

        $adeudo_predial = get_field(
            'adeudo_predial',
            $propiedad_id
        );

        $costo_rehabilitacion = get_field(
            'costo_rehabilitacion',
            $propiedad_id
        );

        $comision = get_field(
            'comision',
            $propiedad_id
        );
    }


    /* ========================================================
     * DATOS DE LA OPERACIÓN
     * ======================================================== */

    $valor_mercado = get_field(
        'valor_mercado',
        $operacion_id
    );

    $precio_cierre = get_field(
        'precio_de_cierre',
        $operacion_id
    );


    /* ========================================================
     * CONVERSIÓN NUMÉRICA PARA CÁLCULOS
     * ======================================================== */

    $presupuesto_num = is_numeric($presupuesto)
        ? (float) $presupuesto
        : 0;

    $adeudo_hipoteca_num = is_numeric($adeudo_hipoteca)
        ? (float) $adeudo_hipoteca
        : 0;

    $valor_catastral_num = is_numeric($valor_catastral)
        ? (float) $valor_catastral
        : 0;

    $precio_venta_num = is_numeric($precio_venta)
        ? (float) $precio_venta
        : 0;

    $adeudo_agua_num = is_numeric($adeudo_agua)
        ? (float) $adeudo_agua
        : 0;

    $adeudo_predial_num = is_numeric($adeudo_predial)
        ? (float) $adeudo_predial
        : 0;

    $costo_rehabilitacion_num = is_numeric(
        $costo_rehabilitacion
    )
        ? (float) $costo_rehabilitacion
        : 0;

    $comision_num = is_numeric($comision)
        ? (float) $comision
        : 0;

    $valor_mercado_num = is_numeric($valor_mercado)
        ? (float) $valor_mercado
        : 0;

    $precio_cierre_num = is_numeric($precio_cierre)
        ? (float) $precio_cierre
        : 0;



/* ========================================================
 * COMPARACIONES DE VALORES VALORES DE LA OPERACION
 * ======================================================== */

/*
 * Valor catastral vs precio de venta
 */
$comparacion_catastral_venta = null;

if ($valor_catastral_num > 0 && $precio_venta_num > 0) {

    $comparacion_catastral_venta =
        (
            (
                $precio_venta_num
                - $valor_catastral_num
            )
            / $valor_catastral_num
        )
        * 100;
}


/*
 * Valor catastral vs valor de mercado
 */
$comparacion_catastral_mercado = null;

if ($valor_catastral_num > 0 && $valor_mercado_num > 0) {

    $comparacion_catastral_mercado =
        (
            (
                $valor_mercado_num
                - $valor_catastral_num
            )
            / $valor_catastral_num
        )
        * 100;
}


/*
 * Precio de cierre vs valor de mercado
 */
$comparacion_cierre_mercado = null;

if ($valor_mercado_num > 0 && $precio_cierre_num > 0) {

    $comparacion_cierre_mercado =
        (
            (
                $precio_cierre_num
                - $valor_mercado_num
            )
            / $valor_mercado_num
        )
        * 100;
}




    /* ========================================================
     * COSTOS ADICIONALES CONOCIDOS
     * ======================================================== */

    $costos_adicionales =

        $adeudo_agua_num
        + $adeudo_predial_num
        + $costo_rehabilitacion_num
        + $comision_num;


    /* ========================================================
     * MONTO DESPUÉS DE COSTOS CONOCIDOS
     *
     * NO representa utilidad neta.
     * No incluye costo de adquisición.
     * ======================================================== */

    $monto_despues_costos = null;


    if ($precio_cierre_num > 0) {

        $monto_despues_costos =
            $precio_cierre_num
            - $costos_adicionales;
    }


    /* ========================================================
     * DIFERENCIA VENTA / CIERRE
     * ======================================================== */

    $diferencia_venta_cierre = null;


    if (
        $precio_venta_num > 0 &&
        $precio_cierre_num > 0
    ) {

        $diferencia_venta_cierre =
            $precio_cierre_num
            - $precio_venta_num;
    }


    /* ========================================================
     * DIFERENCIA MERCADO / CIERRE
     * ======================================================== */

    $diferencia_mercado_cierre = null;


    if (
        $valor_mercado_num > 0 &&
        $precio_cierre_num > 0
    ) {

        $diferencia_mercado_cierre =
            $precio_cierre_num
            - $valor_mercado_num;
    }


    /* ========================================================
     * DESVIACIÓN VS MERCADO
     * ======================================================== */

    $desviacion_mercado = null;


    if (
        $valor_mercado_num > 0 &&
        $precio_cierre_num > 0
    ) {

        $desviacion_mercado =
            (
                (
                    $precio_cierre_num
                    - $valor_mercado_num
                )
                / $valor_mercado_num
            )
            * 100;
    }


/* ========================================================
 * MONTO A RECIBIR
 *
 * Precio de cierre menos costos conocidos
 * y menos adeudo hipotecario.
 * ======================================================== */

$monto_a_recibir = null;

if ($monto_despues_costos !== null) {

    $monto_a_recibir =
        $monto_despues_costos
        - $adeudo_hipoteca_num;
}


    /* ========================================================
     * MARGEN SOBRE PRECIO DE CIERRE
     * ======================================================== */

    $margen_operativo = null;


    if (
        $precio_cierre_num > 0 &&
        $monto_despues_costos !== null
    ) {

        $margen_operativo =
            (
                $monto_despues_costos
                / $precio_cierre_num
            )
            * 100;
    }


    /* ========================================================
     * FUNCIÓN LOCAL PARA MOSTRAR DINERO
     *
     * Mantiene "—" cuando el dato original no existe.
     * ======================================================== */

    $mostrar_dinero = function ($valor) {

        if (
            $valor === null ||
            $valor === ''
        ) {
            return '—';
        }

        return crm_v3_format_money(
            $valor
        );
    };


    ?>

    <div class="crm-v3-analisis-financiero">


        <!-- ==================================================
             TÍTULO
             ================================================== -->

        <div class="crm-v3-financiero-title">
            ANÁLISIS FINANCIERO CLIENTE VENDEDOR
        </div>


        <!-- ==================================================
                     VALORES DE LA OPERACIÓN
             ================================================== -->

        <div class="crm-v3-financiero-section">

            <div class="crm-v3-financiero-section-title">
                VALORES DE LA OPERACIÓN
            </div>


            <div class="crm-v3-financiero-grid">


                <div class="crm-v3-financiero-item">

                    <span>
                        Valor catastral
                    </span>

                    <strong>
                        <?php echo esc_html(
                            $mostrar_dinero(
                                $valor_catastral
                            )
                        ); ?>
                    </strong>

                </div>


                <div class="crm-v3-financiero-item">

                    <span>
                        Valor de mercado
                    </span>

                    <strong>
                        <?php echo esc_html(
                            $mostrar_dinero(
                                $valor_mercado
                            )
                        ); ?>
                    </strong>

                </div>
                
                <div class="crm-v3-financiero-item">

                    <span>
                        Precio de venta
                    </span>

                    <strong>
                        <?php echo esc_html(
                            $mostrar_dinero(
                                $precio_venta
                            )
                        ); ?>
                    </strong>

                </div>

                <div class="crm-v3-financiero-item">

                    <span>
                        Precio de cierre
                    </span>

                    <strong>
                        <?php echo esc_html(
                            $mostrar_dinero(
                                $precio_cierre
                            )
                        ); ?>
                    </strong>

                </div>


            </div>

        </div>


            </div>


<!-- ==================================================
                COMPARACIONES DE VALORES
================================================== -->

            <div class="crm-v3-financiero-comparaciones">

                <div class="crm-v3-financiero-comparacion">

                    <span>
                        Catastral vs Mercado
                    </span>

                    <strong>

                        <?php
                        echo esc_html(
                            $comparacion_catastral_mercado !== null
                                ? (
                                    $comparacion_catastral_mercado >= 0
                                        ? '+'
                                        : ''
                                )
                                . number_format(
                                    $comparacion_catastral_mercado,
                                    2
                                )
                                . '%'
                                : '—'
                        );
                        ?>

                    </strong>

                </div>


                <div class="crm-v3-financiero-comparacion">

                    <span>
                        Catastral vs Venta
                    </span>

                    <strong>

                        <?php
                        echo esc_html(
                            $comparacion_catastral_venta !== null
                                ? (
                                    $comparacion_catastral_venta >= 0
                                        ? '+'
                                        : ''
                                )
                                . number_format(
                                    $comparacion_catastral_venta,
                                    2
                                )
                                . '%'
                                : '—'
                        );
                        ?>

                    </strong>

                </div>


                <div class="crm-v3-financiero-comparacion">

                    <span>
                        Mercado vs Cierre
                    </span>

                    <strong>

                        <?php
                        echo esc_html(
                            $comparacion_cierre_mercado !== null
                                ? (
                                    $comparacion_cierre_mercado >= 0
                                        ? '+'
                                        : ''
                                )
                                . number_format(
                                    $comparacion_cierre_mercado,
                                    2
                                )
                                . '%'
                                : '—'
                        );
                        ?>

                    </strong>

                </div>


            </div>




        <!-- ==================================================
             COSTOS CONOCIDOS
             ================================================== -->

        <div class="crm-v3-financiero-section">

            <div class="crm-v3-financiero-section-title">
                COSTOS CONOCIDOS
            </div>


            <div class="crm-v3-financiero-grid">


                <div class="crm-v3-financiero-item">

                    <span>
                        Adeudo agua
                    </span>

                    <strong>
                        <?php echo esc_html(
                            $mostrar_dinero(
                                $adeudo_agua
                            )
                        ); ?>
                    </strong>

                </div>


                <div class="crm-v3-financiero-item">

                    <span>
                        Adeudo predial
                    </span>

                    <strong>
                        <?php echo esc_html(
                            $mostrar_dinero(
                                $adeudo_predial
                            )
                        ); ?>
                    </strong>

                </div>


                <div class="crm-v3-financiero-item">

                    <span>
                        Rehabilitación
                    </span>

                    <strong>
                        <?php echo esc_html(
                            $mostrar_dinero(
                                $costo_rehabilitacion
                            )
                        ); ?>
                    </strong>

                </div>


                <div class="crm-v3-financiero-item">

                    <span>
                        Comisión
                    </span>

                    <strong>
                        <?php echo esc_html(
                            $mostrar_dinero(
                                $comision
                            )
                        ); ?>
                    </strong>

                </div>


                <div class="crm-v3-financiero-item crm-v3-financiero-total">

                    <span>
                        Costos adicionales conocidos
                    </span>

                    <strong>
                        <?php echo esc_html(
                            crm_v3_format_money(
                                $costos_adicionales
                            )
                        ); ?>
                    </strong>

                </div>


            </div>

        </div>


<!-- ==================================================
     SITUACIÓN DEL CLIENTE VENDEDOR
     ================================================== -->

<div class="crm-v3-financiero-section">

    <div class="crm-v3-financiero-section-title">
        SITUACIÓN DEL CLIENTE
    </div>


    <div class="crm-v3-financiero-grid">


        <!-- PRECIO DE CIERRE -->

        <div class="crm-v3-financiero-item">

            <span>
                Precio de cierre
            </span>

            <strong>
                <?php echo esc_html(
                    $mostrar_dinero(
                        $precio_cierre
                    )
                ); ?>
            </strong>

        </div>


        <!-- HIPOTECA -->

        <div class="crm-v3-financiero-item">

            <span>
                Hipoteca
            </span>

            <strong>
                <?php echo esc_html(
                    $mostrar_dinero(
                        $adeudo_hipoteca
                    )
                ); ?>
            </strong>

        </div>


        <!-- GASTOS CONOCIDOS -->

        <div class="crm-v3-financiero-item">

            <span>
                Gastos conocidos
            </span>

            <strong>
                <?php echo esc_html(
                    $mostrar_dinero(
                        $costos_adicionales
                    )
                ); ?>
            </strong>

        </div>


        <!-- MONTO A RECIBIR -->

        <div class="crm-v3-financiero-item">

            <span>
                Monto a recibir
            </span>

            <strong>
                <?php echo esc_html(
                    $mostrar_dinero(
                        $monto_a_recibir
                    )
                ); ?>
            </strong>

        </div>


    </div>

</div>


        <!-- ==================================================
             RESULTADO PRELIMINAR
             ================================================== -->

        <div class="crm-v3-financiero-section">

            <div class="crm-v3-financiero-section-title">
                RESULTADO PRELIMINAR
            </div>


            <div class="crm-v3-financiero-grid">


                <div class="crm-v3-financiero-item<?php
    echo (
        $diferencia_venta_cierre !== null &&
        $diferencia_venta_cierre < 0
    )
        ? ' is-negative'
        : '';
?>">

                    <span>
                        Diferencia venta / cierre
                    </span>

                    <strong>
                        <?php
                        echo esc_html(
                            $diferencia_venta_cierre !== null
                                ? crm_v3_format_money(
                                    $diferencia_venta_cierre
                                )
                                : '—'
                        );
                        ?>
                    </strong>

                </div>


                <div class="crm-v3-financiero-item<?php
    echo (
        $diferencia_mercado_cierre !== null &&
        $diferencia_mercado_cierre < 0
    )
        ? ' is-negative'
        : '';
?>">

                    <span>
                        Diferencia mercado / cierre
                    </span>

                    <strong>
                        <?php
                        echo esc_html(
                            $diferencia_mercado_cierre !== null
                                ? crm_v3_format_money(
                                    $diferencia_mercado_cierre
                                )
                                : '—'
                        );
                        ?>
                    </strong>

                </div>


                <div class="crm-v3-financiero-item<?php
    echo (
        $monto_despues_costos !== null &&
        $monto_despues_costos < 0
    )
        ? ' is-negative'
        : '';
?>">

                    <span>
                        Monto después de costos conocidos
                    </span>

                    <strong>
                        <?php
                        echo esc_html(
                            $monto_despues_costos !== null
                                ? crm_v3_format_money(
                                    $monto_despues_costos
                                )
                                : '—'
                        );
                        ?>
                    </strong>

                </div>


                <div class="crm-v3-financiero-item<?php
    echo (
        $desviacion_mercado !== null &&
        $desviacion_mercado < 0
    )
        ? ' is-negative'
        : '';
?>">

                    <span>
                        Desviación vs mercado
                    </span>

                    <strong>

                        <?php
                        echo esc_html(
                            $desviacion_mercado !== null
                                ? number_format(
                                    $desviacion_mercado,
                                    2
                                ) . '%'
                                : '—'
                        );
                        ?>

                    </strong>

                </div>


                <div class="crm-v3-financiero-item<?php
    echo (
        $margen_operativo !== null &&
        $margen_operativo < 0
    )
        ? ' is-negative'
        : '';
?>">

                    <span>
                        Margen sobre cierre
                    </span>

                    <strong>

                        <?php
                        echo esc_html(
                            $margen_operativo !== null
                                ? number_format(
                                    $margen_operativo,
                                    2
                                ) . '%'
                                : '—'
                        );
                        ?>

                    </strong>

                </div>


            </div>

        </div>


    </div>

    <?php
}


