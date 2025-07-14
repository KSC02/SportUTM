<?php
require 'conexion.php';
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['nombre_usuario']);
    $clave = trim($_POST['contraseña']);

    if (strlen($usuario) < 4 || strlen($clave) < 4) {
        echo json_encode(["success" => false, "message" => "❌ Usuario y contraseña deben tener al menos 4 caracteres."]);
        exit;
    }

    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);

    // Verificar si existe
    $stmt = $conn->prepare("SELECT 1 FROM usuarios WHERE nombre_usuario = :usuario");
    $stmt->execute(['usuario' => $usuario]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => false, "message" => "❌ El usuario ya existe."]);
    } else {
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre_usuario, contraseña) VALUES (:usuario, :clave)");
        $stmt->execute(['usuario' => $usuario, 'clave' => $clave_hash]);
        echo json_encode(["success" => true, "message" => "✅ Usuario registrado correctamente."]);
    }
}