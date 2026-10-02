<?php
/**
 * CRM V3 - PIPELINE DE OPERACIÓN
 *
 * El pipeline representa visualmente el avance de una operación.
 * No crea ni modifica datos de la operación.
 */


/* ============================================================
 * RENDER PIPELINE DE UNA OPERACIÓN
 * ============================================================ */

function crm_v3_pipeline_operacion($operacion_id) {

    $operacion_id = (int) $operacion_id;

    if (!$operacion_id) {
        return;
    }


    /* --------------------------------------------------------
     * DATOS DE LA OPERACIÓN
     * -------------------------------------------------------- */

    $comprador = get_field(
        'comprador',
        $operacion_id
    );

    $propiedad = get_field(
        'propiedad',
        $operacion_id
    );

    $expediente_completo = get_field(
        'expediente_completo',
        $operacion_id
    );

    $valuador = get_field(
        'valuador',
        $operacion_id
    );

    $valor_mercado = get_field(
        'valor_mercado',
        $operacion_id
    );

    $institucion_financiera = get_field(
        'institucion_financiera',
        $operacion_id
    );

    $notaria = get_field(
        'notaria',
        $operacion_id
    );

    $fecha_cierre = get_field(
        'fecha_cierre',
        $operacion_id
    );

   $estatus_operacion = get_field(
        'estatus_de_operecion',
        $operacion_id
    );


    /* --------------------------------------------------------
     * DETERMINAR ETAPAS COMPLETADAS
     * -------------------------------------------------------- */

    /*
     * Una operación inicia con comprador + propiedad.
     */
    $comprador_completo =
        !empty($comprador) &&
        !empty($propiedad);


    /*
     * Expediente completo.
     */
    $expediente_completo =
        (bool) $expediente_completo;


    /*
     * Valuador asignado.
     */
    $valuador_completo =
        !empty($valuador);


    /*
     * Institución financiera.
     */
    $institucion_completa =
        !empty($institucion_financiera);


    /*
     * Notaría asignada.
     */
    $notaria_completa =
        !empty($notaria);


    /*
     * Fecha de cierre.
     */
    $fecha_cierre_completa =
        !empty($fecha_cierre);

/*
     * Estatus.
     *
     * No consideramos "En proceso" como una etapa terminada.
     * Se considera completada cuando existe un estatus
     * diferente de vacío y la operación ha llegado a esa etapa.
     */
    $estatus_completo =
        !empty($estatus_operacion) &&
        (
            $estatus_operacion === 'cerrada' ||
            $estatus_operacion === 'perdida'
        );


   


    /* --------------------------------------------------------
     * ETAPAS
     * -------------------------------------------------------- */

    $etapas = array(

        array(
            'nombre'    => 'Comprador',
            'completo'  => $comprador_completo,
        ),

        array(
            'nombre'    => 'Expediente completo',
            'completo'  => $expediente_completo,
        ),

        array(
            'nombre'    => 'Valuador',
            'completo'  => $valuador_completo,
        ),

        array(
            'nombre'    => 'Institución financiera',
            'completo'  => $institucion_completa,
        ),

        array(
            'nombre'    => 'Notaria',
            'completo'  => $notaria_completa,
        ),

        array(
            'nombre'    => 'Fecha de cierre',
            'completo'  => $fecha_cierre_completa,
        ),

        array(
            'nombre'    => 'Estatus',
            'completo'  => $estatus_completo,
        ),

        

    );


    /* --------------------------------------------------------
     * ENCONTRAR PRIMERA ETAPA PENDIENTE
     * -------------------------------------------------------- */

    $indice_actual = null;

    foreach ($etapas as $indice => $etapa) {

        if (!$etapa['completo']) {

            $indice_actual = $indice;

            break;
        }
    }


    /* --------------------------------------------------------
     * RENDER
     * -------------------------------------------------------- */

    echo '<div class="crm-v3-pipeline">';


    foreach ($etapas as $indice => $etapa) {

        $clases = array(
            'crm-v3-pipeline-step'
        );


        /*
         * Etapa completada.
         */
        if ($etapa['completo']) {

            $clases[] = 'is-complete';

        }
        /*
         * Primera etapa pendiente = etapa actual.
         */
        elseif ($indice_actual === $indice) {

            $clases[] = 'is-current';

        }
        /*
         * Etapas todavía pendientes.
         */
        else {

            $clases[] = 'is-pending';

        }


        echo '<div class="' . esc_attr(
            implode(' ', $clases)
        ) . '">';

        echo '<span>';

        echo esc_html(
            $etapa['nombre']
        );

        echo '</span>';

        echo '</div>';
    }


        echo '</div>';


    /*
     * EDITOR DE DATOS DE LA OPERACIÓN
     *
     * Se muestra inmediatamente debajo
     * del Pipeline de esta operación.
     */

    crm_v3_pipeline_editor_operacion(
        $operacion_id
    );

}




/* ============================================================
 * EDITOR DE DATOS DE OPERACIÓN
 * ============================================================ */

function crm_v3_pipeline_editor_operacion($operacion_id) {

    if (!$operacion_id) {
        return;
    }

    /*
     * DATOS ACTUALES
     */

    $expediente_completo = get_field(
        'expediente_completo',
        $operacion_id
    );

    $valuador = get_field(
        'valuador',
        $operacion_id
    );

    $valor_mercado = get_field(
        'valor_mercado',
        $operacion_id
    );

    $institucion_financiera = get_field(
        'institucion_financiera',
        $operacion_id
    );

    $notaria = get_field(
        'notaria',
        $operacion_id
    );

    $fecha_cierre = get_field(
        'fecha_cierre',
        $operacion_id
    );

   $estatus_operacion = get_field(
        'estatus_de_operecion',
        $operacion_id
    );


    /*
     * NORMALIZAR VALUADOR
     */

    $valuador_id = 0;

    if (is_object($valuador) && isset($valuador->ID)) {

        $valuador_id = (int) $valuador->ID;

    } elseif (is_numeric($valuador)) {

        $valuador_id = (int) $valuador;

    }


    /*
     * NORMALIZAR NOTARIA
     */

    $notaria_id = 0;

    if (is_object($notaria) && isset($notaria->ID)) {

        $notaria_id = (int) $notaria->ID;

    } elseif (is_numeric($notaria)) {

        $notaria_id = (int) $notaria;

    }


    /*
     * NORMALIZAR INSTITUCIÓN
     */

    $institucion_id = 0;

    if (is_array($institucion_financiera)) {

        if (!empty($institucion_financiera)) {
            $institucion_id = (int) reset(
                $institucion_financiera
            );
        }

    } elseif (is_numeric($institucion_financiera)) {

        $institucion_id = (int) $institucion_financiera;

    }


    /*
     * FECHA DE CIERRE
     *
     * ACF guarda normalmente Ymd.
     * El campo visual utiliza Y-m-d.
     */

    $fecha_cierre_input = '';

    if (!empty($fecha_cierre)) {

        $fecha_obj = DateTime::createFromFormat(
            'd/m/Y',
            $fecha_cierre
        );

        if ($fecha_obj) {

            $fecha_cierre_input = $fecha_obj->format('Y-m-d');

        } elseif (
            preg_match(
                '/^\d{8}$/',
                $fecha_cierre
            )
        ) {

            $fecha_obj = DateTime::createFromFormat(
                'Ymd',
                $fecha_cierre
            );

            if ($fecha_obj) {

                $fecha_cierre_input = $fecha_obj->format('Y-m-d');

            }

        } elseif (
            preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $fecha_cierre
            )
        ) {

            $fecha_cierre_input = $fecha_cierre;

        }

    }


    /*
     * CATÁLOGO DE VALUADORES
     */

    $valuadores = get_posts(array(
        'post_type'      => 'valuadores',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));


    /*
     * CATÁLOGO DE NOTARÍAS
     */

    $notarias = get_posts(array(
        'post_type'      => 'notarias',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));


    /*
     * CATÁLOGO INSTITUCIONES FINANCIERAS
     */

    $instituciones = get_terms(array(
        'taxonomy'   => 'origen-de-recurso',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ));

    ?>


</================== DATOS DE OPERACION FICHA DEL CLIENTE ===============/>

        <div class="crm-v3-operacion-editor crm-v3-operacion-editor-layout">

        <div class="crm-v3-operacion-editor-left">

        <div class="crm-v3-operacion-editor-panel">

        <div class="crm-v3-operacion-editor-title">
            DATOS DE OPERACIÓN
        </div>


        <form
            method="post"
            class="crm-v3-operacion-editor-form"
        >
        <input
    type="hidden"
    name="cliente_id"
    value="<?php echo esc_attr(
        isset($_GET['cliente_id'])
            ? absint($_GET['cliente_id'])
            : 0
    ); ?>"
>


            <?php
            wp_nonce_field(
                'crm_v3_actualizar_operacion',
                'crm_v3_operacion_nonce'
            );
            ?>

            <input
                type="hidden"
                name="crm_v3_operacion_id"
                value="<?php echo esc_attr($operacion_id); ?>"
            >


            <!-- EXPEDIENTE -->

            <div class="crm-v3-operacion-editor-row">

                <label>
                    Expediente completo
                </label>

                <select name="expediente_completo">

                    <option
                        value="0"
                        <?php selected(
                            $expediente_completo,
                            0
                        ); ?>
                    >
                        No
                    </option>

                    <option
                        value="1"
                        <?php selected(
                            $expediente_completo,
                            1
                        ); ?>
                    >
                        Sí
                    </option>

                </select>

            </div>


            <!-- VALUADOR -->

            <div class="crm-v3-operacion-editor-row">

                <label>
                    Valuador
                </label>

                <select name="valuador">

                    <option value="">
                        Seleccionar valuador
                    </option>

                    <?php foreach ($valuadores as $item): ?>

                        <option
                            value="<?php echo esc_attr($item->ID); ?>"
                            <?php selected(
                                $valuador_id,
                                $item->ID
                            ); ?>
                        >
                            <?php echo esc_html($item->post_title); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <!-- VALOR MERCADO -->

            <div class="crm-v3-operacion-editor-row">

                <label>
                    Valor mercado
                </label>

                <input
                    type="number"
                    name="valor_mercado"
                    value="<?php echo esc_attr(
                        $valor_mercado
                    ); ?>"
                    min="0"
                    step="0.01"
                    placeholder="Capturar valor"
                >

            </div>


            <!-- INSTITUCIÓN -->

            <div class="crm-v3-operacion-editor-row">

                <label>
                    Institución financiera
                </label>

                <select name="institucion_financiera">

                    <option value="">
                        Seleccionar institución
                    </option>

                    <?php
                    if (!is_wp_error($instituciones) && !empty($instituciones)):
                    ?>

                        <?php foreach ($instituciones as $term): ?>

                            <option
                                value="<?php echo esc_attr($term->term_id); ?>"
                                <?php selected(
                                    $institucion_id,
                                    $term->term_id
                                ); ?>
                            >
                                <?php echo esc_html($term->name); ?>
                            </option>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </select>

            </div>


            <!-- NOTARIA -->

            <div class="crm-v3-operacion-editor-row">

                <label>
                    Notaria
                </label>

                <select name="notaria">

                    <option value="">
                        Seleccionar notaria
                    </option>

                    <?php foreach ($notarias as $item): ?>

                        <option
                            value="<?php echo esc_attr($item->ID); ?>"
                            <?php selected(
                                $notaria_id,
                                $item->ID
                            ); ?>
                        >
                            <?php echo esc_html($item->post_title); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- FECHA CIERRE -->

            <div class="crm-v3-operacion-editor-row">

                <label>
                    Fecha de cierre
                </label>

                <input
                    type="date"
                    name="fecha_cierre"
                    value="<?php echo esc_attr(
                        $fecha_cierre_input
                    ); ?>"
                >

            </div>


<!-- ESTATUS -->

            <div class="crm-v3-operacion-editor-row">

                <label>
                    Estatus
                </label>

                <select name="estatus_de_operecion">

                    <option
                        value=""
                        <?php selected(
                            $estatus_operacion,
                            ''
                        ); ?>
                    >
                        Seleccionar
                    </option>

                    <option
                        value="en_proceso"
                        <?php selected(
                            $estatus_operacion,
                            'en_proceso'
                        ); ?>
                    >
                        En proceso
                    </option>

                    <option
                        value="perdida"
                        <?php selected(
                            $estatus_operacion,
                            'perdida'
                        ); ?>
                    >
                        Perdida
                    </option>

                    <option
                        value="cerrada"
                        <?php selected(
                            $estatus_operacion,
                            'cerrada'
                        ); ?>
                    >
                        Cerrada
                    </option>

                </select>

            </div>




            <div class="crm-v3-operacion-editor-actions">

                <button
                    type="submit"
                    name="crm_v3_actualizar_operacion"
                    class="crm-v3-operacion-editor-save"
                >
                    Guardar cambios
                </button>           

                </form>

        </div>


        <!-- =====================================================
             ANÁLISIS FINANCIERO
             ===================================================== -->

        <div class="crm-v3-operacion-financiero">

            <?php

            if (function_exists('crm_v3_analisis_financiero')) {

                crm_v3_analisis_financiero(
                    $operacion_id
                );

            }

            ?>

        </div>

    </div>


    <!-- =====================================================
         CÁLCULO DE ISR
         ===================================================== -->

    <div class="crm-v3-operacion-isr">

        <?php

        if (function_exists('crm_v3_isr_operacion')) {

            crm_v3_isr_operacion(
                $operacion_id
            );

        }

        ?>

    </div>

</div>

<?php
}


/* ============================================================
 * GUARDAR DATOS DE OPERACIÓN DESDE LA FICHA
 * ============================================================ */

function crm_v3_actualizar_operacion_desde_ficha() {

    if (
        !isset($_POST['crm_v3_actualizar_operacion'])
    ) {
        return;
    }

    if (
        !isset($_POST['crm_v3_operacion_nonce'])
        ||
        !wp_verify_nonce(
            $_POST['crm_v3_operacion_nonce'],
            'crm_v3_actualizar_operacion'
        )
    ) {
        return;
    }

    if (
        !current_user_can('edit_posts')
    ) {
        return;
    }

    $operacion_id = isset(
        $_POST['crm_v3_operacion_id']
    )
        ? absint($_POST['crm_v3_operacion_id'])
        : 0;

    if (!$operacion_id) {
        return;
    }


    /*
     * EXPEDIENTE
     */

    $expediente_completo =
        isset($_POST['expediente_completo'])
            ? absint($_POST['expediente_completo'])
            : 0;

    update_field(
        'expediente_completo',
        $expediente_completo,
        $operacion_id
    );


    /*
     * VALUADOR
     */

    $valuador_id =
        isset($_POST['valuador'])
            ? absint($_POST['valuador'])
            : 0;

    update_field(
        'valuador',
        $valuador_id ?: false,
        $operacion_id
    );

    /*
     * VALOR DE MERCADO
     */

    $valor_mercado =
        isset($_POST['valor_mercado'])
            ? sanitize_text_field(
                $_POST['valor_mercado']
            )
            : '';

    if ($valor_mercado !== '') {

        $valor_mercado = (float) $valor_mercado;

        if ($valor_mercado >= 0) {

            update_field(
                'valor_mercado',
                $valor_mercado,
                $operacion_id
            );

        }

    } else {

        update_field(
            'valor_mercado',
            false,
            $operacion_id
        );

    }


    /*
     * INSTITUCIÓN FINANCIERA
     */

    $institucion_id =
        isset($_POST['institucion_financiera'])
            ? absint($_POST['institucion_financiera'])
            : 0;

    update_field(
        'institucion_financiera',
        $institucion_id ?: false,
        $operacion_id
    );


    /*
     * NOTARIA
     */

    $notaria_id =
        isset($_POST['notaria'])
            ? absint($_POST['notaria'])
            : 0;

    update_field(
        'notaria',
        $notaria_id ?: false,
        $operacion_id
    );


    /*
     * FECHA DE CIERRE
     */

    $fecha_cierre =
        isset($_POST['fecha_cierre'])
            ? sanitize_text_field(
                $_POST['fecha_cierre']
            )
            : '';

    if ($fecha_cierre) {

        $fecha_obj = DateTime::createFromFormat(
            'Y-m-d',
            $fecha_cierre
        );

        if ($fecha_obj) {

            update_field(
                'fecha_cierre',
                $fecha_obj->format('Ymd'),
                $operacion_id
            );

        }

    } else {

        update_field(
            'fecha_cierre',
            false,
            $operacion_id
        );

    }


/*
     * ESTATUS
     */

    $estatus =
        isset($_POST['estatus_de_operecion'])
            ? sanitize_text_field(
                $_POST['estatus_de_operecion']
            )
            : '';

    $estatus_permitidos = array(
        'en_proceso',
        'perdida',
        'cerrada',
    );

    if (
        $estatus === ''
        ||
        in_array(
            $estatus,
            $estatus_permitidos,
            true
        )
    ) {

        update_field(
            'estatus_de_operecion',
            $estatus,
            $operacion_id
        );

    }



   /*
 * REGRESAR A LA FICHA DEL CLIENTE
 */

$cliente_id = 0;


/*
 * REGRESAR AL CLIENTE DESDE CUYA FICHA
 * SE ESTÁ EDITANDO LA OPERACIÓN.
 */

$cliente_id = isset($_POST['cliente_id'])
    ? absint($_POST['cliente_id'])
    : 0;


/*
 * Construir URL de regreso a la ficha.
 */

if ($cliente_id) {

    $redirect_url = add_query_arg(
        array(
            'page'       => 'crm-ficha-cliente',
            'cliente_id' => $cliente_id,
        ),
        admin_url('admin.php')
    );

} else {

    $redirect_url = admin_url(
        'admin.php?page=crm-clientes'
    );

}


wp_safe_redirect(
    $redirect_url
);

exit;

}

add_action(
    'admin_init',
    'crm_v3_actualizar_operacion_desde_ficha'
);



