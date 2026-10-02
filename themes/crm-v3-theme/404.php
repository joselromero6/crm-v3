<?php
/**
 * Página no encontrada.
 */

get_header();
?>

<main class="site-main container">

    <section class="error-404">

        <h1>No encontramos esta página</h1>

        <p>
            Es posible que la dirección haya cambiado o que la propiedad
            ya no esté disponible.
        </p>

        <p class="error-404-actions">

            <a href="<?php echo esc_url(home_url('/propiedades/')); ?>" class="btn-primary">
                Ver propiedades
            </a>

            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-secondary">
                Ir al inicio
            </a>

        </p>

    </section>

</main>

<?php get_footer(); ?>
