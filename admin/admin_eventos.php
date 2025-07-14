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
}

// ELIMINAR EVENTO
if (isset($_GET['eliminar_evento'])) {
    $stmt = $conn->prepare("DELETE FROM eventos WHERE id = :id");
    $stmt->execute(['id' => $_GET['eliminar_evento']]);
    header("Location: admin_eventos.php");
    exit;
}

// CARGAR EVENTO A EDITAR
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
    <meta charset="UTF-8">
    <title>Admin - Eventos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<nav class="bg-green-700 text-white px-6 py-4 flex justify-between items-center">
    <h1 class="text-xl font-bold">⚙️ Admin - Gestión de Eventos</h1>
    <a href="../config/logout.php" class="bg-white text-green-700 px-3 py-1 rounded">Cerrar sesión</a>
</nav>

<main class="max-w-7xl mx-auto p-6">
    <h2 class="text-2xl font-bold text-green-700 mb-4">🎯 Gestión de Eventos</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
        <form method="POST" class="bg-white p-4 rounded shadow space-y-3">
            <h3 class="text-lg font-semibold text-green-700"><?= $eventoEditar ? '✏️ Editar Evento' : '➕ Crear Evento' ?></h3>
            <input type="hidden" name="crear_evento">
            <?php if ($eventoEditar): ?>
                <input type="hidden" name="editar_evento_id" value="<?= $eventoEditar['id'] ?>">
            <?php endif; ?>
            <input name="nombre" class="border p-2 w-full" placeholder="Nombre del evento" required value="<?= $eventoEditar['nombre'] ?? '' ?>">
            <textarea name="descripcion" class="border p-2 w-full" placeholder="Descripción (opcional)"><?= $eventoEditar['descripcion'] ?? '' ?></textarea>
            <input name="fecha_inicio" type="date" class="border p-2 w-full" required value="<?= $eventoEditar['fecha_inicio'] ?? '' ?>">
            <button class="bg-green-600 text-white px-4 py-2 rounded">
                <?= $eventoEditar ? 'Actualizar evento' : 'Guardar evento' ?>
            </button>
        </form>

        <form method="POST" class="bg-white p-4 rounded shadow space-y-3">
            <h3 class="text-lg font-semibold text-green-700">🏅 Asociar Deporte a Evento</h3>
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
                <option value="Basket">Basket</option>
                <option value="Voley">Voley</option>
                <option value="Ecuavoley">Ecuavoley</option>
            </select>
            <textarea name="reglas" class="border p-2 w-full" placeholder="Reglas del deporte (opcional)"></textarea>
            <button class="bg-green-600 text-white px-4 py-2 rounded">Asociar deporte</button>
        </form>
    </div>

    <h2 class="text-2xl font-bold text-green-700 mb-4">📋 Eventos Registrados</h2>

    <?php foreach ($eventos as $evento): ?>
        <div class="bg-white rounded shadow p-4 mb-6">
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-lg font-bold text-green-800">📌 <?= htmlspecialchars($evento['nombre']) ?></h3>
                <div class="space-x-2">
                    <a href="?editar=<?= $evento['id'] ?>" class="bg-yellow-400 text-white px-3 py-1 rounded">Editar</a>
                    <a href="?eliminar_evento=<?= $evento['id'] ?>" class="bg-red-600 text-white px-3 py-1 rounded" onclick="return confirm('¿Eliminar evento?')">Eliminar</a>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-2">📅 Fecha: <?= $evento['fecha_inicio'] ?></p>
            <?php if ($evento['descripcion']): ?>
                <p class="mb-3"><?= nl2br(htmlspecialchars($evento['descripcion'])) ?></p>
            <?php endif; ?>

            <div>
                <strong>🏆 Deportes:</strong>
                <ul class="list-disc pl-6 mt-2 space-y-1">
                    <?php
                    $stmt = $conn->prepare("SELECT d.nombre, ed.id AS evento_deporte_id FROM deportes d JOIN eventos_deportes ed ON ed.deporte_id = d.id WHERE ed.evento_id = :evento_id");
                    $stmt->execute(['evento_id' => $evento['id']]);
                    $deportes = $stmt->fetchAll();

                    if (!$deportes):
                        echo "<li class='text-gray-500'>Sin deportes asociados.</li>";
                    else:
                        foreach ($deportes as $dep):
                    ?>
                        <li>
                            <?= htmlspecialchars($dep['nombre']) ?>
                            <a href="fase_grupo.php?evento_deporte_id=<?= $dep['evento_deporte_id'] ?>" class="ml-3 text-blue-600 underline font-semibold">📊 Fase de grupos</a>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>
</main>

</body>
</html>
