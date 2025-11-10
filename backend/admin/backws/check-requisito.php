<?php
header('Content-Type: application/json');
require_once '../clases/class_conexion.php';

$conexion = new Conexion();
$con = $conexion->getConexion();

$response = [];

// 1. Verificar el requisito 467
$query1 = "SELECT * FROM requisito WHERE requisito_id = 467";
$result1 = mysqli_query($con, $query1);
if ($result1 && mysqli_num_rows($result1) > 0) {
    $response['requisito'] = mysqli_fetch_assoc($result1);
} else {
    $response['requisito'] = 'No encontrado';
}

// 2. Verificar la lista 3104
$query2 = "SELECT * FROM lista WHERE lista_id = 3104";
$result2 = mysqli_query($con, $query2);
if ($result2 && mysqli_num_rows($result2) > 0) {
    $response['lista_3104'] = mysqli_fetch_assoc($result2);
} else {
    $response['lista_3104'] = 'No encontrado';
}

// 3. Verificar si el JOIN funciona
$query3 = "SELECT r.requisito_id, r.l_requisitolista_id, lr.lista_nombre, lr.tipolista_id, lr.lista_activo, lr.lista_eliminado
           FROM requisito r
           LEFT JOIN lista lr ON r.l_requisitolista_id = lr.lista_id
           WHERE r.requisito_id = 467";
$result3 = mysqli_query($con, $query3);
if ($result3 && mysqli_num_rows($result3) > 0) {
    $response['join_test'] = mysqli_fetch_assoc($result3);
}

// 4. Probar el query exacto del endpoint
$query4 = "SELECT r.requisito_id
           FROM lista lr
           INNER JOIN requisito r ON r.l_requisitolista_id = lr.lista_id
           WHERE lr.lista_activo = '1'
           AND lr.tipolista_id = '49'
           AND r.requisito_eliminado = '0'
           AND r.requisito_id = 467";
$result4 = mysqli_query($con, $query4);
if ($result4 && mysqli_num_rows($result4) > 0) {
    $response['query_exacto'] = 'SÍ APARECE';
} else {
    $response['query_exacto'] = 'NO APARECE';
    $response['query_exacto_error'] = mysqli_error($con);
}

echo json_encode($response, JSON_PRETTY_PRINT);
mysqli_close($con);
?>
