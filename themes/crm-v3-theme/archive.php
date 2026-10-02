<?php get_header(); ?>

<main class="site-main container">
    <header class="archive-header">
        <h1><?php the_archive_title(); ?></h1>
        <div><?php the_archive_description(); ?></div>
    </header>

    <?php if (have_posts()) : ?>
        
        <div class="archive-grid">
            <?php while (have_posts()) : the_post(); ?>
                
                <article <?php post_class('archive-card'); ?>>
                    
                    <?php if (has_post_thumbnail()) : ?>
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail('medium'); ?>
                        </a>
                    <?php endif; ?>

                    <h2>
                        <a href="<?php the_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                    </h2>

                    <div class="archive-excerpt">
                        <?php the_excerpt(); ?>
                    </div>

                </article>

            <?php endwhile; ?>
        </div>

        <div class="pagination">
            <?php the_posts_pagination(); ?>
        </div>

    <?php else : ?>

        <p>No se encontraron publicaciones.</p>

    <?php endif; ?>

</main>

<?php get_footer(); ?>