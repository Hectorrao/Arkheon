<?php
header("Content-Type: application/json");

require_once("config/database.php");
$conn = db::conexion();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, nombre, nombre_cientifico, imagen
    FROM animalia
    WHERE nombre LIKE :q OR nombre_cientifico LIKE :q2
    ORDER BY nombre ASC
");

$q_param = $q . '%';

$stmt->execute([
    ':q' => $q_param,
    ':q2' => $q_param
]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
?>