<?php
/**
 * Plantilla de respaldo: lista el contenido que no tiene
 * una plantilla más específica.
 */

get_header();
?>

<main class="site-main container">

    <section class="blog-archive-section">

        <?php if (have_posts()) : ?>

            <div class="blog-grid">

                <?php while (have_posts()) : the_post(); ?>

                    <article class="blog-card">

                        <div class="blog-card-content">

                            <h2>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h2>

                            <?php the_excerpt(); ?>

                            <a href="<?php the_permalink(); ?>" class="btn-secondary">
                                Leer más
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

            <p>No hay contenido para mostrar.</p>

        <?php endif; ?>

    </section>

</main>

<?php get_footer(); ?>
