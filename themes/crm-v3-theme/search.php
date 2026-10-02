<?php
/**
 * Resultados de búsqueda.
 */

get_header();
?>

<main class="site-main container">

    <section class="blog-archive-section">

        <h1>
            Resultados para “<?php echo esc_html(get_search_query()); ?>”
        </h1>

        <div class="search-again">
            <?php get_search_form(); ?>
        </div>

        <?php if (have_posts()) : ?>

            <div class="blog-grid">

                <?php while (have_posts()) : the_post(); ?>

                    <article class="blog-card">

                        <a href="<?php the_permalink(); ?>" class="blog-thumb">

                            <?php if (get_post_type() === 'propiedades') : ?>

                                <img
                                    src="<?php echo esc_url(crm_v3_theme_imagen_propiedad_url()); ?>"
                                    alt="<?php the_title_attribute(); ?>"
                                    loading="lazy"
                                >

                            <?php elseif (has_post_thumbnail()) : ?>

                                <?php the_post_thumbnail('medium_large'); ?>

                            <?php endif; ?>

                        </a>

                        <div class="blog-card-content">

                            <h2>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h2>

                            <?php if (get_post_type() !== 'propiedades') : ?>
                                <?php the_excerpt(); ?>
                            <?php endif; ?>

                            <a href="<?php the_permalink(); ?>" class="btn-secondary">
                                <?php echo get_post_type() === 'propiedades' ? 'Ver propiedad' : 'Leer más'; ?>
                            </a>

                        </div>

                    </article>

                <?php endwhile; ?>

            </div>

            <div class="blog-pagination">
                <?php
                the_posts_pagination(array(
                    'prev_text' => '« Anterior',
                    'next_text' => 'Siguiente »',
                ));
                ?>
            </div>

        <?php else : ?>

            <p>
                No encontramos resultados. Prueba con otras palabras o
                <a href="<?php echo esc_url(home_url('/propiedades/')); ?>">revisa las propiedades disponibles</a>.
            </p>

        <?php endif; ?>

    </section>

</main>

<?php get_footer(); ?>
