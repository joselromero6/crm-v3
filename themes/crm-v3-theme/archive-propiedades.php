<?php get_header(); ?>



<section class="properties-archive container">
    <h1>Propiedades Disponibles</h1>
    <p>Encuentra oportunidades inmobiliarias seleccionadas para ti.</p>




<!-- FILTROS PRO -->
<form class="properties-filters" method="GET" action="<?php echo esc_url(get_post_type_archive_link('propiedades')); ?>">

    <input 
        type="text" 
        name="buscar" 
        placeholder="Buscar propiedad..."
        value="<?php echo isset($_GET['buscar']) ? esc_attr($_GET['buscar']) : ''; ?>"
    >

    <select name="ciudad">
    <option value="">Todas las ciudades</option>

    <?php

$ids_visibles = crm_v3_theme_propiedades_visibles();

$ciudades = get_terms(array(
'taxonomy' => 'ciudad',
'hide_empty' => true,
'object_ids' => $ids_visibles
));

foreach ($ciudades as $ciudad_item) :
?>

            <option value="<?php echo esc_attr($ciudad_item->slug); ?>"
                <?php selected(isset($_GET['ciudad']) ? sanitize_title(wp_unslash($_GET['ciudad'])) : '', $ciudad_item->slug); ?>>
                <?php echo esc_html($ciudad_item->name); ?>
            </option>
        <?php endforeach; ?>
    </select>

    
    <select name="tipo">
        <option value="">Todos los tipos</option>
        <?php

        $ids_visibles = crm_v3_theme_propiedades_visibles();

$tipos = get_terms(array(

'taxonomy' => 'tipo-de-propiedad',
'hide_empty' => true,
'object_ids' => $ids_visibles

));

       foreach ($tipos as $tipo_item) :

    $tipo_id = is_array($tipo_item) ? $tipo_item['term_id'] : $tipo_item->term_id;
    $tipo_nombre = is_array($tipo_item) ? $tipo_item['name'] : $tipo_item->name;
?>

    <option value="<?php echo esc_attr($tipo_id); ?>"
        <?php selected(isset($_GET['tipo']) ? $_GET['tipo'] : '', $tipo_id); ?>>
        <?php echo esc_html($tipo_nombre); ?>
    </option>

<?php endforeach; ?>

    </select>

    <button type="submit" class="btn-primary">
        Filtrar
    </button>

</form>


    <div class="properties-grid">

        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>



            <div class="property-card">

               <div class="property-image">

<?php $badge = crm_v3_theme_badge_estado(); ?>

<?php if ($badge) : ?>

<div class="property-status <?php echo esc_attr($badge['clase']); ?>">

<?php echo esc_html($badge['texto']); ?>

</div>

<?php endif; ?>


<a href="<?php the_permalink(); ?>">

<img
    src="<?php echo esc_url(crm_v3_theme_imagen_propiedad_url()); ?>"
    alt="<?php the_title_attribute(); ?>"
    loading="lazy"
>

</a>

</div>



                <div class="property-content">

                    <h2>
                        <a href="<?php the_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                    </h2>

                    <?php
                    $precio = get_field('precio_de_venta');

                    // CIUDAD
                    $ciudad = get_field('ciudad');
                    if (is_numeric($ciudad)) {
                        $ciudad = get_term($ciudad);
                    }

                    // TIPO DE PROPIEDAD
                    $tipo = get_field('tipo_de_propiedad');
                    if (is_numeric($tipo)) {
                        $tipo = get_term($tipo);
                    }
                    ?>



                    <?php if ($precio) : ?>
                        <p class="property-price">
                            $<?php echo number_format($precio, 2); ?> MXN
                        </p>
                    <?php endif; ?>

                    <?php if ($ciudad) : ?>
                        <p>
                            <strong>Ciudad:</strong>
                            <?php
                            if (is_object($ciudad) && isset($ciudad->name)) {
                                echo esc_html($ciudad->name);
                            } elseif (is_array($ciudad) && isset($ciudad['name'])) {
                                echo esc_html($ciudad['name']);
                            } else {
                                echo esc_html($ciudad);
                            }
                            ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($tipo) : ?>
                        <p>
                            <strong>Tipo:</strong>
                            <?php
                            if (is_object($tipo) && isset($tipo->name)) {
                                echo esc_html($tipo->name);
                            } elseif (is_array($tipo) && isset($tipo['name'])) {
                                echo esc_html($tipo['name']);
                            } else {
                                echo esc_html($tipo);
                            }
                            ?>
                        </p>
                    <?php endif; ?>

                    <a href="<?php the_permalink(); ?>" class="btn-secondary">
                        Ver propiedad
                    </a>

                </div>

            </div>

        <?php endwhile; else : ?>

            <p>No hay propiedades disponibles actualmente.</p>

        <?php endif; ?>

    </div>

    <div class="pagination properties-pagination">
        <?php
        the_posts_pagination(array(
            'mid_size'  => 1,
            'prev_text' => '« Anterior',
            'next_text' => 'Siguiente »',
        ));
        ?>
    </div>

</section>

<?php get_footer(); ?>




