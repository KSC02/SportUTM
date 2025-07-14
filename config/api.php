<?php
require 'conexion.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Leer tareas
        $stmt = $conn->query("SELECT * FROM tareas ORDER BY fecha ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'POST':
        // Crear nueva tarea
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("INSERT INTO tareas (descripcion, fecha, prioridad, etiqueta) VALUES (:d, :f, :p, :e)");
        $stmt->execute([
            'd' => $data['descripcion'],
            'f' => $data['fecha'],
            'p' => $data['prioridad'],
            'e' => $data['etiqueta'],
        ]);
        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        // Eliminar tarea
        parse_str(file_get_contents("php://input"), $data);
        $stmt = $conn->prepare("DELETE FROM tareas WHERE id = :id");
        $stmt->execute(['id' => $data['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'PUT':
        // Actualizar tarea
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("UPDATE tareas SET descripcion = :d, fecha = :f, prioridad = :p, etiqueta = :e WHERE id = :id");
        $stmt->execute([
            'd' => $data['descripcion'],
            'f' => $data['fecha'],
            'p' => $data['prioridad'],
            'e' => $data['etiqueta'],
            'id' => $data['id']
        ]);
        echo json_encode(['success' => true]);
        break;
}
?>