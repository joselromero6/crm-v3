<?php
/*
Template Name: Página Contacto
*/


if (
    isset($_POST['crm_contact_submit'])
    &&
    wp_verify_nonce($_POST['_wpnonce'],'crm_contact_form')
) {

if ( empty($_POST['aviso_privacidad']) ) {
    wp_die('Debes aceptar el Aviso de privacidad para enviar la solicitud.');
}

    $nombre = sanitize_text_field($_POST['nombre']);
    $telefono = sanitize_text_field($_POST['telefono']);
    $email = sanitize_email($_POST['email']);
    $operacion = sanitize_text_field($_POST['operacion']);
    $mensaje = sanitize_textarea_field($_POST['mensaje']);

    $destino = 'admin@cibr.com.mx';

    $asunto = 'Nuevo contacto desde sitio web';

    $contenido =
    "Nombre: {$nombre}\n".
    "Teléfono: {$telefono}\n".
    "Email: {$email}\n".
    "Operación: {$operacion}\n\n".
    "Mensaje:\n{$mensaje}";

    $headers = array(
        'Reply-To: '.$nombre.' <'.$email.'>'
    );


    $enviado = wp_mail(
    $destino,
    $asunto,
    $contenido,
    $headers
);


/*
|--------------------------------------------------------------------------
| Crear Lead automático
|--------------------------------------------------------------------------
*/

    wp_insert_post(array(
'post_type' => 'leads',
'post_status' => 'publish',
'post_title' => $nombre,
'meta_input' => array(
    'telefono' => $telefono,
    'notas' => $mensaje,
    'correo' => $email,
    'tipo_de_cliente' => $operacion,
    'origen_lead' => 72,
    'fecha_captacion' => date('Ymd')

)

));
}


get_header();
?>

<main class="contact-page">

    <!-- HERO -->
    <section class="contact-hero">
        <div class="container">
            <h1>Hablemos sobre tu próxima operación inmobiliaria</h1>
            <p>
                Compra, vende o invierte con asesoría profesional de CIBR Inmobiliaria.
            </p>
        </div>
    </section>


<!-- MAPA -->
<section class="contact-map-section container">

    <h2>Nuestra ubicación</h2>

    <div class="contact-map-box">

        <iframe
            src="https://maps.google.com/maps?width=100%25&amp;height=450&amp;hl=es&amp;q=Guadalajara,%20Jalisco,%20México&amp;t=&amp;z=13&amp;ie=UTF8&amp;iwloc=B&amp;output=embed"
            width="100%"
            height="450"
            style="border:0; border-radius:18px;"
            allowfullscreen
            loading="lazy">
        </iframe>

    </div>

</section>




    <!-- CONTACTO -->
    <section class="contact-main container">

        <!-- FORMULARIO -->
        <div class="contact-form-box">

            <h2>Solicita información</h2>


<?php if(isset($enviado)) : ?>

<div class="contact-alert">

<?php
echo $enviado
? 'Solicitud enviada correctamente.'
: 'Error al enviar. Intenta nuevamente.';
?>

</div>

<?php endif; ?>



            <form method="post">
            <?php wp_nonce_field('crm_contact_form'); ?>
            <input type="hidden" name="crm_contact_submit" value="1">

                <input type="text" name="nombre" placeholder="Nombre completo" required>

                <input type="tel" name="telefono" placeholder="Teléfono" required>

                <input type="email" name="email" placeholder="Correo electrónico" required>


                <select name="operacion">
                <option value="comprador">
                Compra
                </option>

                <option value="vendedor">
                Venta
                </option>

                <option value="inversion">
                Inversión
                </option>

                <option value="asesoria">
                Asesoría
                </option>

                </select>

                <textarea name="mensaje" placeholder="Cuéntanos cómo podemos ayudarte"></textarea>



<div class="privacy-consent">

    <label>
        <input type="checkbox" name="aviso_privacidad" value="1" required>
        He leído y acepto el
        <a href="/aviso-de-privacidad/" target="_blank">
            Aviso de privacidad
        </a>.
    </label>

</div>



                <button type="submit" class="btn-primary">
                    Enviar solicitud
                </button>

            </form>

        </div>

        <!-- SIDEBAR -->
        <aside class="contact-sidebar">

            <div class="contact-card">
                <h3>WhatsApp</h3>
                <a href="https://wa.me/523312869601" target="_blank">
                    +52 33 1286 9601
                </a>
            </div>

            <div class="contact-card">
                <h3>Teléfono</h3>
                <a href="tel:+523312869601">
                    Llamar ahora
                </a>
            </div>

            <div class="contact-card">
                <h3>Email</h3>
                <a href="mailto:contactanos@cibr.com.mx">
                    contactanos@cibr.com.mx
                </a>
            </div>

            <div class="contact-card">
                <h3>Ubicación</h3>
                <p>Guadalajara, Jalisco</p>
            </div>

        </aside>

    </section>

</main>

<?php get_footer(); ?>