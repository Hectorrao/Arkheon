<?php
require_once("config/database.php");
$conn = db::conexion();

function formatYears($mya) {
    if ($mya === null || $mya === '') {
        return '';
    }
    
    if ($mya == 0) {
        return 'Presente';
    }
    
    $mya = (float)$mya;
    
    if ($mya >= 1) {
        if (abs($mya - round($mya)) < 0.01) {
            return number_format(round($mya), 0, ',', '.') . ' Ma';
        } else {
            return number_format($mya, 2, ',', '.') . ' Ma';
        }
    } 
    elseif ($mya >= 0.001) {
        $años = $mya * 1000000;
        if (abs($años - round($años)) < 1) {
            return number_format(round($años), 0, ',', '.') . ' años';
        } else {
            return number_format($años, 0, ',', '.') . ' años';
        }
    }
    else {
        $años = $mya * 1000000;
        return number_format($años, 0, ',', '.') . ' años';
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare(
    "SELECT a.*, GROUP_CONCAT(DISTINCT p.nombre ORDER BY p.nombre SEPARATOR ', ') AS periodos_lista
     FROM animalia a
     LEFT JOIN animalia_periodo ap ON a.id = ap.animalia_id
     LEFT JOIN periodos p ON ap.periodo_id = p.id
     WHERE a.id = :id
     GROUP BY a.id
     LIMIT 1"
);
$stmt->execute([':id' => $id]);
$animal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$animal) {
    header("Location: index.php");
    exit;
}

$stmt_periodos = $conn->prepare(
    "SELECT p.nombre FROM periodos p 
     JOIN animalia_periodo ap ON p.id = ap.periodo_id 
     WHERE ap.animalia_id = :id
     ORDER BY p.nombre"
);
$stmt_periodos->execute([':id' => $id]);
$periodos_animal = $stmt_periodos->fetchAll(PDO::FETCH_COLUMN);

$stmt2b = $conn->prepare("SELECT * FROM animalia_bloques WHERE animal_id = :id ORDER BY seccion, orden ASC");
$stmt2b->execute([':id' => $id]);
$bloques_raw = $stmt2b->fetchAll(PDO::FETCH_ASSOC);

$bloques = [];
foreach ($bloques_raw as $b) {
    $bloques[$b['seccion']][] = $b;
}

$stmt3 = $conn->prepare(
    "SELECT a.id, a.nombre, a.nombre_cientifico, a.imagen, a.extinto,
            GROUP_CONCAT(DISTINCT p.nombre ORDER BY p.nombre SEPARATOR ', ') AS periodos_lista
     FROM animalia a
     JOIN animalia_periodo ap ON a.id = ap.animalia_id
     JOIN periodos p ON ap.periodo_id = p.id
     WHERE ap.periodo_id IN (
         SELECT periodo_id FROM animalia_periodo WHERE animalia_id = :id
     )
     AND a.id != :id2
     GROUP BY a.id
     ORDER BY RAND() 
     LIMIT 4"
);
$stmt3->execute([':id' => $id, ':id2' => $id]);
$relacionados = $stmt3->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($animal['nombre']); ?> (<?php echo htmlspecialchars($animal['nombre_cientifico']); ?>) - Explorador de Especies.">
    <link href="/public/css/main.css" rel="stylesheet">
    <title><?php echo htmlspecialchars($animal['nombre']); ?> · Explorador de Especies</title>
</head>
<body>
<div class="page">
    <div class="topbar">
        <nav>
            <a href="index.php" class="topbar-logo">Explorador de Especies</a>
            <div class="topbar-search">
                <input type="text" id="searchInput" placeholder="Buscar..." class="search-input small" autocomplete="off" autofill="off"/>
                <button id="searchButton" class="search-button small">Buscar</button>
            </div>
        </nav>
    </div>

    <section class="animal-hero" style="background-image: url('/img/portadas/<?php echo htmlspecialchars($animal['imagen']); ?>');">
        <div class="animal-hero-overlay"></div>
        <div class="animal-hero-content">
            <h1><?php echo htmlspecialchars($animal['nombre']); ?></h1>
            <p class="animal-scientific"><?php echo htmlspecialchars($animal['nombre_cientifico']); ?></p>
            <p class="animal-period">
                <?php echo htmlspecialchars($animal['era']); ?> ·
                <?php foreach($periodos_animal as $idx => $per): ?>
                    <a href="search.php?periodo=<?php echo urlencode($per); ?>" style="color:inherit;text-decoration:underline;">
                        <?php echo htmlspecialchars($per); ?>
                    </a><?php echo $idx < count($periodos_animal) - 1 ? ', ' : ''; ?>
                <?php endforeach; ?> ·
                <?php echo htmlspecialchars($animal['epoca']); ?>
                <?php if($animal['start_mya'] !== null || $animal['end_mya'] !== null): ?>
                    <br>
                    <small>
                        <?php
                        $start = $animal['start_mya'];
                        $end = $animal['end_mya'];
                        
                        if ($start !== null && $start !== '') {
                            echo "~" . formatYears($start);
                        }
                        
                        if ($start !== null && $start !== '' && $end !== null && $end !== '') {
                            echo ' - ';
                        }
                        
                        if ($end !== null && $end !== '') {
                            if ($end == 0) {
                                echo 'Presente';
                            } else {
                                echo "~" . formatYears($end);
                            }
                        }
                        ?>
                    </small>
                <?php endif; ?>
            </p>
        </div>
    </section>

    <div class="animal-layout">
        <main class="animal-main">
            <?php
            $secciones = [
                'taxonomia_etimologia'     => 'Taxonomía y etimología',
                'descripcion_morfologia'   => 'Descripción y morfología',
                'habitat_distribucion'     => 'Hábitat y distribución',
                'comportamiento_ecologia'  => 'Comportamiento y ecología',
                'reproduccion_ciclo'       => 'Reproducción y ciclo de vida',
                'conservacion_amenazas'    => 'Conservación y amenazas',
                'extincion_descubrimiento' => 'Extinción y descubrimiento',
            ];

            $hay_contenido = false;
            ?>

            <?php foreach($secciones as $campo => $titulo): ?>

                <?php if(!empty($bloques[$campo])): ?>
                    <?php $hay_contenido = true; ?>
                    <section class="wiki-section">
                        <h2><?php echo $titulo; ?></h2>
                        <?php foreach($bloques[$campo] as $bloque): ?>
                            <?php
                            $tieneImagen = !empty($bloque['imagen']);
                            $tamano = $bloque['imagen_tamano'] ?? 'mediana';
                            $claseTamano = in_array($tamano, ['pequena', 'mediana', 'grande']) ? $tamano : 'mediana';
                            ?>
                            <div class="content-block<?php echo $tieneImagen ? '' : ' no-image'; ?>">
                                <?php if($tieneImagen): ?>
                                <div class="content-block-image content-block-image--<?php echo $claseTamano; ?>">
                                    <img src="/img/<?php echo htmlspecialchars($bloque['imagen']); ?>"
                                         alt="<?php echo htmlspecialchars($bloque['leyenda'] ?: $titulo); ?>" 
                                         draggable="false" loading="lazy">
                                    <?php if(!empty($bloque['leyenda'])): ?>
                                        <p class="content-block-caption"><?php echo htmlspecialchars($bloque['leyenda']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <div class="content-block-text">
                                    <?php echo nl2br(htmlspecialchars($bloque['texto'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

            <?php endforeach; ?>

            <?php if(!$hay_contenido): ?>
                <div class="no-info-notice">
                    <p>La información detallada de esta especie aún no está disponible.</p>
                </div>
            <?php endif; ?>
        </main>

        <aside class="animal-sidebar">
            <div class="sidebar-card">
                <img src="/img/portadas/<?php echo htmlspecialchars($animal['imagen']); ?>" alt="<?php echo htmlspecialchars($animal['nombre']); ?>" class="sidebar-img" draggable="false" loading="lazy">
                <p class="sidebar-caption"><?php echo htmlspecialchars($animal['nombre_cientifico']); ?></p>
            </div>

            <div class="sidebar-taxon-card">
                <h3>Datos taxonómicos</h3>
                <table class="taxon-table-compact">
                    <tr><th>Reino</th><td><?php echo htmlspecialchars($animal['reino'] ?? ' - '); ?></td></tr>
                    <tr><th>Filo</th><td><?php echo htmlspecialchars($animal['filo'] ?? ' - '); ?></td></tr>
                    <tr><th>Clase</th><td><?php echo htmlspecialchars($animal['clase'] ?? ' - '); ?></td></tr>
                    <tr><th>Orden</th><td><?php echo htmlspecialchars($animal['orden_tax'] ?? ' - '); ?></td></tr>
                    <tr><th>Familia</th><td><?php echo htmlspecialchars($animal['familia'] ?? ' - '); ?></td></tr>
                    <tr><th>Género</th><td><em><?php echo htmlspecialchars($animal['genero'] ?? ' - '); ?></em></td></tr>
                    <tr><th>Especie</th><td><em><?php echo htmlspecialchars($animal['especie'] ?? ' - '); ?></em></td></tr>
                    <tr><th>Eón</th><td><?php echo htmlspecialchars($animal['eon'] ?? ' - '); ?></td></tr>
                    <tr><th>Era</th><td><?php echo htmlspecialchars($animal['era'] ?? ' - '); ?></td></tr>
                    <tr><th>Período(s)</th><td>
                        <?php echo htmlspecialchars($animal['periodos_lista'] ?? $animal['periodo'] ?? ' - '); ?>
                    </td></tr>
                    <tr><th>Época</th><td><?php echo htmlspecialchars($animal['epoca'] ?? ' - '); ?></td></tr>
                    <tr><th>Edad</th><td><?php echo htmlspecialchars($animal['edad'] ?? ' - '); ?></td></tr>
                    <tr><th>Estado</th><td><?php echo $animal['extinto'] ? '† <strong>Extinto</strong>' : '✅ Existente'; ?></td></tr>
                </table>
            </div>

            <?php if(!empty($relacionados)): ?>
            <div class="sidebar-related">
                <h3>Criaturas del mismo período</h3>
                <?php foreach($relacionados as $rel): ?>
                <a href="animal.php?id=<?php echo $rel['id']; ?>" class="related-item">
                    <img src="/img/portadas/<?php echo htmlspecialchars($rel['imagen']); ?>" alt="<?php echo htmlspecialchars($rel['nombre']); ?>" draggable="false" loading="lazy">
                    <div>
                        <p><?php echo htmlspecialchars($rel['nombre']); ?></p>
                        <small><?php echo htmlspecialchars($rel['nombre_cientifico']); ?></small>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php foreach($periodos_animal as $per): ?>
            <a href="search.php?periodo=<?php echo urlencode($per); ?>" class="btn-era" style="margin-bottom: 0.5rem;">
                Ver todas las criaturas del <?php echo htmlspecialchars($per); ?>
            </a>
            <?php endforeach; ?>
        </aside>
    </div>

    <div class="back-link" style="padding: 0 2rem 2rem;">
        <a href="index.php">← Volver al inicio</a>
    </div>

    <footer class="footer">
        <p>© 2026 Explorador de Especies · Descubre el pasado, comprende el presente</p>
    </footer>
</div>
<script src="/public/js/main.js" defer></script>
</body>
</html>