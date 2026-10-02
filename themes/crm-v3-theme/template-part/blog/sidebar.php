<?php
/**
 * CRM-V3
 * Sidebar Blog
 */
?>

<aside class="blog-sidebar">

    <div class="sidebar-card">

        <h3>Buscar</h3>

        <?php get_search_form(); ?>

    </div>

    <div class="sidebar-card">

        <h3>Categorías</h3>

        <ul>

            <?php

            wp_list_categories(array(

                'title_li'  => '',

                'show_count'=> true,

                'orderby'   => 'count',

                'order'     => 'DESC'

            ));

            ?>

        </ul>

    </div>

    <div class="sidebar-card">

        <h3>Artículos recientes</h3>

        <ul>

            <?php

            $recent = new WP_Query(array(

                'post_type'=>'post',

                'posts_per_page'=>5

            ));

            while($recent->have_posts()) :

                $recent->the_post();

            ?>

                <li>

                    <a href="<?php the_permalink(); ?>">

                        <?php the_title(); ?>

                    </a>

                </li>

            <?php endwhile; wp_reset_postdata(); ?>

        </ul>

    </div>

    <div class="sidebar-card sidebar-cta">

        <h3>¿Buscas asesoría?</h3>

        <p>

            Compra, vende o invierte con expertos inmobiliarios.

        </p>

        <a href="<?php echo esc_url(crm_v3_theme_whatsapp_url()); ?>"

           class="btn-whatsapp"

           target="_blank">

            WhatsApp

        </a>

    </div>

</aside>