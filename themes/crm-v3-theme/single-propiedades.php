<?php get_header(); ?>

<?php while (have_posts()) : the_post(); ?>



<section class="single-property-layout">

    <!-- SIDEBAR IZQUIERDO ASESOR -->
    <aside class="property-left-sidebar">

      <?php
        $asesor_id = get_the_author_meta('ID');
        $asesor_nombre = get_the_author();
        $asesor_email = get_the_author_meta('user_email');
        $asesor_telefono = get_field('telefono', 'user_' . $asesor_id);
        $asesor_foto = get_field('foto_perfil', 'user_' . $asesor_id);
    ?>

        <div class="property-advisor-box">

            <?php if ($asesor_foto) : ?>
                <img src="<?php echo esc_url($asesor_foto['url']); ?>" alt="<?php echo esc_attr($asesor_nombre); ?>">
            <?php endif; ?>

            <h3><?php echo esc_html($asesor_nombre); ?></h3>

            <p class="advisor-role">
            <?php echo esc_html(get_field('cargo', 'user_' . $asesor_id) ?: 'Asesor Inmobiliario'); ?>
            </p>

            <p class="advisor-note">
    <?php 
    echo esc_html(
        get_field('biografia_asesor', 'user_' . $asesor_id) 
        ?: 'Agenda visitas, solicita información detallada o recibe asesoría personalizada.'
    ); 
    ?>
</p>

            <?php if ($asesor_telefono) : ?>
                <a href="tel:<?php echo esc_attr($asesor_telefono); ?>" class="btn-secondary">
                    <?php echo esc_html($asesor_telefono); ?>
                </a>

                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $asesor_telefono); ?>?text=Hola,%20me%20interesa%20la%20propiedad:%20<?php echo urlencode(get_the_title()); ?>"
                   class="btn-whatsapp"
                   target="_blank">
                   WhatsApp
                </a>
            <?php endif; ?>

            <?php if ($asesor_email) : ?>
                <a href="mailto:<?php echo esc_attr($asesor_email); ?>" class="advisor-email">
    <?php echo esc_html($asesor_email); ?>
</a>
            <?php endif; ?>

        </div>

    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="property-main-content">


<section class="single-property container">

    <div class="single-property-header">
        <h1><?php the_title(); ?></h1>

        <?php
        $precio = get_field('precio_de_venta');
        if ($precio):
        ?>
            <p class="single-price">
                $<?php echo number_format($precio, 2); ?> MXN
            </p>
        <?php endif; ?>
    </div>

    


    <!-- GALERÍA DE PROPIEDAD -->

<?php
$imagenes = get_field('imagenes');

if ($imagenes && is_array($imagenes)) :

    $imagen_principal = $imagenes[0];

    if (is_array($imagen_principal)) {
        $imagen_principal_url = $imagen_principal['url'];
    } elseif (is_numeric($imagen_principal)) {
        $imagen_principal_url = wp_get_attachment_image_url($imagen_principal, 'large');
    } else {
        $imagen_principal_url = $imagen_principal;
    }

    $estado = get_field('estado_comercial');

    $badges = array(
        'captada' => array(
            'texto' => 'CAPTADA',
            'clase' => 'estado-captada'
        ),
        'en_preparacion' => array(
            'texto' => 'EN PREPARACIÓN',
            'clase' => 'estado-preparacion'
        ),
        'disponible' => array(
            'texto' => 'DISPONIBLE',
            'clase' => 'estado-disponible'
        ),
        'tratada' => array(
            'texto' => 'TRATADA',
            'clase' => 'estado-tratada'
        ),
        'vendida' => array(
            'texto' => 'VENDIDA',
            'clase' => 'estado-vendida'
        )
    );
?>

<div class="property-gallery-main">

    <div class="property-main-image">

        <?php if (!empty($estado) && isset($badges[$estado])) : ?>

            <span class="property-status <?php echo esc_attr($badges[$estado]['clase']); ?>">
                <?php echo esc_html($badges[$estado]['texto']); ?>
            </span>

        <?php endif; ?>

        <img
            id="property-main-img"
            src="<?php echo esc_url($imagen_principal_url); ?>"
            alt="<?php echo esc_attr(get_the_title()); ?>"
        >

    </div>

    <div class="property-gallery-carousel">

        <?php foreach ($imagenes as $indice => $imagen) :

            if (is_array($imagen)) {
                $thumb_url = $imagen['sizes']['medium_large'] ?? $imagen['url'];
                $full_url = $imagen['url'];
            } elseif (is_numeric($imagen)) {
                $thumb_url = wp_get_attachment_image_url($imagen, 'medium_large');
                $full_url = wp_get_attachment_image_url($imagen, 'large');
            } else {
                $thumb_url = $imagen;
                $full_url = $imagen;
            }
        ?>

            <button
                type="button"
                class="gallery-thumb <?php echo $indice === 0 ? 'active' : ''; ?>"
                data-image="<?php echo esc_url($full_url); ?>"
            >
                <img
                    src="<?php echo esc_url($thumb_url); ?>"
                    alt="<?php echo esc_attr(get_the_title()); ?>"
                >
            </button>

        <?php endforeach; ?>

    </div>

</div>

<?php endif; ?>

</div>



   <!-- CARACTERÍSTICAS -->
<div class="single-property-details">

    <h2>Características</h2>

    <div class="property-details-grid">

        <p><strong>Dirección:</strong> <?php the_field('direccion'); ?></p>
        <p><strong>Recámaras:</strong> <?php the_field('recamaras'); ?></p>
        <p><strong>Baños:</strong> <?php the_field('banos'); ?></p>
        <p><strong>Cochera:</strong> <?php the_field('cochera'); ?></p>
        <p><strong>M² Terreno:</strong> <?php the_field('m2_terreno'); ?></p>
        <p><strong>M² Construcción:</strong> <?php the_field('m2_construccion'); ?></p>
        <p><strong>Estado del inmueble:</strong> <?php the_field('estado_del_inmueble'); ?></p>
        <p><strong>Documentación:</strong> <?php the_field('documentacion'); ?></p>

    </div>

</div>

    
    <!-- DESCRIPCIÓN PREMIUM -->
<?php if (get_the_content()) : ?>
<div class="single-property-description premium-description">

    <h2>Descripción de la propiedad</h2>

    <div class="description-content">
        <?php the_content(); ?>
    </div>

    <div class="property-benefits">
        <div class="benefit-box">
            <h3>Ubicación estratégica</h3>
            <p>Cercanía a servicios, escuelas, zonas comerciales y principales vialidades.</p>
        </div>

        <div class="benefit-box">
            <h3>Alta plusvalía</h3>
            <p>Zona con crecimiento constante y excelente oportunidad patrimonial.</p>
        </div>

        <div class="benefit-box">
            <h3>Asesoría profesional</h3>
            <p>Te acompañamos durante todo el proceso de compra o inversión.</p>
        </div>
    </div>

</div>
<?php endif; ?>


<!-- PROPIEDADES SIMILARES -->
<?php
$ciudad_actual = get_field('ciudad');

if ($ciudad_actual) :

    $related_args = array(
    'post_type'      => 'propiedades',
    'posts_per_page' => 3,
    'post__not_in'   => array(get_the_ID()),
    'meta_query'     => array(
        array(
            'key'     => 'ciudad',
            'value'   => is_object($ciudad_actual) ? $ciudad_actual->term_id : $ciudad_actual,
            'compare' => '='
        ),
        array(
            'key'     => 'mostrar_web',
            'value'   => 1,
            'compare' => '='
        )
    )
);

    $related_query = new WP_Query($related_args);

    if ($related_query->have_posts()) :
?>
<section class="related-properties">
    <h2>Propiedades similares</h2>

    <div class="properties-grid">

        <?php while ($related_query->have_posts()) : $related_query->the_post(); ?>

            <div class="property-card">

                <?php if (has_post_thumbnail()) : ?>
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('medium_large'); ?>
                    </a>
                <?php endif; ?>

                <div class="property-content">

                    <h3>
                        <a href="<?php the_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                    </h3>

                    <?php
                    $precio = get_field('precio_de_venta');
                    if ($precio):
                    ?>
                        <p class="property-price">
                            $<?php echo number_format($precio, 2); ?> MXN
                        </p>
                    <?php endif; ?>

                    <a href="<?php the_permalink(); ?>" class="btn-secondary">
                        Ver propiedad
                    </a>

                </div>

            </div>

        <?php endwhile; ?>

    </div>
</section>
<?php
    endif;
    wp_reset_postdata();

endif;
?>


<!-- UBICACIÓN PREMIUM -->
<?php
$direccion = get_field('direccion');
$ciudad = get_field('ciudad');

$ciudad_nombre = '';

if ($ciudad) {
    if (is_object($ciudad) && isset($ciudad->name)) {
        $ciudad_nombre = $ciudad->name;
    } elseif (is_array($ciudad) && isset($ciudad['name'])) {
        $ciudad_nombre = $ciudad['name'];
    } else {
        $ciudad_nombre = $ciudad;
    }
}

$ubicacion_completa = trim($direccion . ', ' . $ciudad_nombre . ', Jalisco, México');
?>

<?php if ($direccion || $ciudad_nombre) : ?>
<div class="property-location-box">


<h2>Ubicación de la propiedad</h2>

    <p><?php echo esc_html($ubicacion_completa); ?></p>

    <iframe
    src="https://maps.google.com/maps?width=100%25&amp;height=350&amp;hl=es&amp;q=<?php echo urlencode($ubicacion_completa); ?>&amp;t=&amp;z=15&amp;ie=UTF8&amp;iwloc=B&amp;output=embed"
    width="100%"
    height="350"
    style="border:0; border-radius:14px; margin-top:25px;"
    allowfullscreen
    loading="lazy">
    </iframe>

    <a 
        href="https://www.google.com/maps/search/<?php echo urlencode($ubicacion_completa); ?>" 
        target="_blank" 
        class="btn-secondary"
    >
        Ver ubicación en Google Maps
    </a>

</div>
<?php endif; ?>



    <!-- CTA -->
    <div class="single-property-contact">
        <a href="https://wa.me/523312869601?text=Hola,%20me%20interesa%20la%20propiedad:%20<?php echo urlencode(get_the_title()); ?>" 
   class="btn-whatsapp" 
   target="_blank"> Contactar por WhatsApp
</a>
        
    </div>
    
</section>


    </main>
</section>

<?php endwhile; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const mainImage = document.getElementById('property-main-img');
    const thumbnails = document.querySelectorAll('.gallery-thumb');

    if (!mainImage || !thumbnails.length) {
        return;
    }

    thumbnails.forEach(function (thumbnail) {

        thumbnail.addEventListener('click', function () {

            const imageUrl = this.getAttribute('data-image');

            if (!imageUrl) {
                return;
            }

            mainImage.src = imageUrl;

            thumbnails.forEach(function (item) {
                item.classList.remove('active');
            });

            this.classList.add('active');
        });

    });

});
</script>



<?php get_footer(); ?>