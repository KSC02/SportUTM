<?php
session_start();
require '../config/conexion.php';

if (!isset($_SESSION['usuario'])) {
  header("Location: ../login.html");
  exit;
}

// Usuario actual
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE nombre_usuario = :nombre");
$stmt->execute(['nombre' => $_SESSION['usuario']]);
$usuario = $stmt->fetch();
if (!$usuario) die("❌ Usuario no válido.");

// Obtener eventos con deportes y cantidad de equipos
$sql = "
  SELECT e.id AS evento_id, e.nombre AS evento_nombre, e.descripcion,
         d.id AS deporte_id, d.nombre AS deporte_nombre, d.reglas,
         ed.id AS evento_deporte_id,
         (SELECT COUNT(*) FROM equipos WHERE evento_deporte_id = ed.id) AS total_equipos
  FROM eventos e
  JOIN eventos_deportes ed ON ed.evento_id = e.id
  JOIN deportes d ON d.id = ed.deporte_id
  ORDER BY e.fecha_inicio DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Eventos - SportUTM</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    .line-clamp-3 {
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
    }
  </style>
</head>
<body class="bg-gray-100">

<!-- Navbar -->
<nav class="bg-green-700 text-white px-6 py-4 flex justify-between items-center shadow">
  <h1 class="text-xl font-bold flex items-center gap-2"><i class="fas fa-futbol"></i> SportUTM</h1>
  <div class="space-x-4 text-sm">
    <span>👋 <?= htmlspecialchars($_SESSION['usuario']) ?></span>
    <a href="../config/logout.php" class="bg-white text-green-700 px-3 py-1 rounded hover:bg-gray-200">Cerrar sesión</a>
  </div>
</nav>

<section class="max-w-6xl mx-auto mt-10 px-4">
  <h2 class="text-3xl font-bold text-green-700 mb-8">🎯 Eventos Deportivos Disponibles</h2>

  <?php if (count($resultados) === 0): ?>
    <p class="text-gray-600">No hay eventos disponibles en este momento.</p>
  <?php else: ?>
    <?php
    $eventosAgrupados = [];
    foreach ($resultados as $row) {
      $eventosAgrupados[$row['evento_id']]['nombre'] = $row['evento_nombre'];
      $eventosAgrupados[$row['evento_id']]['descripcion'] = $row['descripcion'];
      $eventosAgrupados[$row['evento_id']]['deportes'][] = [
        'id' => $row['deporte_id'],
        'nombre' => $row['deporte_nombre'],
        'reglas' => $row['reglas'],
        'evento_deporte_id' => $row['evento_deporte_id'],
        'total_equipos' => $row['total_equipos']
      ];
    }
    ?>

    <div class="space-y-6">
      <?php foreach ($eventosAgrupados as $evento_id => $evento): ?>
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-2xl font-bold text-green-700 mb-2">📢 <?= htmlspecialchars($evento['nombre']) ?></h3>
          <p class="text-gray-700 mb-4"><?= nl2br(htmlspecialchars($evento['descripcion'])) ?></p>

          <div class="grid md:grid-cols-2 gap-6">
            <?php foreach ($evento['deportes'] as $deporte): ?>
              <div class="bg-gray-50 border rounded-lg p-4 relative">
                <h4 class="text-lg font-semibold text-gray-800 mb-1">🏅 <?= htmlspecialchars($deporte['nombre']) ?></h4>
                <p class="text-sm text-gray-600 mb-1">🧑‍🤝‍🧑 Equipos registrados: <strong><?= $deporte['total_equipos'] ?></strong></p>

                <!-- Reglas con "ver más" -->
                <?php
                  $reglas = htmlspecialchars($deporte['reglas']);
                  $clampClass = (strlen($reglas) > 200) ? 'line-clamp-3' : '';
                ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 p-3 text-yellow-800 text-sm mb-3 rounded <?= $clampClass ?>" id="reglas-<?= $deporte['evento_deporte_id'] ?>">
                  <strong>📘 Reglas:</strong><br>
                  <?= nl2br($reglas) ?>
                </div>

                <?php if (strlen($reglas) > 200): ?>
                  <button onclick="toggleReglas('<?= $deporte['evento_deporte_id'] ?>')" class="text-blue-600 text-sm hover:underline mb-2">Ver más</button>
                <?php endif; ?>

                <div class="flex flex-wrap gap-2 mt-2">
                  <a href="registro_equipo.php?evento=<?= $evento_id ?>&deporte=<?= $deporte['id'] ?>&rol=capitan"
                     class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">
                    🧑‍✈️ Capitán
                  </a>
                  <a href="ver_mi_equipo.php?evento=<?= $evento_id ?>&deporte=<?= $deporte['id'] ?>&rol=jugador"
                     class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300 text-sm">
                    👥 Jugador
                  </a>
                  <a href="ver_equipos.php?evento=<?= $evento_id ?>&deporte=<?= $deporte['id'] ?>"
                     class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                    🔍 Ver Equipos
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<script>
  function toggleReglas(id) {
    const reglasBox = document.getElementById('reglas-' + id);
    reglasBox.classList.toggle('line-clamp-3');
  }
</script>

</body>
</html