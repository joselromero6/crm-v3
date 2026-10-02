<?php get_header(); ?>

<main class="site-main container">

    <section class="blog-archive-section">

    <h1>Blog Inmobiliario</h1>

    <p class="blog-subtitle">
        Noticias, inversión, vivienda social y estrategias inmobiliarias.
    </p>

    <div class="blog-layout">

        <div class="blog-content">

            <div class="blog-grid">

                <?php if (have_posts()) : ?>

                    <?php while (have_posts()) : the_post(); ?>

                        <article class="blog-card">

                            <?php if (has_post_thumbnail()) : ?>

                                <a href="<?php the_permalink(); ?>" class="blog-thumb">

                                    <?php the_post_thumbnail('medium_large'); ?>

                                </a>

                            <?php endif; ?>

                            <div class="blog-card-content">

                                <h2>

                                    <a href="<?php the_permalink(); ?>">

                                        <?php the_title(); ?>

                                    </a>

                                </h2>

                                <span class="blog-date">

                                    📅 <?php echo get_the_date('d \d\e F, Y'); ?>

                                </span>

                                <span class="blog-category">

                                    🏷️ <?php the_category(', '); ?>

                                </span>

                                <?php the_excerpt(); ?>

                                <a href="<?php the_permalink(); ?>" class="btn-secondary">

                                    Leer más

                                </a>

                            </div>

                        </article>

                    <?php endwhile; ?>

                <?php endif; ?>

            </div>

            <div class="blog-pagination">

                <?php

                the_posts_pagination(array(

                    'prev_text' => '« Anterior',

                    'next_text' => 'Siguiente »'

                ));

                ?>

            </div>
            
        </div>

        <?php get_template_part('template-part/blog/sidebar'); ?>

        
    </div>

</section>

</main>

<?php get_footer(); ?>