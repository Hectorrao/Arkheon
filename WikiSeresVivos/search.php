<?php
require_once("config/database.php");
require_once("includes/medio.php");
$conn = db::conexion();

$q       = isset($_GET['q'])       ? trim($_GET['q'])       : '';
$era     = isset($_GET['era'])     ? trim($_GET['era'])     : '';
$eon     = isset($_GET['eon'])     ? trim($_GET['eon'])     : '';
$epoca   = isset($_GET['epoca'])   ? trim($_GET['epoca'])   : '';
$periodo = isset($_GET['periodo']) ? trim($_GET['periodo']) : '';
$medio   = isset($_GET['medio'])   ? trim($_GET['medio'])   : '';
$estado  = isset($_GET['estado'])  ? trim($_GET['estado'])  : '';
$orden   = isset($_GET['orden'])   ? trim($_GET['orden'])   : 'nombre';

$porPagina = 20;
$pagina = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina < 1) { $pagina = 1; }
$offset = ($pagina - 1) * $porPagina;

$mediosValidos = ['aereo', 'terrestre', 'acuatico', 'anfibio'];

$conditions = [];
$params = [];
$joins = [];

if ($q !== '') {
    $conditions[] = "(a.nombre LIKE :q OR a.nombre_cientifico LIKE :q2)";
    $params[':q'] = $q . '%';
    $params[':q2'] = $q . '%';
}
if ($era !== '')     { $conditions[] = "a.era = :era";         $params[':era'] = $era; }
if ($eon !== '')     { $conditions[] = "a.eon = :eon";         $params[':eon'] = $eon; }
if ($epoca !== '')   { $conditions[] = "a.epoca = :epoca";     $params[':epoca'] = $epoca; }

if ($periodo !== '') {
    $joins[] = "JOIN animalia_periodo ap_filter ON a.id = ap_filter.animalia_id";
    $joins[] = "JOIN periodos p_filter ON ap_filter.periodo_id = p_filter.id";
    $conditions[] = "p_filter.nombre = :periodo";
    $params[':periodo'] = $periodo;
}

if (in_array($medio, $mediosValidos, true)) {
    $conditions[] = "a.medio = :medio";
    $params[':medio'] = $medio;
}
if ($estado === 'existente')    { $conditions[] = "a.extinto = 0"; }
elseif ($estado === 'extinto') { $conditions[] = "a.extinto = 1"; }

$where = count($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
$joinClause = count($joins) ? implode(" ", array_unique($joins)) : "";

$ordenSql = "ORDER BY a.nombre ASC";
if ($orden === 'antiguo')  { $ordenSql = "ORDER BY a.end_mya DESC"; }
elseif ($orden === 'reciente') { $ordenSql = "ORDER BY a.end_mya ASC"; }

$countSql = "SELECT COUNT(DISTINCT a.id) FROM animalia a $joinClause $where";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalResultados = (int)$countStmt->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalResultados / $porPagina));

if ($pagina > $totalPaginas) { $pagina = $totalPaginas; }
$offset = ($pagina - 1) * $porPagina;

$sql = "SELECT a.*, GROUP_CONCAT(DISTINCT p.nombre ORDER BY p.nombre SEPARATOR ', ') AS periodos_lista
        FROM animalia a
        LEFT JOIN animalia_periodo ap ON a.id = ap.animalia_id
        LEFT JOIN periodos p ON ap.periodo_id = p.id
        $joinClause
        $where
        GROUP BY a.id
        $ordenSql
        LIMIT $porPagina OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filtro_label = $q ?: $era ?: $eon ?: $epoca ?: $periodo ?: 'Todos';

function buildFilterUrl($overrides = []) {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, function ($v) {
        return $v !== '' && $v !== null;
    });
    return 'search.php?' . http_build_query($params);
}

$mediosChips = [
    'aereo'     => ['icon' => 'ti-feather',  'label' => 'Volador',     'color' => '#3B82C4'],
    'terrestre' => ['icon' => 'ti-paw',      'label' => 'Terrestre', 'color' => '#875E0B'],
    'acuatico'  => ['icon' => 'ti-fish',     'label' => 'Acuático',  'color' => '#0d3d97'],
    'anfibio'   => ['icon' => 'ti-droplet',  'label' => 'Anfibio',   'color' => '#0F8A6E'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link href="/public/css/main.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <title><?= htmlspecialchars($filtro_label) ?></title>
</head>
<body>
<div class="page page--search">
    <div class="topbar">
        <nav>
            <a href="index.php" class="topbar-logo">Explorador de Especies</a>
            <div class="topbar-search">
                <input type="text" id="searchInput" placeholder="Buscar..." value="<?= htmlspecialchars($q) ?>" class="search-input small" autocomplete="off" autofill="off">
                <button id="searchButton" class="search-button small">Buscar</button>
            </div>
        </nav>
        <div class="filters-bar">
            <div class="filters-bar-left">
                <span class="filters-count"><?= $totalResultados ?> resultados</span>
                <select class="filters-sort" onchange="location.href=this.value">
                    <option value="<?= buildFilterUrl(['orden' => 'nombre', 'page' => 1]) ?>" <?= $orden === 'nombre' ? 'selected' : '' ?>>Nombre (A-Z)</option>
                    <option value="<?= buildFilterUrl(['orden' => 'reciente', 'page' => 1]) ?>" <?= $orden === 'reciente' ? 'selected' : '' ?>>Antigüedad descendente</option>
                    <option value="<?= buildFilterUrl(['orden' => 'antiguo', 'page' => 1]) ?>" <?= $orden === 'antiguo' ? 'selected' : '' ?>>Antigüedad ascendente</option>
                </select>
            </div>

            <div class="filters-bar-right">
                <a href="<?= buildFilterUrl(['medio' => '', 'page' => 1]) ?>" class="filter-chip <?= $medio === '' ? 'active' : '' ?>">
                    <i class="ti ti-apps" aria-hidden="true"></i> Todos
                </a>
                <?php foreach ($mediosChips as $key => $m): ?>
                    <a href="<?= buildFilterUrl(['medio' => $key, 'page' => 1]) ?>"
                       class="filter-chip <?= $medio === $key ? 'active' : '' ?>"
                       style="<?= $medio === $key ? 'background:' . $m['color'] . ';border-color:' . $m['color'] . ';' : 'color:' . $m['color'] . ';' ?>">
                        <i class="ti <?= $m['icon'] ?>" aria-hidden="true"></i> <?= $m['label'] ?>
                    </a>
                <?php endforeach; ?>

                <span class="filters-separator"></span>

                <a href="<?= buildFilterUrl(['estado' => '', 'page' => 1]) ?>" class="filter-chip <?= $estado === '' ? 'active' : '' ?>">Todos</a>
                <a href="<?= buildFilterUrl(['estado' => 'existente', 'page' => 1]) ?>" class="filter-chip <?= $estado === 'existente' ? 'active' : '' ?>">Existentes</a>
                <a href="<?= buildFilterUrl(['estado' => 'extinto', 'page' => 1]) ?>" class="filter-chip <?= $estado === 'extinto' ? 'active' : '' ?>">Extintos</a>
            </div>
        </div>
    </div>

    <section class="results-section" style="padding-top: 1rem;">
        <div class="back-link">
            <a href="index.php">← Volver al inicio</a>
        </div>
        <div class="results-header">
            <h2>
                <?php if ($q): ?>Resultados
                <?php elseif ($periodo): ?>Período <?= htmlspecialchars($periodo) ?>
                <?php elseif ($epoca): ?>Época: <?= htmlspecialchars($epoca) ?>
                <?php elseif ($era): ?>Era: <?= htmlspecialchars($era) ?>
                <?php elseif ($eon): ?>Eón: <?= htmlspecialchars($eon) ?>
                <?php else: ?>Todas las especies<?php endif; ?>
            </h2>
        </div>

        <?php if (empty($resultados)): ?>
            <div class="no-results">
                <p><strong>Sin datos</strong></p>
            </div>
        <?php else: ?>
            <div class="species-grid">
                <?php foreach($resultados as $animal): ?>
                    <?php $medioAnimal = medioInfo($animal['medio'] ?? null); ?>
                    <a href="animal.php?id=<?= $animal['id'] ?>" class="card">
                        <img src="/img/portadas/<?= htmlspecialchars($animal['imagen']) ?>" alt="<?= htmlspecialchars($animal['nombre']) ?>" loading="lazy" draggable="false">
                        <div class="card-body" style="background-image: url('/img/<?= $medioAnimal['key'] ?>.png');">
                            <div class="card-body-tint"></div>
                            <div class="card-body-shade"></div>
                            <div class="card-body-content">
                                <p class="species-name"><?= htmlspecialchars($animal['nombre']) ?></p>
                                <p class="species-scientific"><?= htmlspecialchars($animal['nombre_cientifico']) ?></p>
                                <p class="species-period"><?= htmlspecialchars($animal['periodos_lista'] ?? $animal['periodo']) ?></p>
                                <p class="species-epoch"><?= htmlspecialchars($animal['epoca']) ?></p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPaginas > 1): ?>
            <nav class="pagination" aria-label="Paginación de resultados">
                <a href="<?= buildFilterUrl(['page' => max(1, $pagina - 1)]) ?>"
                   class="pagination-arrow <?= $pagina <= 1 ? 'disabled' : '' ?>">‹ Anterior</a>

                <?php
                $inicio = max(1, $pagina - 2);
                $fin = min($totalPaginas, $inicio + 4);
                $inicio = max(1, $fin - 4);
                ?>

                <?php if ($inicio > 1): ?>
                    <a href="<?= buildFilterUrl(['page' => 1]) ?>" class="pagination-page">1</a>
                    <?php if ($inicio > 2): ?><span class="pagination-ellipsis">…</span><?php endif; ?>
                <?php endif; ?>

                <?php for ($p = $inicio; $p <= $fin; $p++): ?>
                    <a href="<?= buildFilterUrl(['page' => $p]) ?>" class="pagination-page <?= $p === $pagina ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>

                <?php if ($fin < $totalPaginas): ?>
                    <?php if ($fin < $totalPaginas - 1): ?><span class="pagination-ellipsis">…</span><?php endif; ?>
                    <a href="<?= buildFilterUrl(['page' => $totalPaginas]) ?>" class="pagination-page"><?= $totalPaginas ?></a>
                <?php endif; ?>

                <a href="<?= buildFilterUrl(['page' => min($totalPaginas, $pagina + 1)]) ?>"
                   class="pagination-arrow <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">Siguiente ›</a>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <footer class="footer"><p>© 2026 Explorador de Especies · Descubre la historia en la tierra</p></footer>
</div>
<script src="/public/js/main.js" defer></script>
</body>
</html>