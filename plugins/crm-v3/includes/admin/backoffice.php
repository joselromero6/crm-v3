<?php
/**
 * Backoffice CRM CIBR
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * ============================================================
 * 2. NOMBRE DE LA TABLA
 * ============================================================
 */

function crm_comunicados_tabla() {

    global $wpdb;

    return $wpdb->prefix . 'crm_comunicados';
}


/**
 * ============================================================
 * BACKOFFICE — CONTADOR DE CLIENTES
 * ============================================================
 */

function crm_v3_backoffice_clientes_count($estatus) {

    $query = new WP_Query([
        'post_type'      => 'clientes',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'meta_query'     => [
            [
                'key'     => 'estatus',
                'value'   => $estatus,
                'compare' => '=',
            ],
        ],
    ]);

    return (int) $query->found_posts;
}

/**
 * Página principal del Backoffice
 */
function crm_v3_backoffice_page() {
    ?>
    <div class="wrap crm-v3-backoffice">

        <header class="crm-bo-header">

            <div class="crm-bo-header-content">

                <div class="crm-bo-brand">

                    <div class="crm-bo-brand-icon">
                        <span class="dashicons dashicons-chart-area"></span>
                    </div>

                    <div class="crm-bo-brand-text">
                        <h1>Backoffice</h1>
                        <p>CIBR Asesoría Análisis y Gestión Inmobiliaria</p>
                    </div>

                </div>

                <div class="crm-bo-header-meta">

                    <div class="crm-bo-status">
                        <span class="crm-bo-status-dot"></span>
                        Sistema operativo
                    </div>

                    <div class="crm-bo-date">
                        <?php echo esc_html(
                            wp_date(
                                'd \d\e F \d\e Y',
                                current_time('timestamp')
                            )
                        ); ?>
                    </div>

                </div>

            </div>

        </header>

        <main class="crm-bo-content">

<!-- =====================================================
     ENLACES EXTERNOS
===================================================== -->

<section class="crm-bo-panel">

    <div class="crm-bo-panel-header">
        <div>
            <h2>Enlaces externos</h2>
            
        </div>
    </div>

    <div class="crm-bo-links">

         <a href="https://cibr.com.mx:2096/" class="crm-bo-link">
            <span class="dashicons dashicons-building"></span>
            <span>Mailweb</span>
        </a>

        <a href="https://micuenta.infonavit.org.mx/" class="crm-bo-link">
            <span class="dashicons dashicons-building"></span>
            <span>Infonavit</span>
        </a>

        <a href="https://www.sat.gob.mx/portal/public/home" class="crm-bo-link">
            <span class="dashicons dashicons-media-document"></span>
            <span>Generar factura SAT</span>
        </a>

        <a href="https://www.gob.mx/curp/" class="crm-bo-link">
            <span class="dashicons dashicons-id"></span>
            <span>CURP y otros</span>
        </a>

        <a href="https://app.cfe.mx/Aplicaciones/CCFE/ReciboDeLuzGMX/Consulta" class="crm-bo-link">
            <span class="dashicons dashicons-admin-home"></span>
            <span>CFE</span>
        </a>

        <a href="https://tesoreria.tlajomulco.gob.mx/" class="crm-bo-link">
            <span class="dashicons dashicons-water"></span>
            <span>Agua y Predial Tlajo</span>
        </a>

        <a href="https://drive.google.com/drive/" class="crm-bo-link">
            <span class="dashicons dashicons-cloud"></span>
            <span>Google Drive</span>
        </a>

        <a href="https://www.google.com/maps?authuser=0" class="crm-bo-link">
            <span class="dashicons dashicons-location"></span>
            <span>Maps</span>
        </a>
        
        <a href="https://www.iloveimg.com/es" class="crm-bo-link">
            <span class="dashicons dashicons-love"></span>
            <span>Image Love</span>
        </a>
        
        <a href="https://visorurbano.jalisco.gob.mx/inicio" class="crm-bo-link">
            <span class="dashicons dashicons-lens"></span>
            <span>Visor Urbano</span>
        </a>

    </div>

</section>


<!-- =====================================================
     ÁREA DE TRABAJO INTERNO
===================================================== -->

<section class="crm-bo-panel">

    <div class="crm-bo-panel-header">
        <div>
            <h2>Área de trabajo interno</h2>
        </div>
    </div>

    <div class="crm-bo-workspace-grid">

    <!-- ================================================
         ÁREA PRINCIPAL — 70%
    ================================================= -->

    <div class="crm-bo-workspace-main">

        <div class="crm-bo-links crm-bo-links-internal">

            <a href="<?php echo esc_url(admin_url('admin.php?page=crm-nuevos-registros')); ?>"
               class="crm-bo-link">
                <span class="dashicons dashicons-plus-alt"></span>
                <span>Nuevos registros</span>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=crm-clientes')); ?>"
               class="crm-bo-link">
                <span class="dashicons dashicons-groups"></span>
                <span>CRM Clientes</span>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=crm-propiedades')); ?>"
               class="crm-bo-link">
                <span class="dashicons dashicons-admin-home"></span>
                <span>CRM Propiedades</span>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=crm-operaciones')); ?>"
               class="crm-bo-link">
                <span class="dashicons dashicons-clipboard"></span>
                <span>CRM Operaciones</span>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=crm-v3-catalogos')); ?>"
               class="crm-bo-link">
                <span class="dashicons dashicons-category"></span>
                <span>Catálogos</span>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=crm-leads')); ?>"
               class="crm-bo-link">
                <span class="dashicons dashicons-chart-area"></span>
                <span>Admin Leads</span>
            </a>

        </div>

    </div>


    <!-- ================================================
         ÁREA SECUNDARIA — 30%
    ================================================= -->

    <div class="crm-bo-workspace-side">

        <!-- Espacio reservado para próximos módulos -->

    </div>

</div>

</section>

        </main>


<!-- =====================================================
     ANÁLISIS
===================================================== -->

<section class="crm-bo-analysis-grid">

    <div class="crm-bo-analysis-panel">

        <div class="crm-bo-analysis-header crm-bo-analysis-header-clientes">

    <h2>Análisis Clientes</h2>

    <div class="crm-bo-client-analysis-controls">

        <button
            type="button"
            class="crm-bo-client-counter"
            data-estatus="en_proceso"
        >
            <span>En proceso</span>
            <strong>
                <?php
                echo esc_html(
                    crm_v3_backoffice_clientes_count(
                        'en_proceso'
                    )
                );
                ?>
            </strong>
        </button>

        <button
            type="button"
            class="crm-bo-client-counter"
            data-estatus="para_relacionar"
        >
            <span>Para relacionar</span>
            <strong>
                <?php
                echo esc_html(
                    crm_v3_backoffice_clientes_count(
                        'para_relacionar'
                    )
                );
                ?>
            </strong>
        </button>

        <button
            type="button"
            class="crm-bo-client-counter"
            data-estatus="perdido"
        >
            <span>Perdido</span>
            <strong>
                <?php
                echo esc_html(
                    crm_v3_backoffice_clientes_count(
                        'perdido'
                    )
                );
                ?>
            </strong>
        </button>

    </div>

</div>

        <div class="crm-bo-analysis-content">

    <div class="crm-bo-client-analysis">


        <div class="crm-bo-client-selector">

            <select
                id="crm-bo-cliente-selector"
                disabled
            >
                <option value="">
                    Seleccionar cliente
                </option>
            </select>

        </div>

        <div
            id="crm-bo-cliente-resumen"
            class="crm-bo-cliente-resumen"
        >
            <div class="crm-bo-analysis-placeholder">
                Selecciona un contador para comenzar.
            </div>
        </div>

    </div>

</div>

    </div>


    <div class="crm-bo-analysis-panel">

    <div class="crm-bo-analysis-header">
        <h2>Análisis de mercado</h2>
    </div>

    <div class="crm-bo-analysis-content">

        <?php
        if (function_exists('crm_v3_analisis_mercado_render')) {
            crm_v3_analisis_mercado_render();
        }
        ?>

    </div>

</div>

</section>


<!-- =====================================================
          PRÓXIMOS MÓDULOS ANALISIS ESTRATEGICOS
===================================================== -->

<section class="crm-bo-next-grid">

    <!-- =================================================
         CARD 40% COMUNICADOS
    ================================================== -->

    <div class="crm-bo-next-panel crm-bo-next-panel-small">

        <div class="crm-bo-next-header">
            <h2>Comunicados Inmobiliarios</h2>
        </div>

        <div class="crm-bo-next-content">

            <div class="crm-bo-next-placeholder">

                <div class="crm-bo-next-icon">
                    <span class="dashicons dashicons-chart-pie"></span>
                </div>

                <h3>Indicadores clave</h3>

                <p>
                    Próximamente encontrarás métricas
                    e indicadores relevantes.
                </p>

            </div>

        </div>

    </div>


    <!-- =================================================
         CARD 60%
    ================================================== -->

    <div class="crm-bo-next-panel crm-bo-next-panel-large">

        <div class="crm-bo-next-header">
            <h2>Centro de Inteligencia y Estrategia (CIE)</h2>
        </div>

        <div class="crm-bo-next-content">

            <div class="crm-bo-next-placeholder">

                <div class="crm-bo-next-icon">
                    <span class="dashicons dashicons-chart-bar"></span>
                </div>

                <h3>Gráfica de tendencia</h3>

                <p>
                    Aquí podremos visualizar la evolución
                    de precios y comportamiento de la zona.
                </p>

            </div>

        </div>

    </div>

</section>


    </div>

    <?php
}