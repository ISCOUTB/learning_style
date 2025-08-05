<?php
require_once(dirname(__FILE__)."/../../lib.php");

// Verificar que la solicitud es POST y que existe el parámetro 'id'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    // Sanitizar el valor recibido
    $id_course = filter_var($_POST['id'], FILTER_VALIDATE_INT);

    if ($id_course !== false && $id_course > 0) {
        // Llamar a la función con el ID validado
        get_metrics($id_course);
    } else {
        // Responder con error si el ID no es válido
        http_response_code(400);
        echo json_encode(["error" => "ID inválido"]);
    }
} else {
    // Responder con error si no se cumple la condición
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido o parámetro faltante"]);
}

