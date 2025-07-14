<?php
session_start();
require '../config/conexion.php';

if (!isset($_SESSION['usuario'])) {
  header("Location: ../login.html");
  exit;
}

if (!isset($_GET['evento']) || !isset($_GET['deporte'])) {
  die("❌ Parámetros inválidos.");
}

$evento_id = intval($_GET['evento']);
$deporte_id = intval($_GET['deporte']);

// Obtener ID del usuario actual
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE nombre_usuario = :usuario");
$stmt->execute(['usuario' => $_SESSION['usuario']]);
$usuario = $stmt->fetch();
if (!$usuario) die("❌ Usuario no encontrado.");
$usuario_id = $usuario['id'];

// Buscar evento_deporte_id
$stmt = $conn->prepare("
  SELECT id FROM eventos_deportes
  WHERE evento_id = :evento AND deporte_id = :deporte
");
$stmt->execute(['evento' => $evento_id, 'deporte' => $deporte_id]);
$ed = $stmt->fetch();
if (!$ed) die("❌ Combinación evento-deporte no válida.");
$evento_deporte_id = $ed['id'];

// Buscar el equipo del usuario en este evento + deporte
$stmt = $conn->prepare("
  SELECT e.id AS equipo_id, e.nombre_equipo, e.facultad
  FROM equipos e
  JOIN usuarios_equipos ue ON ue.equipo_id = e.id
  WHERE ue.usuario_id = :usuario_id AND e.evento_deporte_id = :edid
");
$stmt->execute(['usuario_id' => $usuario_id, 'edid' => $evento_deporte_id]);
$equipo = $stmt->fetch();

if (!$equipo) {
  echo "⚠️ No estás registrado en ningún equipo para este evento y deporte.";
  exit;
}

$equipo_id = $equipo['equipo_id'];

// Obtener lista de integrantes
$stmt = $conn->prepare("
  SELECT u.nombre_usuario, ue.rol
  FROM usuarios u
  JOIN usuarios_equipos ue ON ue.usuario_id = u.id
  WHERE ue.equipo_id = :equipo_id
");
$stmt->execute(['equipo_id' => $equipo_id]);
$integrantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mi equipo - SportUTM</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="bg-gray-100">

<!-- Navbar -->
<nav class="bg-green-700 text-white px-6 py-4 flex justify-between items-center shadow">
  <h1 class="text-xl font-bold flex items-center gap-2"><i class="fas fa-futbol"></i> SportUTM</h1>
  <div class="space-x-4 text-sm">
    <span>👋 <?= htmlspecialchars($_SESSION['usuario']) ?></span>
    <a href="../config/logout.php" class="bg-white text-green-700 px-3 py-1 rounded hover:bg-gray-200 transition">Cerrar sesión</a>
  </div>
</nav>

<!-- Contenido -->
<section class="max-w-5xl mx-auto mt-12 px-4">
  <div class="bg-white shadow-lg rounded-lg p-6">
    <h2 class="text-2xl font-bold text-green-700 mb-4">🏅 Mi equipo registrado</h2>

    <div class="mb-6">
      <p class="text-lg"><strong>Nombre del equipo:</strong> <?= htmlspecialchars($equipo['nombre_equipo']) ?></p>
      <p class="text-lg"><strong>Facultad:</strong> <?= htmlspecialchars($equipo['facultad']) ?></p>
    </div>

    <h3 class="text-xl font-semibold text-gray-700 mb-3">👥 Integrantes</h3>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php foreach ($integrantes as $i): ?>
        <div class="bg-green-50 border border-green-200 rounded p-4 shadow-sm hover:shadow-md transition">
          <div class="flex items-center gap-4">
            <div class="bg-green-600 text-white rounded-full w-12 h-12 flex items-center justify-center text-lg font-bold">
              <?= strtoupper(substr($i['nombre_usuario'], 0, 1)) ?>
            </div>
            <div>
              <p class="font-semibold"><?= htmlspecialchars($i['nombre_usuario']) ?></p>
              <p class="text-sm text-gray-600"><?= $i['rol'] === 'capitan' ? '👑 Capitán' : 'Jugador' ?></p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Botón regresar -->
<div class="mt-6">
  <a href="eventos.php" class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300 transition text-sm">
    ⬅️ Regresar a eventos
  </a>
</div>

  </div>
</section>

<!-- Footer -->
<footer class="mt-20 bg-green-700 text-white text-center py-4">
  <p>&copy; <?= date("Y") ?> SportUTM - Todos los derechos reservados.</p>
</footer>

</body>
</html>