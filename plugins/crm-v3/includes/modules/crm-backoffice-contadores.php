<?php
/**
 * ============================================================
 * BACKOFFICE — CONTADORES
 * ============================================================
 *
 * Dos grupos de 5 contadores entre "Área de trabajo interno" y
 * "Análisis Clientes".
 *
 * GRUPO 1 — Estatus de clientes y operaciones
 *
 *   Contador          Estatus en CRM Clientes   Última operación
 *   ----------------  ------------------------  ----------------
 *   En proceso        En proceso                (sin operación)
 *   Sin relación      Para relacionar           (sin operación)
 *   Relacionado       Para relacionar           En proceso
 *   Perdido           Para relacionar           Perdida
 *   Cerrado           Para relacionar           Cerrada
 *
 *   Los clientes con estatus "Perdido" no entran en ningún contador.
 *
 * GRUPO 2 — Etapas del pipeline (CRM Operaciones)
 *
 *   Cada operación en proceso cuenta una sola vez, en la etapa más
 *   avanzada que tiene completa:
 *   Expediente completo → Valuador → Institución financiera → Notaría.
 *   Las operaciones cerradas cuentan en "Cerrado".
 *   No se cuentan las perdidas ni las que aún no completan ninguna
 *   etapa.
 *
 * FILTRO POR FECHA (uno por grupo)
 *
 *   Todo · Este mes · Mes anterior · Este año.
 *   Fecha que se usa:
 *   - Cliente sin operación: el día en que se registró.
 *   - Operación cerrada: la fecha de cierre (o, si no la tiene, la
 *     fecha de operación).
 *   - Las demás operaciones: la fecha de operación.
 *   Un registro sin fecha solo aparece con "Todo".
 * ============================================================
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Periodos del filtro.
 */
function crm_v3_contadores_periodos() {

    return array(
        'todo'         => 'Todo',
        'este_mes'     => 'Este mes',
        'mes_anterior' => 'Mes anterior',
        'este_anio'    => 'Este año',
    );
}


/**
 * Periodo elegido para un grupo (leído de la dirección de la página).
 */
function crm_v3_contadores_periodo($parametro) {

    $periodo = isset($_GET[$parametro])
        ? sanitize_key(wp_unslash($_GET[$parametro]))
        : 'todo';

    return array_key_exists($periodo, crm_v3_contadores_periodos())
        ? $periodo
        : 'todo';
}


/**
 * ¿La fecha (AAAA-MM-DD) cae dentro del periodo?
 */
function crm_v3_contadores_en_periodo($fecha, $periodo) {

    if ($periodo === 'todo') {
        return true;
    }

    if (empty($fecha)) {
        return false;
    }

    $mes_fecha = substr($fecha, 0, 7);

    switch ($periodo) {

        case 'este_mes':
            return $mes_fecha === wp_date('Y-m');

        case 'mes_anterior':
            return $mes_fecha === wp_date('Y-m', strtotime('first day of last month', current_time('timestamp')));

        case 'este_anio':
            return substr($fecha, 0, 4) === wp_date('Y');
    }

    return true;
}


/**
 * Fecha de una operación para el filtro (AAAA-MM-DD o '').
 */
function crm_v3_contadores_fecha_operacion($operacion_id, $estatus) {

    if ($estatus === 'cerrada') {

        $cierre = crm_v3_fecha_para_input(get_field('fecha_cierre', $operacion_id));

        if ($cierre !== '') {
            return $cierre;
        }
    }

    return crm_v3_fecha_para_input(get_field('fecha_de_operacion', $operacion_id));
}


/**
 * Operaciones publicadas, de la más reciente a la más antigua,
 * con los datos que necesitan los contadores. Se leen una sola vez.
 */
function crm_v3_contadores_operaciones() {

    static $lista = null;

    if ($lista !== null) {
        return $lista;
    }

    $lista = array();

    $operaciones = crm_v3_ordenar_por_fecha(
        get_posts(array(
            'post_type'      => 'operaciones',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        )),
        'fecha_de_operacion'
    );

    foreach ($operaciones as $operacion) {

        $id      = $operacion->ID;
        $estatus = crm_v3_valor_normalizado(get_field('estatus_de_operacion', $id));

        $propiedad_id = crm_v3_get_related_id(get_field('propiedad', $id));

        $lista[] = array(
            'id'           => $id,
            'estatus'      => $estatus,
            'fecha'        => crm_v3_contadores_fecha_operacion($id, $estatus),
            'comprador_id' => crm_v3_get_related_id(get_field('comprador', $id)),
            'vendedor_id'  => $propiedad_id
                ? crm_v3_get_related_id(get_field('cliente_propietario', $propiedad_id))
                : 0,
        );
    }

    return $lista;
}


/**
 * ============================================================
 * GRUPO 1 — ESTATUS DE CLIENTES Y OPERACIONES
 * ============================================================
 */
function crm_v3_contadores_clientes($periodo = 'todo') {

    $conteo = array(
        'proceso'         => 0,
        'para_relacionar' => 0,
        'relacionado'     => 0,
        'perdido'         => 0,
        'cerrado'         => 0,
    );

    // Última operación de cada cliente (como comprador o vendedor).
    $ultima = array();

    foreach (crm_v3_contadores_operaciones() as $operacion) {

        foreach (array('comprador_id', 'vendedor_id') as $rol) {

            $cliente_id = $operacion[$rol];

            if ($cliente_id && !isset($ultima[$cliente_id])) {
                $ultima[$cliente_id] = $operacion;
            }
        }
    }

    $clientes = get_posts(array(
        'post_type'      => 'clientes',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ));

    foreach ($clientes as $cliente) {

        $estatus = crm_v3_valor_normalizado(get_field('estatus', $cliente->ID));

        $registrado = get_the_date('Y-m-d', $cliente);

        if ($estatus === 'en_proceso') {

            if (crm_v3_contadores_en_periodo($registrado, $periodo)) {
                $conteo['proceso']++;
            }

            continue;
        }

        if ($estatus !== 'para_relacionar') {
            continue;
        }

        if (!isset($ultima[$cliente->ID])) {

            if (crm_v3_contadores_en_periodo($registrado, $periodo)) {
                $conteo['para_relacionar']++;
            }

            continue;
        }

        $operacion = $ultima[$cliente->ID];

        if (!crm_v3_contadores_en_periodo($operacion['fecha'], $periodo)) {
            continue;
        }

        switch ($operacion['estatus']) {

            case 'cerrada':
                $conteo['cerrado']++;
                break;

            case 'perdida':
                $conteo['perdido']++;
                break;

            default:
                $conteo['relacionado']++;
        }
    }

    return $conteo;
}


/**
 * ============================================================
 * GRUPO 2 — ETAPAS DEL PIPELINE
 * ============================================================
 */
function crm_v3_contadores_pipeline($periodo = 'todo') {

    $conteo = array(
        'expediente'  => 0,
        'valuador'    => 0,
        'institucion' => 0,
        'notaria'     => 0,
        'cerrado'     => 0,
    );

    foreach (crm_v3_contadores_operaciones() as $operacion) {

        if (!crm_v3_contadores_en_periodo($operacion['fecha'], $periodo)) {
            continue;
        }

        if ($operacion['estatus'] === 'cerrada') {
            $conteo['cerrado']++;
            continue;
        }

        if ($operacion['estatus'] === 'perdida') {
            continue;
        }

        $id = $operacion['id'];

        // De la etapa más avanzada a la primera: cuenta la primera
        // que encuentre completa.
        $etapas = array(
            'notaria'     => !empty(get_field('notaria', $id)),
            'institucion' => !empty(get_field('institucion_financiera', $id)),
            'valuador'    => !empty(get_field('valuador', $id)),
            'expediente'  => (bool) get_field('expediente_completo', $id),
        );

        foreach ($etapas as $etapa => $completa) {

            if ($completa) {
                $conteo[$etapa]++;
                break;
            }
        }
    }

    return $conteo;
}


/**
 * ============================================================
 * VISTA
 * ============================================================
 */
function crm_v3_contadores_grupo($titulo, $parametro, $periodo, $conteo, $etiquetas) {

    ?>

    <div class="crm-bo-contadores-grupo">

        <div class="crm-bo-contadores-cabecera">

            <h3><?php echo esc_html($titulo); ?></h3>

            <label class="crm-bo-contadores-periodo">
                <span>Filtrar por fecha</span>
                <select name="<?php echo esc_attr($parametro); ?>" onchange="this.form.submit()">
                    <?php foreach (crm_v3_contadores_periodos() as $clave => $nombre) : ?>
                        <option value="<?php echo esc_attr($clave); ?>" <?php selected($periodo, $clave); ?>>
                            <?php echo esc_html($nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

        </div>

        <div class="crm-bo-contadores-fila">

            <?php foreach ($etiquetas as $clave => $etiqueta) : ?>

                <div
                    class="crm-bo-contador crm-bo-contador-<?php echo esc_attr($clave); ?>"
                    title="<?php echo esc_attr($etiqueta['completo']); ?>"
                    tabindex="0"
                >
                    <span><?php echo esc_html($etiqueta['corto']); ?></span>
                    <strong><?php echo esc_html((int) $conteo[$clave]); ?></strong>
                </div>

            <?php endforeach; ?>

        </div>

    </div>

    <?php
}


function crm_v3_backoffice_contadores() {

    $periodo_clientes = crm_v3_contadores_periodo('periodo_clientes');
    $periodo_pipeline = crm_v3_contadores_periodo('periodo_pipeline');

    ?>

    <section class="crm-bo-panel crm-bo-contadores" id="crm-contadores">

        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>#crm-contadores">

            <input type="hidden" name="page" value="crm-v3-cibr">

            <div class="crm-bo-contadores-grid">

                <?php
                crm_v3_contadores_grupo(
                    'Estatus clientes y operaciones',
                    'periodo_clientes',
                    $periodo_clientes,
                    crm_v3_contadores_clientes($periodo_clientes),
                    array(
                        'proceso' => array(
                            'corto'    => 'En proceso',
                            'completo' => 'En proceso: clientes con estatus "En proceso", todavía sin operación.',
                        ),
                        'para_relacionar' => array(
                            'corto'    => 'Sin relación',
                            'completo' => 'Para relacionar (sin relación): clientes con estatus "Para relacionar" que aún no tienen operación.',
                        ),
                        'relacionado' => array(
                            'corto'    => 'Relacionado',
                            'completo' => 'Relacionado: clientes "Para relacionar" con una operación en proceso.',
                        ),
                        'perdido' => array(
                            'corto'    => 'Perdido',
                            'completo' => 'Perdido: clientes "Para relacionar" cuya operación quedó como Perdida.',
                        ),
                        'cerrado' => array(
                            'corto'    => 'Cerrado',
                            'completo' => 'Cerrado: clientes "Para relacionar" cuya operación quedó como Cerrada.',
                        ),
                    )
                );

                crm_v3_contadores_grupo(
                    'Operaciones por etapa',
                    'periodo_pipeline',
                    $periodo_pipeline,
                    crm_v3_contadores_pipeline($periodo_pipeline),
                    array(
                        'expediente' => array(
                            'corto'    => 'Expediente',
                            'completo' => 'Expediente completo: operaciones en proceso cuyo último avance es el expediente completo.',
                        ),
                        'valuador' => array(
                            'corto'    => 'Valuador',
                            'completo' => 'Valuador: operaciones en proceso cuyo último avance es el valuador asignado.',
                        ),
                        'institucion' => array(
                            'corto'    => 'Institución',
                            'completo' => 'Institución financiera: operaciones en proceso cuyo último avance es la institución financiera.',
                        ),
                        'notaria' => array(
                            'corto'    => 'Notaría',
                            'completo' => 'Notaría: operaciones en proceso que ya tienen notaría asignada.',
                        ),
                        'cerrado' => array(
                            'corto'    => 'Cerrado',
                            'completo' => 'Cerrado: operaciones con estatus Cerrada.',
                        ),
                    )
                );
                ?>

            </div>

            <noscript>
                <button type="submit" class="button">Aplicar</button>
            </noscript>

        </form>

    </section>

    <?php
}
