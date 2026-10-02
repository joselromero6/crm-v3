<?php get_header(); ?>

<main class="single-blog-layout container">

    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

        <div class="single-blog-main">

        <article class="single-blog-post">

            <header class="single-blog-header">

                <h1><?php the_title(); ?></h1>

                <div class="single-blog-meta">
                    <span><?php echo get_the_date(); ?></span>
                    <span> | </span>
                    <span><?php the_author(); ?></span>
                </div>

            </header>

            <?php if (has_post_thumbnail()) : ?>
                <div class="single-blog-image">
                    <?php the_post_thumbnail('full'); ?>
                </div>
            <?php endif; ?>

            <div class="single-blog-content">
                <?php the_content(); ?>
            </div>



<div class="blog-cta-box">

    <h3>¿Necesitas vender, comprar o invertir?</h3>

    <p>
        En CIBR Inmobiliaria te ayudamos con asesoría profesional y soluciones inmobiliarias reales.
    </p>

    <a href="/contacto" class="btn-secondary">Contactar asesor</a>

    <a href="https://wa.me/523312869601" class="btn-whatsapp" target="_blank">
        WhatsApp
    </a>

</div>

<section class="related-blog-posts">

    <h3>Artículos relacionados</h3>

    <div class="related-blog-grid">

        <?php
        $related_posts = new WP_Query(array(
            'post_type'      => 'post',
            'posts_per_page' => 3,
            'post__not_in'   => array(get_the_ID()),
            'category__in'   => wp_get_post_categories(get_the_ID())
        ));

        if ($related_posts->have_posts()) :
            while ($related_posts->have_posts()) :
                $related_posts->the_post();
        ?>

            <article class="related-post-card">

                <?php if (has_post_thumbnail()) : ?>
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('medium'); ?>
                    </a>
                <?php endif; ?>

                <h4>
                    <a href="<?php the_permalink(); ?>">
                        <?php the_title(); ?>
                    </a>
                </h4>

            </article>

        <?php
            endwhile;
            wp_reset_postdata();
        endif;
        ?>

    </div>

</section>


    </article>

</div>





<aside class="single-blog-sidebar">

    <!-- POSTS RECIENTES -->
    <div class="sidebar-widget">
        <h3>Artículos recientes</h3>

        <ul>
            <?php
            $recent_posts = wp_get_recent_posts(array(
                'numberposts' => 5,
                'post_status' => 'publish'
            ));

            foreach ($recent_posts as $post_item) :
            ?>
                <li>
                    <a href="<?php echo get_permalink($post_item['ID']); ?>">
                        <?php echo esc_html($post_item['post_title']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- CATEGORÍAS -->
    <div class="sidebar-widget">
        <h3>Categorías</h3>
        <ul>
            <?php wp_list_categories(array(
                'title_li' => ''
            )); ?>
        </ul>
    </div>

    <!-- AYUDA Y HERRAMIENTAS -->
    <div class="sidebar-widget sidebar-help">

        <h3>¿Necesitas ayuda?</h3>

        <p class="sidebar-help-intro">
            Encuentra información y herramientas para tomar mejores decisiones sobre tu vivienda.
        </p>

        <div class="sidebar-help-group">
            <h4>Simulador de Crédito Hipotecario</h4>

            <a href="https://socasesores.com/simulador-credito-hipotecario/?q=cibr" target="_blank" rel="noopener">
                Precalifícate y consulta tu crédito →
            </a>

        </div>

        <div class="sidebar-help-group">
            <h4>Crédito Infonavit</h4>

            <a href="https://micuenta.infonavit.org.mx/" target="_blank" rel="noopener">
                Precalifícate y consulta tu crédito →
            </a>

            <a href="https://portalmx.infonavit.org.mx/wps/portal/infonavitmx/mx2/derechohabientes/quiero_credito/quiero_comprar/">
                Conoce tus opciones para comprar →
            </a>
        </div>

        <div class="sidebar-help-group">
            <h4>Crédito FOVISSSTE</h4>

            <a href="https://www.gob.mx/fovissste/acciones-y-programas/servicios-en-linea-del-fovissste" target="_blank" rel="noopener">
                Consulta tu crédito y servicios →
            </a>

            <a href="https://www.gob.mx/fovissste/acciones-y-programas/creditos-hipotecarios" target="_blank" rel="noopener">
                Conoce las opciones de crédito →
            </a>
            
            <a href="https://originacion.fovissste.com.mx/Originacion/cgi-bin/Predictamen/SimuladorParaTodos.aspx" target="_blank" rel="noopener">
                Simulador de crédito fovissste →
            </a>
        </div>

        <div class="sidebar-help-group">
            <h4>Compra o venta</h4>

            <a href="/blog/">
                Ver guías inmobiliarias →
            </a>
        </div>

    </div>


<!-- COMPARTIR ARTÍCULO -->
<div class="sidebar-widget sidebar-share">

    <h3>Compartir este artículo</h3>

    <p>
        ¿Te pareció útil? Compártelo con alguien a quien pueda servirle.
    </p>

    <div class="sidebar-share-buttons">

        <a
            href="https://wa.me/?text=<?php echo rawurlencode(get_the_title() . ' ' . get_permalink()); ?>"
            target="_blank"
            rel="noopener"
            class="share-whatsapp"
        >
            WhatsApp
        </a>

        <a
            href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode(get_permalink()); ?>"
            target="_blank"
            rel="noopener"
            class="share-facebook"
        >
            Facebook
        </a>

        <a
            href="https://twitter.com/intent/tweet?text=<?php echo rawurlencode(get_the_title()); ?>&url=<?php echo rawurlencode(get_permalink()); ?>"
            target="_blank"
            rel="noopener"
            class="share-x"
        >
            X
        </a>

        <button
            type="button"
            class="share-copy"
            onclick="navigator.clipboard.writeText('<?php echo esc_js(get_permalink()); ?>'); this.textContent='¡Enlace copiado!'; setTimeout(() => this.textContent='Copiar enlace', 2000);"
        >
            Copiar enlace
        </button>

    </div>

</div>

    <!-- CTA -->
    <div class="sidebar-widget sidebar-cta">
        <h3>¿Buscas asesoría?</h3>
        <p>Compra, vende o invierte con expertos inmobiliarios.</p>

        <a href="https://wa.me/523312869601" class="btn-whatsapp" target="_blank">
            WhatsApp
        </a>
    </div>

</aside>



<?php
if (comments_open() || get_comments_number()) :
    comments_template();
endif;
?>```



    <?php endwhile; endif; ?>

</main>

<?php get_footer(); ?>