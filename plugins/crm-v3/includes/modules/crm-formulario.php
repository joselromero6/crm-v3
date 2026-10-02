<?php
/**
 * CRM V3 - Nuevos Registros
 *
 * Registro de clientes mediante ACF.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Preparar ACF Frontend Form.
 */
/**
 * Preparar ACF Frontend Forms.
 */
function crm_v3_formulario_acf_head() {

    if (
        ! isset( $_GET['page'] ) ||
        $_GET['page'] !== 'crm-nuevos-registros'
    ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( function_exists( 'acf_form_head' ) ) {
        acf_form_head();
    }
}

add_action(
    'admin_init',
    'crm_v3_formulario_acf_head',
    1
);



/**
 * Mostrar Origen lead como selector únicamente
 * en Nuevos Registros.
 */
function crm_v3_formulario_origen_lead_selector( $field ) {

    if (
        ! is_admin() ||
        ! isset( $_GET['page'] ) ||
        $_GET['page'] !== 'crm-nuevos-registros'
    ) {
        return $field;
    }

    $field['field_type'] = 'select';
    $field['multiple']   = 0;
    $field['allow_null'] = 1;

    return $field;
}

add_filter(
    'acf/prepare_field/name=origen_lead',
    'crm_v3_formulario_origen_lead_selector'
);

add_filter(
    'acf/prepare_field/name=tipo_de_cartera',
    function ( $field ) {

        if (
            is_admin() &&
            isset( $_GET['page'] ) &&
            $_GET['page'] === 'crm-nuevos-registros'
        ) {
            $field['field_type'] = 'select';
            $field['multiple']   = 0;
            $field['allow_null'] = 1;
        }

        return $field;
    }
);

add_filter(
    'acf/prepare_field/name=imagenes',
    function ( $field ) {

        if (
            is_admin() &&
            isset( $_GET['page'] ) &&
            $_GET['page'] === 'crm-nuevos-registros'
        ) {
            $field['button_text'] = 'Selecciona imagen';
        }

        return $field;
    }
);

add_filter(
    'acf/prepare_field/name=mostrar_web',
    function ( $field ) {

        if (
            is_admin() &&
            isset( $_GET['page'] ) &&
            $_GET['page'] === 'crm-nuevos-registros'
        ) {
            $field['message'] = 'Mostrar en Web';
        }

        return $field;
    }
);


/**
 * Mostrar Institución financiera como selector
 * únicamente en Nuevos Registros.
 */
add_filter(
    'acf/prepare_field/name=institucion_financiera',
    function ( $field ) {

        if (
            is_admin() &&
            isset( $_GET['page'] ) &&
            $_GET['page'] === 'crm-nuevos-registros'
        ) {
            $field['field_type'] = 'select';
            $field['multiple']   = 0;
            $field['allow_null'] = 1;
        }

        return $field;
    }
);



/**
 * Render principal.
 */
function crm_v3_nuevos_registros_page() {

    if ( ! function_exists( 'acf_form' ) ) {
        echo '<div class="notice notice-error"><p>ACF no está disponible.</p></div>';
        return;
    }
    ?>

    <div class="wrap crm-v3-formulario">

        <!-- =====================================================
             HEADER
             ===================================================== -->
        <div class="crm-v3-module-header">
            <span>NUEVOS REGISTROS</span>
        </div>

        <?php
        /*
         * Tras guardar se regresa a esta página con ?registro=...,
         * así recargar no vuelve a crear el registro.
         */
        $crm_registros_guardados = array(
            'cliente'   => 'Cliente guardado correctamente.',
            'propiedad' => 'Propiedad guardada correctamente.',
            'operacion' => 'Operación guardada correctamente.',
        );

        $crm_registro = isset($_GET['registro'])
            ? sanitize_key($_GET['registro'])
            : '';
        ?>

        <?php if (isset($crm_registros_guardados[$crm_registro])) : ?>

            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>
                        <?php echo esc_html($crm_registros_guardados[$crm_registro]); ?>
                    </strong>
                </p>
            </div>

        <?php endif; ?>


        <!-- =====================================================
             REGISTRO DE CLIENTE
             ===================================================== -->
        <section class="crm-v3-form-card">

            <div class="crm-v3-form-card-header">
                <div>
                    <h2>Registro de cliente</h2>
                    <p>Captura de información del cliente</p>
                </div>
            </div>

            <div class="crm-v3-form-card-body">

                <?php
                acf_form(
                    array(
                        'post_id'      => 'new_post',
                        'post_title' => true,
                        'new_post'     => array(
                            'post_type'   => 'clientes',
                            'post_status' => 'publish',
                        ),
                        'field_groups' => array(
                            'group_69fa45838143b',
                        ),
                        'form'         => true,
                        'return'       => admin_url('admin.php?page=crm-nuevos-registros&registro=cliente'),
                        'html_submit_button' => '<button type="submit" class="crm-v3-form-submit">%s</button>',
                        'submit_value' => 'Guardar cliente',
                    )
                );
                ?>

            </div>

        </section>


<!-- =====================================================
     REGISTRO DE PROPIEDAD
     ===================================================== -->
<section class="crm-v3-form-card crm-v3-property-card">

    <div class="crm-v3-form-card-header">
        <div>
            <h2>Registro de propiedad</h2>
            <p>Captura de información de la propiedad</p>
        </div>
    </div>

    <div class="crm-v3-form-card-body">

        <?php
        acf_form(
            array(
                'post_id'      => 'new_post',
                'post_title' => true,
                'new_post'     => array(
                    'post_type'   => 'propiedades',
                    'post_status' => 'publish',
                ),
                'field_groups' => array(
                    'group_69fa4597c55be',
                ),
                'form'         => true,
                'return'       => admin_url('admin.php?page=crm-nuevos-registros&registro=propiedad'),
                'html_submit_button' => '<button type="submit" class="crm-v3-form-submit">%s</button>',
                'submit_value' => 'Guardar propiedad',
            )
        );
        ?>

    </div>

</section>

<!-- =====================================================
     REGISTRO DE OPERACIÓN
     ===================================================== -->
<section class="crm-v3-form-card crm-v3-operation-card">

    <div class="crm-v3-form-card-header">
        <div>
            <h2>Registro de operación</h2>
            <p>Captura de información de la operación inmobiliaria</p>
        </div>
    </div>

    <div class="crm-v3-form-card-body">

        <?php
        acf_form(
            array(
                'post_id'      => 'new_post',
                'post_title' => true,
                'new_post'     => array(
                    'post_type'   => 'operaciones',
                    'post_status' => 'publish',
                ),
                'field_groups' => array(
                    'group_69fa45a722a01',
                ),
                'form'         => true,
                'return'       => admin_url('admin.php?page=crm-nuevos-registros&registro=operacion'),
                'html_submit_button' => '<button type="submit" class="crm-v3-form-submit">%s</button>',
                'submit_value' => 'Guardar operación',
            )
        );
        ?>

    </div>

</section>


    </div>

    <?php
}