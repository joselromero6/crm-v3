<?php get_header(); ?>

<main class="site-main">

    <!-- HERO -->
    <section class="hero-section">
        <div class="container">
            <h2>Encuentra tu próxima propiedad con expertos inmobiliarios</h2>
            <p>Compra, vende o invierte con respaldo profesional en Guadalajara, Tlajomulco y alrededores.</p>
            <a href="/propiedades" class="btn-primary">Ver propiedades</a>
        </div>
    </section>



    <!--        SERVICIOS       -->
    <section class="services-section container">
        <h3>Nuestros Servicios</h3>


        <div class="services-grid">
            <div class="service-card">
                <h4>Compra y Venta</h4>
                <p>Asesoría, Análisis y Gestión Inmobiliaria.</p>
            </div>

            <div class="service-card">
                <h4>Inversión</h4>
                <p>Opciones estratégicas para inversionistas inmobiliarios.</p>
            </div>

            <div class="service-card">
                <h4>Créditos Hipotecarios</h4>
                <p>Apoyo en Infonavit, bancos, Cofinavit y más.</p>
            </div>
        </div>
    </section>





<!--        PROPIEDADES DESTACADAS      -->
<section class="featured-properties container">
    <h3>Propiedades Destacadas</h3>
<p>Explora oportunidades seleccionadas por nuestros asesores.</p>

    <div class="properties-grid">

<?php
$destacadas = new WP_Query(array(

'post_type'      => 'propiedades',
'posts_per_page' => 3,          // Propiedades a mostrar en destacadas //
'post_status'    => 'publish',

'meta_query' => array(

array(
'key' => 'mostrar_web',
'value' => 1,
'compare' => '='
)

)

));

if ($destacadas->have_posts()) :
    while ($destacadas->have_posts()) : $destacadas->the_post();

        $precio = get_field('precio_de_venta');
?>

    <div class="property-card">

        <a href="<?php the_permalink(); ?>">
            <img
                src="<?php echo esc_url(crm_v3_theme_imagen_propiedad_url()); ?>"
                alt="<?php the_title_attribute(); ?>"
                loading="lazy"
            >
        </a>

        <div class="property-content">

            <h4><?php the_title(); ?></h4>

            <?php if ($precio) : ?>
                <p>$<?php echo number_format($precio, 2); ?> MXN</p>
            <?php endif; ?>

            <a href="<?php the_permalink(); ?>" class="btn-secondary">
                Ver más
            </a>

        </div>

    </div>

<?php
    endwhile;
    wp_reset_postdata();
endif;
?>

</div>
</section>




    <!--        CTA         -->
    <section class="cta-section">
        <div class="container">
            <h3>¿Listo para tu próxima operación inmobiliaria?</h3>
            <P>Experiencia y Conocimiento: Un agente inmobiliario tiene experiencia y conocimientos especializados en la compra y venta de propiedades, lo que le permite guiar al cliente a través del proceso de venta y ayudar a maximizar el valor de la propiedad.
            </P>

            <P>Negociación: Un agente inmobiliario está capacitado para negociar en nombre del propietario para obtener el mejor precio y las mejores condiciones para la venta de la propiedad.
            </P>
            
            <P>Manejo de la Papelería y los Trámites Legales: Un agente inmobiliario puede ayudar a navegar por la complejidad de los trámites legales y la papelería necesarios para la venta de una propiedad, lo que puede ser un proceso abrumador y tiempo consumido para los propietarios.
            </P>
            
            <a href="/contacto" class="btn-secondary">Contáctanos</a>
        </div>
    </section>

</main>

<?php get_footer(); ?>