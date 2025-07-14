<?php
session_start();
require '../config/conexion.php';

// Validar sesión
if (!isset($_SESSION['usuario'])) {
  die("❌ Debes iniciar sesión.");
}

// Validar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  die("❌ Método inválido.");
}

// Validar datos principales
$nombre_equipo = trim($_POST['nombre_equipo']);
$facultad = trim($_POST['facultad']);
$integrantes_json = $_POST['integrantes_json'] ?? '';
$evento_id = intval($_GET['evento'] ?? 0);
$deporte_id = intval($_GET['deporte'] ?? 0);

// Validaciones mínimas
if (strlen($nombre_equipo) < 3 || strlen($facultad) < 3 || !$integrantes_json) {
  die("❌ Datos incompletos.");
}

// Convertir JSON de integrantes a arreglo
$integrantes = json_decode($integrantes_json, true);
if (!is_array($integrantes) || count($integrantes) < 1) {
  die("❌ Debes agregar al menos un integrante.");
}

// Buscar el evento_deporte_id
$stmt = $conn->prepare("SELECT id FROM eventos_deportes WHERE evento_id = :evento AND deporte_id = :deporte");
$stmt->execute([
  'evento' => $evento_id,
  'deporte' => $deporte_id
]);
$relacion = $stmt->fetch();
if (!$relacion) {
  die("❌ Combinación evento-deporte inválida.");
}
$evento_deporte_id = $relacion['id'];

// Buscar ID de usuario actual (para asignarlo como capitán)
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE nombre_usuario = :usuario");
$stmt->execute(['usuario' => $_SESSION['usuario']]);
$usuario = $stmt->fetch();
if (!$usuario) {
  die("❌ Usuario no válido.");
}
$usuario_id = $usuario['id'];

// Guardar el equipo
$stmt = $conn->prepare("INSERT INTO equipos (nombre_equipo, facultad, evento_deporte_id)
                        VALUES (:nombre, :facultad, :edid) RETURNING id");
$stmt->execute([
  'nombre' => $nombre_equipo,
  'facultad' => $facultad,
  'edid' => $evento_deporte_id
]);
$equipo_id = $stmt->fetchColumn();

// Asignar usuario actual como capitán
$stmt = $conn->prepare("INSERT INTO usuarios_equipos (usuario_id, equipo_id, rol)
                        VALUES (:uid, :eid, 'capitan')");
$stmt->execute([
  'uid' => $usuario_id,
  'eid' => $equipo_id
]);

// Guardar los integrantes
$stmt = $conn->prepare("INSERT INTO integrantes (equipo_id, nombre, cedula, telefono)
                        VALUES (:eid, :nombre, :cedula, :telefono)");

foreach ($integrantes as $i) {
  if (!empty($i['nombre']) && !empty($i['cedula']) && !empty($i['telefono'])) {
    $stmt->execute([
      'eid' => $equipo_id,
      'nombre' => $i['nombre'],
      'cedula' => $i['cedula'],
      'telefono' => $i['telefono']
    ]);
  }
}

// Redireccionar con mensaje
header("Location: ../pages/ver_mi_equipo.php?evento=$evento_id&deporte=$deporte_id&registro=ok");
exit;
?>