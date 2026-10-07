<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * ============================================================
 * ALERTAS DEL ANÁLISIS FINANCIERO
 * ============================================================
 *
 * Revisa los números de la operación y devuelve las situaciones
 * con las que hay que tener cuidado. Cada alerta indica en qué
 * tarjeta se muestra, su nivel ("rojo" = problema, "ambar" =
 * precaución), un título y la explicación que aparece al pasar
 * el mouse.
 *
 * Los textos fiscales son orientativos: la determinación final
 * la hace el notario.
 */
function crm_v3_financiero_alertas($d) {

    $alertas = array();

    $dinero = function ($valor) {
        return '$' . number_format((float) $valor, 2);
    };

    $avaluo    = (float) $d['valor_mercado'];
    $cierre    = (float) $d['precio_cierre'];
    $catastral = (float) $d['valor_catastral'];

    /*
     * 1. AVALÚO vs PRECIO (regla del 10%, art. 125 LISR)
     *
     * La ley compara cuánto excede el avalúo al precio pactado,
     * medido sobre el precio: (avalúo - precio) / precio.
     */
    if ($avaluo > 0 && $cierre > 0 && $avaluo > $cierre) {

        $diferencia = $avaluo - $cierre;
        $exceso     = ($diferencia / $cierre) * 100;

        if ($exceso > 10) {

            $alertas['mercado_cierre'] = array(
                'nivel'  => 'rojo',
                'titulo' => 'El valor de mercado supera al precio de cierre en más de 10%',
                'texto'  => array(
                    'El valor de mercado del avalúo (' . $dinero($avaluo) . ') es ' . number_format($exceso, 2) . '% mayor que el precio de cierre (' . $dinero($cierre) . '). El límite es 10%.',
                    'Cuando el valor del avalúo excede en más de 10% el precio pactado, el SAT considera TODA la diferencia como ingreso del comprador (art. 125 de la Ley del ISR). El notario le retiene 20% sobre esa diferencia (art. 132).',
                    'Diferencia: ' . $dinero($diferencia) . '. ISR estimado a cargo del comprador: ' . $dinero($diferencia * 0.20) . '.',
                    'Para no rebasar el límite, el precio tendría que ser de al menos ' . $dinero($avaluo / 1.10) . '.',
                    'Estimación orientativa: confírmala con el notario.',
                ),
            );

        } elseif ($exceso >= 8) {

            $alertas['mercado_cierre'] = array(
                'nivel'  => 'ambar',
                'titulo' => 'Cerca del límite de 10% entre valor de mercado y precio de cierre',
                'texto'  => array(
                    'El valor de mercado del avalúo (' . $dinero($avaluo) . ') es ' . number_format($exceso, 2) . '% mayor que el precio de cierre (' . $dinero($cierre) . '). El límite es 10%.',
                    'Si la diferencia pasa de 10%, el SAT la considera ingreso del comprador y se le retiene 20% de ISR sobre toda la diferencia (arts. 125 y 132 de la Ley del ISR).',
                    'Precio mínimo para no rebasar el límite: ' . $dinero($avaluo / 1.10) . '.',
                ),
            );
        }
    }

    /*
     * 2. PRECIO DE CIERRE contra avalúo y contra valor catastral
     */
    if ($cierre > 0 && $avaluo > 0 && $cierre > $avaluo) {

        $alertas['precio_cierre'] = array(
            'nivel'  => 'ambar',
            'titulo' => 'El precio de cierre es mayor que el valor de mercado',
            'texto'  => array(
                'El precio de cierre (' . $dinero($cierre) . ') supera al valor de mercado del avalúo (' . $dinero($avaluo) . ') por ' . $dinero($cierre - $avaluo) . '.',
                'Si el comprador usa crédito, el banco, Infonavit o Fovissste prestan sobre el valor más bajo. Esa diferencia la tendría que cubrir el comprador con recursos propios.',
            ),
        );

    } elseif ($cierre > 0 && $catastral > 0 && $cierre < $catastral) {

        $alertas['precio_cierre'] = array(
            'nivel'  => 'ambar',
            'titulo' => 'El precio es menor que el valor catastral',
            'texto'  => array(
                'El precio de cierre (' . $dinero($cierre) . ') está por debajo del valor catastral (' . $dinero($catastral) . ').',
                'El impuesto de transmisión patrimonial y los derechos se calculan sobre el valor más alto (precio, avalúo o catastral), así que no bajan aunque baje el precio.',
                'Un precio por debajo del catastral también puede provocar una revisión de la autoridad.',
            ),
        );
    }

    /*
     * 3. ADEUDOS DE LA PROPIEDAD
     */
    $adeudos = array(
        'adeudo_agua'    => 'agua',
        'adeudo_predial' => 'predial',
    );

    foreach ($adeudos as $clave => $nombre) {

        if ((float) $d[$clave] > 0) {

            $alertas[$clave] = array(
                'nivel'  => 'ambar',
                'titulo' => 'Adeudo de ' . $nombre . ' pendiente',
                'texto'  => array(
                    'La propiedad debe ' . $dinero($d[$clave]) . ' de ' . $nombre . '.',
                    'Para escriturar, el notario pide la constancia de no adeudo. Hay que liquidarlo antes de la firma o acordar que se descuente del precio.',
                ),
            );
        }
    }

    /*
     * 4. MONTO QUE RECIBE EL VENDEDOR
     */
    if ($d['monto_a_recibir'] !== null && (float) $d['monto_a_recibir'] < 0) {

        $alertas['monto_a_recibir'] = array(
            'nivel'  => 'rojo',
            'titulo' => 'El precio no alcanza para cubrir hipoteca y gastos',
            'texto'  => array(
                'Después de pagar la hipoteca y los gastos conocidos, al vendedor le faltarían ' . $dinero(abs((float) $d['monto_a_recibir'])) . '.',
                'Tendría que aportar esa cantidad para poder liberar la hipoteca y escriturar, o renegociar el precio.',
            ),
        );
    }

    return $alertas;
}


/**
 * Clases y atributos de una tarjeta que tiene alerta.
 * Se escribe dentro de class="…" de la tarjeta.
 */
function crm_v3_financiero_alerta_clase($alertas, $clave) {

    return isset($alertas[$clave])
        ? ' crm-v3-alerta crm-v3-alerta-' . $alertas[$clave]['nivel']
        : '';
}


/**
 * Atributos extra de la tarjeta con alerta (para poder llegar a
 * ella con el teclado y para lectores de pantalla).
 */
function crm_v3_financiero_alerta_atributos($alertas, $clave) {

    if (!isset($alertas[$clave])) {
        return '';
    }

    return ' tabindex="0" title="' . esc_attr(
        $alertas[$clave]['titulo'] . '. ' . implode(' ', $alertas[$clave]['texto'])
    ) . '"';
}


/**
 * Nota de la alerta: es lo que se muestra al pasar el mouse.
 */
function crm_v3_financiero_alerta_nota($alertas, $clave) {

    if (!isset($alertas[$clave])) {
        return;
    }

    $alerta = $alertas[$clave];

    echo '<div class="crm-v3-alerta-nota" hidden>';
    echo '<b>' . esc_html($alerta['titulo']) . '</b>';

    foreach ($alerta['texto'] as $parrafo) {
        echo '<p>' . esc_html($parrafo) . '</p>';
    }

    echo '</div>';
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

        $adeudo_predial = crm_v3_get_adeudo_predial(
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

    // Es el mismo cálculo que "precio de cierre vs valor de mercado".
    $desviacion_mercado = $comparacion_cierre_mercado;

    /*
     * MERCADO vs CIERRE (tarjeta de comparación)
     *
     * Cuánto excede el valor de mercado del avalúo al precio de
     * cierre, medido contra el precio. Es la forma en que la ley
     * mide el límite del 10% (art. 125 LISR), así la tarjeta y su
     * alerta muestran el mismo número.
     */
    $mercado_sobre_cierre = null;

    if ($valor_mercado_num > 0 && $precio_cierre_num > 0) {

        $mercado_sobre_cierre =
            (($valor_mercado_num - $precio_cierre_num) / $precio_cierre_num) * 100;
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

    $alertas = crm_v3_financiero_alertas(array(
        'valor_mercado'   => $valor_mercado_num,
        'precio_cierre'   => $precio_cierre_num,
        'valor_catastral' => $valor_catastral_num,
        'adeudo_agua'     => $adeudo_agua_num,
        'adeudo_predial'  => $adeudo_predial_num,
        'monto_a_recibir' => $monto_a_recibir,
    ));

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

                <div class="crm-v3-financiero-item<?php echo esc_attr(crm_v3_financiero_alerta_clase($alertas, 'precio_cierre')); ?>"<?php echo crm_v3_financiero_alerta_atributos($alertas, 'precio_cierre'); ?>>

                    <?php crm_v3_financiero_alerta_nota($alertas, 'precio_cierre'); ?>


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


                <div class="crm-v3-financiero-comparacion<?php echo esc_attr(crm_v3_financiero_alerta_clase($alertas, 'mercado_cierre')); ?>"<?php echo crm_v3_financiero_alerta_atributos($alertas, 'mercado_cierre'); ?>>

                    <?php crm_v3_financiero_alerta_nota($alertas, 'mercado_cierre'); ?>


                    <span>
                        Mercado vs Cierre
                    </span>

                    <strong>

                        <?php
                        echo esc_html(
                            $mercado_sobre_cierre !== null
                                ? (
                                    $mercado_sobre_cierre >= 0
                                        ? '+'
                                        : ''
                                )
                                . number_format(
                                    $mercado_sobre_cierre,
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


                <div class="crm-v3-financiero-item<?php echo esc_attr(crm_v3_financiero_alerta_clase($alertas, 'adeudo_agua')); ?>"<?php echo crm_v3_financiero_alerta_atributos($alertas, 'adeudo_agua'); ?>>

                    <?php crm_v3_financiero_alerta_nota($alertas, 'adeudo_agua'); ?>


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


                <div class="crm-v3-financiero-item<?php echo esc_attr(crm_v3_financiero_alerta_clase($alertas, 'adeudo_predial')); ?>"<?php echo crm_v3_financiero_alerta_atributos($alertas, 'adeudo_predial'); ?>>

                    <?php crm_v3_financiero_alerta_nota($alertas, 'adeudo_predial'); ?>


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

        <div class="crm-v3-financiero-item<?php echo esc_attr(crm_v3_financiero_alerta_clase($alertas, 'monto_a_recibir')); ?>"<?php echo crm_v3_financiero_alerta_atributos($alertas, 'monto_a_recibir'); ?>>

                    <?php crm_v3_financiero_alerta_nota($alertas, 'monto_a_recibir'); ?>


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

    <?php
}


