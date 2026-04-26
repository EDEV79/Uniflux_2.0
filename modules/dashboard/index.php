<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_capability('dashboard.view');

$pdo = app_pdo();
$dashboardRestricted = !current_user_can('solicitudes.manage') && !current_user_can('clientes.manage');
$dashboardUserId = current_user_id();

if (!function_exists('dashboard_sum_first_existing')) {
    function dashboard_sum_first_existing(PDO $pdo, $tableName, array $candidateColumns, $restricted = false, $userId = 0)
    {
        if (!app_table_exists($pdo, $tableName)) {
            return 0;
        }

        foreach ($candidateColumns as $columnName) {
            if (!app_column_exists($pdo, $tableName, $columnName)) {
                continue;
            }

            $sql = 'SELECT COALESCE(SUM(' . $columnName . '), 0) AS total_amount FROM ' . $tableName;
            if ($restricted && app_column_exists($pdo, $tableName, 'user_id')) {
                $sql .= ' WHERE user_id = :user_id';
                $statement = $pdo->prepare($sql);
                $statement->execute(array('user_id' => $userId));
                return (float) $statement->fetchColumn();
            }

            $statement = $pdo->query($sql);
            return (float) $statement->fetchColumn();
        }

        return 0;
    }
}

if (!function_exists('dashboard_count_records')) {
    function dashboard_count_records(PDO $pdo, $tableName, $restricted = false, $userId = 0)
    {
        if (!app_table_exists($pdo, $tableName)) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) FROM ' . $tableName;
        if ($restricted && app_column_exists($pdo, $tableName, 'user_id')) {
            $sql .= ' WHERE user_id = :user_id';
            $statement = $pdo->prepare($sql);
            $statement->execute(array('user_id' => $userId));
            return (int) $statement->fetchColumn();
        }

        $statement = $pdo->query($sql);
        return (int) $statement->fetchColumn();
    }
}

$totalIngresos = dashboard_sum_first_existing($pdo, 'eventos', array('precio_show'), $dashboardRestricted, $dashboardUserId);
$totalGastos = dashboard_sum_first_existing($pdo, 'gastos', array('total', 'monto', 'importe'), $dashboardRestricted, $dashboardUserId);
$balance = $totalIngresos - $totalGastos;
$totalComprobantes = dashboard_count_records($pdo, 'eventos', $dashboardRestricted, $dashboardUserId);
$totalSolicitudes = dashboard_count_records($pdo, 'solicitudes');

$recentComprobantes = array();
if (app_table_exists($pdo, 'eventos')) {
    $recentSql = 'SELECT id, nombre, fecha, lugar, precio_show FROM eventos';
    if ($dashboardRestricted && app_column_exists($pdo, 'eventos', 'user_id')) {
        $recentSql .= ' WHERE user_id = :user_id ORDER BY id DESC LIMIT 5';
        $recentStatement = $pdo->prepare($recentSql);
        $recentStatement->execute(array('user_id' => $dashboardUserId));
    } else {
        $recentSql .= ' ORDER BY id DESC LIMIT 5';
        $recentStatement = $pdo->query($recentSql);
    }
    $recentComprobantes = $recentStatement->fetchAll();
}

// ── Chart data ─────────────────────────────────────────────────────────────────
$chartBarLabels = array();
$chartBarEventCount = array();
$chartBarTotalIngresos = array();
$chartBarTotalGastos = array();

$monthsMap = array();
$baseMonth = new DateTime('first day of -11 months');
for ($i = 0; $i < 12; $i++) {
    $monthDate = clone $baseMonth;
    $monthDate->modify('+' . $i . ' month');
    $monthKey = $monthDate->format('Y-m');
    $monthsMap[$monthKey] = array(
        'label' => $monthDate->format('M Y'),
        'count' => 0,
        'total' => 0.0,
        'gastos' => 0.0,
    );
}

if (app_table_exists($pdo, 'eventos')) {
    $barSql = "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes,
                COUNT(*) AS eventos,
                COALESCE(SUM(precio_show), 0) AS total
         FROM eventos
         WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
           AND fecha IS NOT NULL";
    if ($dashboardRestricted && app_column_exists($pdo, 'eventos', 'user_id')) {
        $barSql .= " AND user_id = :user_id";
    }
    $barSql .= "
         GROUP BY mes
         ORDER BY mes ASC";

    if ($dashboardRestricted && app_column_exists($pdo, 'eventos', 'user_id')) {
        $barStmt = $pdo->prepare($barSql);
        $barStmt->execute(array('user_id' => $dashboardUserId));
    } else {
        $barStmt = $pdo->query($barSql);
    }

    foreach ($barStmt->fetchAll() as $row) {
        $monthKey = $row['mes'];
        if (isset($monthsMap[$monthKey])) {
            $monthsMap[$monthKey]['count'] = (int) $row['eventos'];
            $monthsMap[$monthKey]['total'] = (float) $row['total'];
        }
    }
}

if (app_table_exists($pdo, 'gastos') && app_column_exists($pdo, 'gastos', 'fecha')) {
    $expenseColumn = null;
    foreach (array('total', 'monto', 'importe') as $candidateColumn) {
        if (app_column_exists($pdo, 'gastos', $candidateColumn)) {
            $expenseColumn = $candidateColumn;
            break;
        }
    }

    if ($expenseColumn !== null) {
        $expenseSql = "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes,
                    COALESCE(SUM(" . $expenseColumn . "), 0) AS total_gastos
             FROM gastos
             WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
               AND fecha IS NOT NULL";
        if ($dashboardRestricted && app_column_exists($pdo, 'gastos', 'user_id')) {
            $expenseSql .= " AND user_id = :user_id";
        }
        $expenseSql .= "
             GROUP BY mes
             ORDER BY mes ASC";

        if ($dashboardRestricted && app_column_exists($pdo, 'gastos', 'user_id')) {
            $expenseStmt = $pdo->prepare($expenseSql);
            $expenseStmt->execute(array('user_id' => $dashboardUserId));
        } else {
            $expenseStmt = $pdo->query($expenseSql);
        }

        foreach ($expenseStmt->fetchAll() as $row) {
            $monthKey = $row['mes'];
            if (isset($monthsMap[$monthKey])) {
                $monthsMap[$monthKey]['gastos'] = (float) $row['total_gastos'];
            }
        }
    }
}

foreach ($monthsMap as $monthInfo) {
    $chartBarLabels[] = $monthInfo['label'];
    $chartBarEventCount[] = $monthInfo['count'];
    $chartBarTotalIngresos[] = $monthInfo['total'];
    $chartBarTotalGastos[] = $monthInfo['gastos'];
}

$chartPieLabels = array('Cantidad de eventos', 'Ganancias', 'Gastos');
$chartPieRawData = array(
    (int) $totalComprobantes,
    (float) $totalIngresos,
    (float) $totalGastos,
);

// Scale money metrics to "thousands" so the event slice remains visible.
$chartPieData = array(
    (int) $totalComprobantes,
    round(((float) $totalIngresos) / 1000, 2),
    round(((float) $totalGastos) / 1000, 2),
);

$pageTitle = 'Dashboard | ' . app_name();
$pageHeading = 'Dashboard';
$pageDescription = '';
$currentModule = 'dashboard';
$pageActions = array();

if (current_user_can('comprobantes.manage')) {
    $pageActions[] = array(
        'label' => 'Nuevo comprobante',
        'href' => 'comprobantes.php?action=create',
        'icon' => 'fa-solid fa-plus',
        'class' => 'btn-primary',
    );
}

if (current_user_can('solicitudes.manage')) {
    $pageActions[] = array(
        'label' => 'Ver solicitudes',
        'href' => 'solicitudes.php',
        'icon' => 'fa-solid fa-list-check',
        'class' => 'btn-outline-secondary',
    );
}

include APP_ROOT . '/includes/header.php';
?>

<section class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
        <article class="metric-card metric-card-income crm-animate-in">
            <span>Total ingresos</span>
            <h3><?php echo e(format_currency($totalIngresos)); ?></h3>
        </article>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <article class="metric-card metric-card-expense crm-animate-in">
            <span>Total gastos</span>
            <h3><?php echo e(format_currency($totalGastos)); ?></h3>
        </article>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <article class="metric-card metric-card-balance crm-animate-in">
            <span>Balance</span>
            <h3><?php echo e(format_currency($balance)); ?></h3>
        </article>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <article class="metric-card crm-animate-in">
            <span>Comprobantes</span>
            <h3><?php echo e(number_format($totalComprobantes)); ?></h3>
        </article>
    </div>
</section>

<section class="row g-4 mb-4">
    <!-- Bar chart: eventos, ingresos y gastos por mes -->
    <div class="col-12 col-xl-7">
        <article class="surface-card h-100 crm-animate-in">
            <div class="surface-card__header">
                <div>
                    <h2 class="surface-card__title">Eventos, ganancias y gastos por mes</h2>
                    <p class="surface-card__subtitle">Ultimo ano: cantidad de eventos, ingresos y gastos totales.</p>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartIngresosMes" aria-label="Grafica de eventos, ganancias y gastos por mes" role="img"></canvas>
            </div>
        </article>
    </div>
    <!-- Pie chart: eventos / ganancias / gastos -->
    <div class="col-12 col-xl-5">
        <article class="surface-card h-100 crm-animate-in">
            <div class="surface-card__header">
                <div>
                    <h2 class="surface-card__title">Eventos, ganancias y gastos</h2>
                    <p class="surface-card__subtitle">Vista resumida de indicadores principales.</p>
                </div>
            </div>
            <div class="chart-wrapper chart-wrapper--pie">
                <canvas id="chartLugares" aria-label="Grafica de eventos, ganancias y gastos" role="img"></canvas>
            </div>
        </article>
    </div>
</section>

<section class="row g-4">
    <div class="col-12">
        <article class="surface-card crm-animate-in">
            <div class="surface-card__header">
                <div>
                    <h2 class="surface-card__title">Actividad reciente</h2>
                    <p class="surface-card__subtitle">Ultimos comprobantes creados.</p>
                </div>
                <a class="btn btn-sm btn-outline-primary" href="<?php echo e(app_url('comprobantes.php')); ?>">
                    <i class="fa-solid fa-list me-1"></i>Ver todos
                </a>
            </div>
            <?php if (empty($recentComprobantes)) : ?>
                <div class="empty-state">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>No hay comprobantes registrados todavia.</p>
                </div>
            <?php else : ?>
                <div class="activity-list">
                    <?php foreach ($recentComprobantes as $item) : ?>
                        <div class="activity-list__item">
                            <div class="activity-list__icon"><i class="bi bi-receipt"></i></div>
                            <div class="activity-list__body">
                                <strong><?php echo e($item['nombre']); ?></strong>
                                <small><?php echo e(!empty($item['fecha']) && function_exists('TraeFechaExplode') ? TraeFechaExplode($item['fecha']) : $item['fecha']); ?> &middot; <?php echo e($item['lugar']); ?></small>
                            </div>
                            <div class="activity-list__amount"><?php echo e(format_currency($item['precio_show'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    </div>
</section>

<script>
    (function() {
        var barLabels = <?php echo json_encode($chartBarLabels, JSON_UNESCAPED_UNICODE); ?>;
        var barEventCount = <?php echo json_encode($chartBarEventCount); ?>;
        var barIngresos = <?php echo json_encode($chartBarTotalIngresos); ?>;
        var barGastos = <?php echo json_encode($chartBarTotalGastos); ?>;
        var pieLabels = <?php echo json_encode($chartPieLabels, JSON_UNESCAPED_UNICODE); ?>;
        var pieData = <?php echo json_encode($chartPieData); ?>;
        var pieRawData = <?php echo json_encode($chartPieRawData); ?>;
        var hasInitialized = false;

        function initCharts() {
            if (hasInitialized) {
                return;
            }

            if (typeof window.Chart === 'undefined') {
                return;
            }

            hasInitialized = true;

            var chartDefaults = {
                color: '#94a3b8',
                font: {
                    family: 'Manrope, sans-serif',
                    size: 13
                },
            };
            Chart.defaults.color = chartDefaults.color;
            Chart.defaults.font.family = chartDefaults.font.family;
            Chart.defaults.font.size = chartDefaults.font.size;

            var ctxBar = document.getElementById('chartIngresosMes');
            if (ctxBar) {
                new Chart(ctxBar, {
                    type: 'bar',
                    data: {
                        labels: barLabels,
                        datasets: [{
                            label: 'Cantidad de eventos',
                            data: barEventCount,
                            yAxisID: 'y',
                            backgroundColor: 'rgba(16,185,129,0.55)',
                            borderColor: '#10b981',
                            borderWidth: 1,
                            borderRadius: 8,
                            borderSkipped: false,
                        }, {
                            label: 'Ganancias',
                            data: barIngresos,
                            yAxisID: 'y1',
                            backgroundColor: 'rgba(59,130,246,0.55)',
                            borderColor: '#3b82f6',
                            borderWidth: 1,
                            borderRadius: 8,
                            borderSkipped: false,
                        }, {
                            label: 'Gastos',
                            data: barGastos,
                            yAxisID: 'y1',
                            backgroundColor: 'rgba(239,68,68,0.55)',
                            borderColor: '#ef4444',
                            borderWidth: 1,
                            borderRadius: 8,
                            borderSkipped: false,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                labels: {
                                    color: '#94a3b8',
                                    usePointStyle: true,
                                },
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        if (ctx.dataset.yAxisID === 'y') {
                                            return ' Eventos: ' + ctx.parsed.y;
                                        }
                                        return ' ' + new Intl.NumberFormat('es-CR', {
                                            style: 'currency',
                                            currency: 'CRC',
                                            minimumFractionDigits: 0
                                        }).format(ctx.parsed.y);
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: 'rgba(148,163,184,0.08)'
                                },
                                ticks: {
                                    color: '#94a3b8'
                                },
                            },
                            y: {
                                grid: {
                                    color: 'rgba(148,163,184,0.08)'
                                },
                                ticks: {
                                    color: '#94a3b8',
                                },
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Eventos',
                                    color: '#94a3b8',
                                },
                            },
                            y1: {
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false,
                                },
                                ticks: {
                                    color: '#94a3b8',
                                    callback: function(val) {
                                        return new Intl.NumberFormat('es-CR', {
                                            notation: 'compact',
                                            compactDisplay: 'short'
                                        }).format(val);
                                    },
                                },
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Ganancias',
                                    color: '#94a3b8',
                                },
                            },
                        },
                    },
                });
            }

            var ctxPie = document.getElementById('chartLugares');
            if (ctxPie) {
                var pieColors = ['#10b981', '#3b82f6', '#ef4444'];
                new Chart(ctxPie, {
                    type: 'doughnut',
                    data: {
                        labels: pieLabels,
                        datasets: [{
                            data: pieData,
                            backgroundColor: pieColors.slice(0, pieData.length),
                            borderColor: '#111827',
                            borderWidth: 3,
                            hoverOffset: 8,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#94a3b8',
                                    padding: 16,
                                    usePointStyle: true,
                                    pointStyleWidth: 10,
                                },
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        var total = ctx.dataset.data.reduce(function(a, b) {
                                            return a + b;
                                        }, 0);
                                        var pct = total > 0 ? Math.round(ctx.parsed * 100 / total) : 0;
                                        if (ctx.label === 'Cantidad de eventos') {
                                            return ' ' + ctx.label + ': ' + pieRawData[ctx.dataIndex] + ' (' + pct + '%)';
                                        }

                                        return ' ' + ctx.label + ': ' + new Intl.NumberFormat('es-CR', {
                                            style: 'currency',
                                            currency: 'CRC',
                                            minimumFractionDigits: 0
                                        }).format(pieRawData[ctx.dataIndex]) + ' (' + pct + '%)';
                                    },
                                },
                            },
                        },
                    },
                });
            }
        }

        window.addEventListener('load', initCharts);
        // Retry corto por si hay carga diferida o cache lenta del CDN.
        setTimeout(initCharts, 250);
        setTimeout(initCharts, 700);
    }());
</script>

<?php include APP_ROOT . '/includes/footer.php'; ?>
