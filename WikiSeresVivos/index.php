<?php
require_once("config/database.php");
require_once("includes/medio.php");
$conn = db::conexion();

$stmt = $conn->prepare(
    "SELECT a.*, GROUP_CONCAT(DISTINCT p.nombre ORDER BY p.nombre SEPARATOR ', ') AS periodos_lista
     FROM animalia a
     LEFT JOIN animalia_periodo ap ON a.id = ap.animalia_id
     LEFT JOIN periodos p ON ap.periodo_id = p.id
     GROUP BY a.id
     ORDER BY RAND() 
     LIMIT 6"
);
$stmt->execute();
$animales = $stmt->fetchAll(PDO::FETCH_ASSOC);

$periodos = [
    ['key' => 'Precámbrico',  'short' => 'PreЄ', 'label' => 'Precámbrico',  'color' => '#F74370', 'width' => 'clamp(10rem, 30vw, 35rem)', 'start' => 4600, 'end' => 541],
    ['key' => 'Cámbrico',     'short' => 'Є',    'label' => 'Cámbrico',     'color' => '#7FA056', 'width' => 'clamp(8rem, 6vw, 8rem)',  'start' => 541,  'end' => 485.4],
    ['key' => 'Ordovícico',   'short' => 'O',    'label' => 'Ordovícico',   'color' => '#009270', 'width' => 'clamp(7rem, 6vw, 7rem)',  'start' => 485.4, 'end' => 443.8],
    ['key' => 'Silúrico',     'short' => 'S',    'label' => 'Silúrico',     'color' => '#B3E1B6', 'width' => 'clamp(5rem, 6vw, 5rem)',  'start' => 443.8, 'end' => 419.2],
    ['key' => 'Devónico',     'short' => 'D',    'label' => 'Devónico',     'color' => '#CB8C37', 'width' => 'clamp(9rem, 6vw, 9rem)',  'start' => 419.2, 'end' => 358.9],
    ['key' => 'Carbonífero',  'short' => 'C',    'label' => 'Carbonífero',  'color' => '#67A599', 'width' => 'clamp(9rem, 7vw, 9rem)',  'start' => 358.9, 'end' => 298.9],
    ['key' => 'Pérmico',      'short' => 'P',    'label' => 'Pérmico',      'color' => '#F04028', 'width' => 'clamp(7rem, 6vw, 7rem)',  'start' => 298.9, 'end' => 251.9],
    ['key' => 'Triásico',     'short' => 'T',    'label' => 'Triásico',     'color' => '#812B92', 'width' => 'clamp(8rem, 6vw, 8rem)',  'start' => 251.9, 'end' => 201.4],
    ['key' => 'Jurásico',     'short' => 'J',    'label' => 'Jurásico',     'color' => '#34B2C9', 'width' => 'clamp(8.5rem, 6vw, 8.5rem)',  'start' => 201.4, 'end' => 145],
    ['key' => 'Cretácico',    'short' => 'K',    'label' => 'Cretácico',    'color' => '#7FC64E', 'width' => 'clamp(9.5rem, 7vw, 9.5rem)',  'start' => 145,   'end' => 66],
    ['key' => 'Paleógeno',    'short' => 'Pg',   'label' => 'Paleógeno',    'color' => '#FD9A52', 'width' => 'clamp(9rem, 7vw, 9rem)',  'start' => 66,    'end' => 23.03],
    ['key' => 'Neógeno',      'short' => 'N',    'label' => 'Neógeno',      'color' => '#FFE619', 'width' => 'clamp(8.5rem, 6vw, 8.5rem)',  'start' => 23.03, 'end' => 2.58],
    ['key' => 'Cuaternario',  'short' => 'Q',    'label' => 'Cuaternario',  'color' => '#F9F97F', 'width' => 'clamp(9.5rem, 7vw, 9.5rem)',  'start' => 2.58,  'end' => 0],
];

$eras = [
    ['key' => 'Precámbrico', 'short' => 'PreЄ', 'label' => 'Precámbrico', 'color' => '#F74370', 'width' => 'clamp(10rem, 25vw, 35rem)', 'start' => 4600, 'end' => 541],
    ['key' => 'Paleozoico',  'short' => 'Pz',   'label' => 'Paleozoico',  'color' => '#3B8C6E', 'width' => 'clamp(12rem, 25vw, 30rem)', 'start' => 541,  'end' => 251.9],
    ['key' => 'Mesozoico',   'short' => 'Mz',   'label' => 'Mesozoico',   'color' => '#5B4B8A', 'width' => 'clamp(10rem, 20vw, 20rem)', 'start' => 251.9, 'end' => 66],
    ['key' => 'Cenozoico',   'short' => 'Cz',   'label' => 'Cenozoico',   'color' => '#E2984B', 'width' => 'clamp(10rem, 20vw, 20rem)', 'start' => 66,    'end' => 0],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/public/css/main.css" rel="stylesheet">
    <title>BioTimeline</title>
</head>
<body>
<div class="page">
    <section class="hero">
        <div class="hero-background"><div class="hero-overlay"></div></div>
        <div class="hero-content">
            <h1 class="hero-title">Explorador Global de Especies</h1>
            <p class="hero-subtitle">Descubre la historia de la vida en la Tierra</p>
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Buscar criaturas..." class="search-input" autocomplete="off">
                <div id="suggestions" class="suggestions-box" hidden></div>
                <button type="button" id="searchButton" class="search-button">Buscar</button>
            </div>

            <div class="epoch-axis">
                <div class="epoch-axis-title" id="epoch-axis-title">EXPLORAR POR PERÍODO GEOLÓGICO</div>

                <div class="epoch-axis-body">
                    <div class="epoch-axis-carousel">
                        <div class="epoch-axis-track" id="epoch-axis-track">

                            <div class="epoch-axis-panel">
                                <div class="epoch-chips" data-type="periodo">
                                    <?php foreach ($periodos as $p): ?>
                                        <button type="button"
                                            class="epoch-chip"
                                            data-key="<?= htmlspecialchars($p['key']) ?>"
                                            data-label="<?= htmlspecialchars($p['label']) ?>"
                                            data-start="<?= $p['start'] ?>"
                                            data-end="<?= $p['end'] ?>"
                                            style="background: <?= htmlspecialchars($p['color']) ?>;
                                                width: <?= htmlspecialchars($p['width']) ?>;"
                                            title="<?= htmlspecialchars($p['label']) ?>: <?= $p['start'] ?> - <?= $p['end'] == 0 ? 'Actualidad' : $p['end'] ?> millones de años">
                                            <span class="epoch-chip-short"><?= htmlspecialchars($p['short']) ?></span>
                                            <span class="epoch-chip-label"><?= htmlspecialchars($p['label']) ?></span>
                                            <span class="epoch-chip-dates"><?= $p['end'] == 0 ? $p['start'] . ' Ma - Actualidad' : $p['start'] . ' - ' . $p['end'] . 'Ma' ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="epoch-axis-panel">
                                <div class="epoch-chips" data-type="era">
                                    <?php foreach ($eras as $e): ?>
                                        <button type="button"
                                            class="epoch-chip"
                                            data-key="<?= htmlspecialchars($e['key']) ?>"
                                            data-label="<?= htmlspecialchars($e['label']) ?>"
                                            data-start="<?= $e['start'] ?>"
                                            data-end="<?= $e['end'] ?>"
                                            style="background: <?= htmlspecialchars($e['color']) ?>;
                                                width: <?= htmlspecialchars($e['width']) ?>;"
                                            title="<?= htmlspecialchars($e['label']) ?>: <?= $e['start'] ?> - <?= $e['end'] == 0 ? 'Actualidad' : $e['end'] ?> millones de años">
                                            <span class="epoch-chip-short"><?= htmlspecialchars($e['short']) ?></span>
                                            <span class="epoch-chip-label"><?= htmlspecialchars($e['label']) ?></span>
                                            <span class="epoch-chip-dates"><?= $e['end'] == 0 ? $e['start'] . ' Ma - Actualidad' : $e['start'] . ' - ' . $e['end'] . 'Ma' ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <button type="button" id="epoch-axis-arrow" class="epoch-axis-arrow" aria-label="Ver eras geológicas">
                        <span class="epoch-axis-arrow-icon">&rsaquo;</span>
                    </button>
                </div>

                <div class="epoch-axis-footer">
                    <div class="epoch-axis-info">
                        <span id="epoch_label">Período seleccionado:</span> <strong id="epoch_name">Ninguno</strong>
                    </div>
                    <form action="search.php" method="get" id="epoch-form">
                        <input type="hidden" name="periodo" id="selected_periodo" value="">
                        <input type="hidden" name="era" id="selected_era" value="">
                        <button type="submit" id="submit_epoch" class="epoch-axis-submit" disabled>Ver criaturas</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="info">
        <div class="text">
            <h2>Explora el Árbol de la Vida</h2>
            <p>Desde los primeros organismos unicelulares hasta la diversidad de la vida actual, nuestro planeta ha sido testigo de una evolución extraordinaria. Cada especie cuenta una historia única de adaptación, supervivencia y transformación a lo largo de millones de años.</p>
            <p>Selecciona un período o una era geológica en el eje superior para descubrir las criaturas que habitaron en esa época.</p>
        </div>
        <div class="species-preview">
            <?php foreach ($animales as $animal): ?>
                <?php $medio = medioInfo($animal['medio'] ?? null); ?>
                <a href="animal.php?id=<?= (int)$animal['id'] ?>" class="card">
                    <img src="/img/portadas/<?= htmlspecialchars($animal['imagen']) ?>" alt="<?= htmlspecialchars($animal['nombre']) ?>" loading="lazy" draggable="false">
                    <div class="card-body" style="background-image: url('/img/<?= $medio['key'] ?>.png');">
                        <div class="card-body-shade"></div>
                        <div class="card-body-tint"></div>
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
    </section>

    <footer class="footer"><p>© 2026 Explorador de Especies · Descubre la historia en la tierra</p></footer>
</div>
<script src="/public/js/main.js" defer></script>
</body>
</html>