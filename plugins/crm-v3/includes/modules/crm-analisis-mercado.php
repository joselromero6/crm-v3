<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * ============================================================
 * CRM V3 — ANÁLISIS COMPARATIVO DE MERCADO
 * ============================================================
 *
 * Módulo independiente del análisis financiero.
 *
 * Objetivo:
 * - Analizar propiedades comparables.
 * - Filtrar por colonia / fraccionamiento.
 * - Filtrar por tipo de propiedad.
 * - Tomar hasta 5 propiedades recientes.
 * - Comparar valores reales capturados en ACF.
 *
 */


/**
 * ============================================================
 * RENDER — ANÁLISIS DE MERCADO
 * ============================================================
 */

function crm_v3_analisis_mercado_render() {
    ?>

    <div class="crm-v3-analisis-mercado">

        <!-- ==================================================
             ENCABEZADO
             ================================================== -->

        <div class="crm-v3-mercado-header">

            Comparación de propiedades similares de la zona.

        </div>


        <!-- ==================================================
             FILTROS
             ================================================== -->

        <div class="crm-v3-mercado-filtros">

            <div class="crm-v3-mercado-filtro">

                <label for="crm-v3-mercado-colonia">
                    Colonia / Fraccionamiento
                </label>

                <select
    id="crm-v3-mercado-colonia"
    name="crm_v3_mercado_colonia"
>

    <option value="">
        Seleccionar colonia o fraccionamiento
    </option>

    <?php
    $colonias = get_terms([
        'taxonomy'   => 'fraccionamiento-o-colonia',
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    if (
        !is_wp_error($colonias) &&
        !empty($colonias)
    ) :

        foreach ($colonias as $colonia) :
            ?>

            <option
                value="<?php echo esc_attr($colonia->term_id); ?>"
            >
                <?php echo esc_html($colonia->name); ?>
            </option>

            <?php
        endforeach;

    endif;
    ?>

</select>

            </div>


            <div class="crm-v3-mercado-filtro">

                <label for="crm-v3-mercado-tipo">

                    Tipo de propiedad

                </label>

                <select
                    id="crm-v3-mercado-tipo"
                    name="crm_v3_mercado_tipo"
                    disabled
                >

                    <option value="">
                        Seleccionar tipo de propiedad
                    </option>

                </select>

            </div>


            <div class="crm-v3-mercado-filtro crm-v3-mercado-precio">

                <label for="crm-v3-mercado-precio-evaluar">

                    Precio de venta a evaluar

                </label>

                <input
                    type="number"
                    id="crm-v3-mercado-precio-evaluar"
                    name="crm_v3_mercado_precio_evaluar"
                    min="0"
                    step="1000"
                    placeholder="Ingresa precio"
                >

            </div>

        </div>


        <!-- ==================================================
             RESULTADO
             ================================================== -->

        <div
            id="crm-v3-mercado-resultado"
            class="crm-v3-mercado-resultado"
        >

            <div class="crm-v3-mercado-placeholder">

                Selecciona una colonia y un tipo de propiedad
                para iniciar el análisis.

            </div>

        </div>


    </div>

    <?php
}