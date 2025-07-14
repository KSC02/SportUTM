<?php
session_start();
require '../config/conexion.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}

// CREAR o ACTUALIZAR EVENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_evento'])) {
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $fecha = $_POST['fecha_inicio'] ?? '';
        $evento_id = $_POST['editar_evento_id'] ?? null;

        if ($nombre && $fecha) {
            if ($evento_id) {
                // Actualizar evento existente
                $stmt = $conn->prepare("UPDATE eventos SET nombre = :n, descripcion = :d, fecha_inicio = :f WHERE id = :id");
                $stmt->execute(['n' => $nombre, 'd' => $descripcion, 'f' => $fecha, 'id' => $evento_id]);
            } else {
                // Crear nuevo evento
                $stmt = $conn->prepare("INSERT INTO eventos (nombre, descripcion, fecha_inicio) VALUES (:n, :d, :f)");
                $stmt->execute(['n' => $nombre, 'd' => $descripcion, 'f' => $fecha]);
            }
        }
    }

    if (isset($_POST['asociar_deporte'])) {
        $evento_id = $_POST['evento_id'] ?? 0;
        $nombre_dep = $_POST['nombre_dep'] ?? '';
        $reglas = $_POST['reglas'] ?? '';

        if ($evento_id && $nombre_dep) {
            $stmt = $conn->prepare("INSERT INTO deportes (nombre, reglas) VALUES (:n, :r) RETURNING id");
            $stmt->execute(['n' => $nombre_dep, 'r' => $reglas]);
            $deporte_id = $stmt->fetchColumn();

            $stmt = $conn->prepare("INSERT INTO eventos_deportes (evento_id, deporte_id) VALUES (:e, :d)");
            $stmt->execute(['e' => $evento_id, 'd' => $deporte_id]);
        }
    }

    if (isset($_POST['eliminar_evento'])) {
        $id = $_POST['evento_id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM eventos WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}

// Para precargar si viene con GET editar
$eventoEditar = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT * FROM eventos WHERE id = :id");
    $stmt->execute(['id' => $_GET['editar']]);
    $eventoEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $conn->query("SELECT * FROM eventos ORDER BY fecha_inicio DESC");
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Admin - Eventos</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<nav class="bg-green-700 text-white px-6 py-4 flex justify-between items-center">
  <h1 class="text-xl font-bold">⚙️ Panel Admin - Eventos</h1>
  <a href="../config/logout.php" class="bg-white text-green-700 px-3 py-1 rounded">Cerrar sesión</a>
</nav>

<main class="max-w-6xl mx-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
  <!-- FORM CREAR / EDITAR EVENTO -->
  <div class="bg-white p-4 rounded shadow">
    <h2 class="text-xl font-bold mb-3 text-green-700"><?= $eventoEditar ? "Editar Evento" : "Crear Evento" ?></h2>
    <form method="POST" class="space-y-3">
      <input type="hidden" name="crear_evento">
      <?php if ($eventoEditar): ?>
        <input type="hidden" name="editar_evento_id" value="<?= $eventoEditar['id'] ?>">
      <?php endif; ?>
      <input name="nombre" class="border p-2 w-full" placeholder="Nombre del evento" required value="<?= $eventoEditar['nombre'] ?? '' ?>">
      <textarea name="descripcion" class="border p-2 w-full" placeholder="Descripción"><?= $eventoEditar['descripcion'] ?? '' ?></textarea>
      <input name="fecha_inicio" type="date" class="border p-2 w-full" required value="<?= $eventoEditar['fecha_inicio'] ?? '' ?>">
      <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
        <?= $eventoEditar ? "Actualizar" : "Guardar" ?> Evento
      </button>
    </form>
  </div>

  <!-- FORM ASOCIAR DEPORTE -->
  <div class="bg-white p-4 rounded shadow">
    <h2 class="text-xl font-bold mb-3 text-green-700">Asociar Deporte a Evento</h2>
    <form method="POST" class="space-y-3">
      <input type="hidden" name="asociar_deporte">
      <select name="evento_id" class="border p-2 w-full" required>
        <option value="">Seleccione evento</option>
        <?php foreach ($eventos as $ev): ?>
          <option value="<?= $ev['id'] ?>"><?= htmlspecialchars($ev['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="nombre_dep" class="border p-2 w-full" required>
        <option value="">Seleccione deporte</option>
        <option value="Fútbol">Fútbol</option>
        <option value="Baloncesto">Baloncesto</option>
        <option value="Voleibol">Voleibol</option>
        <option value="Ecuavoley">Ecuavoley</option>
      </select>
      <textarea name="reglas" class="border p-2 w-full" placeholder="Reglas del deporte"></textarea>
      <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Asociar Deporte</button>
    </form>
  </div>
</main>

<!-- EVENTOS REGISTRADOS -->
<section class="max-w-6xl mx-auto mt-10 px-6">
  <h2 class="text-2xl font-bold text-green-700 mb-4">Eventos Registrados</h2>
  <?php foreach ($eventos as $evento): ?>
    <div class="bg-white p-4 rounded shadow mb-4">
      <div class="flex justify-between items-center mb-2">
        <h3 class="text-lg font-semibold text-green-800">📌 <?= htmlspecialchars($evento['nombre']) ?></h3>
        <div class="space-x-2">
          <a href="?editar=<?= $evento['id'] ?>" class="text-blue-600 hover:underline">Editar</a>
          <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar este evento?');">
            <input type="hidden" name="evento_id" value="<?= $evento['id'] ?>">
            <button type="submit" name="eliminar_evento" class="text-red-600 hover:underline">Eliminar</button>
          </form>
        </div>
      </div>
      <p class="text-sm text-gray-600 mb-1">📅 Fecha: <?= $evento['fecha_inicio'] ?></p>
      <p class="mb-2"><?= nl2br(htmlspecialchars($evento['descripcion'])) ?></p>

      <div>
        <strong class="text-gray-800">🏅 Deportes:</strong>
        <ul class="list-disc pl-6 mt-1">
          <?php
            $stmt = $conn->prepare("SELECT d.nombre, d.id FROM deportes d JOIN eventos_deportes ed ON ed.deporte_id = d.id WHERE ed.evento_id = :eid");
            $stmt->execute(['eid' => $evento['id']]);
            $deportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($deportes as $dep):
          ?>
            <li class="flex items-center justify-between">
              <span><?= htmlspecialchars($dep['nombre']) ?></span>
              <a href="fase_grupo.php?evento_deporte_id=<?= $evento['id'] ?>_<?= $dep['id'] ?>" class="text-blue-600 hover:underline">Fase de Grupo</a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endforeach; ?>
</section>

</body>
</html>