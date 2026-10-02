<?php
/*
Template Name: Página Contacto
*/


/*
|--------------------------------------------------------------------------
| El formulario lo procesa el plugin CRM-V3
| (includes/modules/crm-contacto-web.php): crea el Lead, envía el
| correo y regresa aquí con el resultado en ?contacto=...
|--------------------------------------------------------------------------
*/

$crm_contacto_mensaje = function_exists('crm_v3_contacto_web_mensaje')
    ? crm_v3_contacto_web_mensaje()
    : null;


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
        <div class="contact-form-box" id="contacto-form">

            <h2>Solicita información</h2>


<?php if ($crm_contacto_mensaje) : ?>

<div
    class="contact-alert contact-alert-<?php echo esc_attr($crm_contacto_mensaje['tipo']); ?>"
    role="status"
>

<?php echo esc_html($crm_contacto_mensaje['texto']); ?>

</div>

<?php endif; ?>



            <form
                method="post"
                action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
            >
            <input type="hidden" name="action" value="crm_v3_contacto_web">

                <!-- Campo trampa contra spam: las personas no lo ven. -->
                <div style="position:absolute; left:-9999px;" aria-hidden="true">
                    <label>
                        No llenar este campo
                        <input type="text" name="crm_campo_extra" value="" tabindex="-1" autocomplete="off">
                    </label>
                </div>

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