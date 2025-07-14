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

// Buscar evento_deporte_id
$stmt = $conn->prepare("
  SELECT ed.id, e.nombre AS evento_nombre, d.nombre AS deporte_nombre
  FROM eventos_deportes ed
  JOIN eventos e ON ed.evento_id = e.id
  JOIN deportes d ON ed.deporte_id = d.id
  WHERE ed.evento_id = :evento AND ed.deporte_id = :deporte
");
$stmt->execute(['evento' => $evento_id, 'deporte' => $deporte_id]);
$relacion = $stmt->fetch();

if (!$relacion) {
  die("❌ Evento o deporte no válido.");
}

$evento_deporte_id = $relacion['id'];
$evento_nombre = $relacion['evento_nombre'];
$deporte_nombre = $relacion['deporte_nombre'];

// Obtener todos los equipos + sus integrantes
$stmt = $conn->prepare("
  SELECT e.id AS equipo_id, e.nombre_equipo, e.facultad,
    (SELECT u.nombre_usuario FROM usuarios u
     JOIN usuarios_equipos ue ON ue.usuario_id = u.id
     WHERE ue.equipo_id = e.id AND ue.rol = 'capitan' LIMIT 1) AS capitan,
    (SELECT COUNT(*) FROM usuarios_equipos WHERE equipo_id = e.id) AS num_jugadores
  FROM equipos e
  WHERE e.evento_deporte_id = :edid
  ORDER BY e.nombre_equipo
");
$stmt->execute(['edid' => $evento_deporte_id]);
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Equipos registrados</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function toggleIntegrantes(id) {
      const fila = document.getElementById("integrantes-" + id);
      if (fila.style.display === "none" || fila.style.display === "") {
        fila.style.display = "block";
      } else {
        fila.style.display = "none";
      }
    }
  </script>
</head>
<body class="bg-gray-100">

<!-- Navbar -->
<nav class="bg-green-700 text-white px-6 py-4 flex justify-between items-center">
  <h1 class="text-xl font-bold">SportUTM</h1>
  <div class="space-x-4">
    <span class="text-sm">👤 <?= htmlspecialchars($_SESSION['usuario']) ?></span>
    <a href="../php/logout.php" class="bg-white text-green-700 px-3 py-1 rounded hover:bg-gray-200 text-sm">Cerrar sesión</a>
  </div>
</nav>

<section class="max-w-6xl mx-auto mt-10 bg-white p-6 rounded shadow">
  <h2 class="text-2xl font-bold text-green-700 mb-6">
    Equipos registrados en <?= htmlspecialchars($evento_nombre) ?> - <?= htmlspecialchars($deporte_nombre) ?>
  </h2>

  <?php if (count($equipos) === 0): ?>
    <p class="text-gray-600">⚠️ Aún no hay equipos registrados.</p>
  <?php else: ?>
    <div class="space-y-6">
      <?php foreach ($equipos as $equipo): ?>
        <?php
          // Obtener integrantes
          $stmt = $conn->prepare("
            SELECT u.nombre_usuario, ue.rol
            FROM usuarios u
            JOIN usuarios_equipos ue ON ue.usuario_id = u.id
            WHERE ue.equipo_id = :id
          ");
          $stmt->execute(['id' => $equipo['equipo_id']]);
          $integrantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="border border-gray-200 rounded-lg bg-gray-50 p-4 shadow">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-lg font-semibold text-green-800"><?= htmlspecialchars($equipo['nombre_equipo']) ?></h3>
              <p class="text-sm text-gray-700">Facultad: <strong><?= htmlspecialchars($equipo['facultad']) ?></strong></p>
              <p class="text-sm">Capitán: <strong><?= $equipo['capitan'] ?? 'No asignado' ?></strong></p>
              <p class="text-sm">Integrantes: <?= $equipo['num_jugadores'] ?></p>
            </div>
            <button onclick="toggleIntegrantes(<?= $equipo['equipo_id'] ?>)" class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">
              👥 Ver integrantes
            </button>
          </div>

          <!-- Tabla de integrantes (oculta por defecto) -->
          <div id="integrantes-<?= $equipo['equipo_id'] ?>" class="mt-4 hidden">
            <table class="w-full text-sm border mt-2 bg-white rounded">
              <thead class="bg-green-100 text-green-900">
                <tr>
                  <th class="px-3 py-2 border">Nombre de usuario</th>
                  <th class="px-3 py-2 border">Rol</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($integrantes as $i): ?>
                  <tr>
                    <td class="border px-3 py-2"><?= htmlspecialchars($i['nombre_usuario']) ?></td>
                    <td class="border px-3 py-2">
                      <?= $i['rol'] === 'capitan' ? '👑 Capitán' : 'Jugador' ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

</body>
</html>