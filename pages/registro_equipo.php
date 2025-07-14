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
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE nombre_usuario = :nombre");
$stmt->execute(['nombre' => $_SESSION['usuario']]);
$usuario = $stmt->fetch();
$usuario_id = $usuario['id'];

// Validar relación evento-deporte
$stmt = $conn->prepare("SELECT ed.id AS evento_deporte_id, e.nombre AS evento, d.nombre AS deporte
                        FROM eventos_deportes ed
                        JOIN eventos e ON ed.evento_id = e.id
                        JOIN deportes d ON ed.deporte_id = d.id
                        WHERE ed.evento_id = :evento AND ed.deporte_id = :deporte");
$stmt->execute([
  'evento' => $evento_id,
  'deporte' => $deporte_id
]);
$relacion = $stmt->fetch();

if (!$relacion) {
  die("❌ Combinación evento-deporte no válida.");
}

$evento_deporte_id = $relacion['evento_deporte_id'];
$evento_nombre = $relacion['evento'];
$deporte_nombre = $relacion['deporte'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Registrar Equipo - SportUTM</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.19.2/dist/css/uikit.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/uikit@3.19.2/dist/js/uikit.min.js"></script>
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

<!-- Contenido -->
<section class="max-w-6xl mx-auto mt-10 bg-white p-8 rounded shadow">
  <h2 class="text-2xl font-bold text-green-700 mb-6">🏆 Registrar Nuevo Equipo</h2>

  <form method="POST" action="../config/guardar_equipo.php?evento=<?= $evento_id ?>&deporte=<?= $deporte_id ?>">
    <div class="grid md:grid-cols-2 gap-8">

      <!-- Datos del equipo -->
      <div>
        <h3 class="text-lg font-semibold mb-4">📝 Información del Equipo</h3>
        <div class="mb-4">
          <label class="block mb-1 font-medium">Nombre del equipo</label>
          <input type="text" name="nombre_equipo" class="w-full border rounded px-4 py-2" required>
        </div>
        <div class="mb-4">
          <label class="block mb-1 font-medium">Facultad</label>
          <input type="text" name="facultad" class="w-full border rounded px-4 py-2" required>
        </div>
      </div>

      <!-- Formulario de integrante -->
      <div>
        <h3 class="text-lg font-semibold mb-4">👥 Añadir Integrantes</h3>
        <div class="mb-2">
          <label class="block mb-1 font-medium">Nombre</label>
          <input type="text" id="nombre" class="w-full border rounded px-4 py-2">
        </div>
        <div class="mb-2">
          <label class="block mb-1 font-medium">Cédula</label>
          <input type="text" id="cedula" class="w-full border rounded px-4 py-2">
        </div>
        <div class="mb-2">
          <label class="block mb-1 font-medium">Teléfono</label>
          <input type="text" id="telefono" class="w-full border rounded px-4 py-2">
        </div>

        <!-- Mensaje modo edición -->
        <div id="modo-edicion" class="hidden text-sm text-blue-600 font-semibold mt-2 animate-pulse">
          ✏️ Editando integrante...
        </div>

        <div class="mt-4">
          <button type="button" onclick="agregarIntegrante()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">➕ Agregar integrante</button>
        </div>
      </div>
    </div>

    <!-- Tabla de integrantes -->
    <div class="mt-10">
      <h3 class="text-lg font-semibold mb-4 text-gray-700">📋 Lista de Integrantes</h3>
      <table class="w-full border text-sm" id="tabla-integrantes">
        <thead class="bg-green-100 text-green-900">
          <tr>
            <th class="border px-3 py-2">Nombre</th>
            <th class="border px-3 py-2">Cédula</th>
            <th class="border px-3 py-2">Teléfono</th>
            <th class="border px-3 py-2">Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

    <!-- Campo oculto con todos los integrantes -->
    <input type="hidden" name="integrantes_json" id="integrantes_json">
    <input type="hidden" name="evento_id" value="<?= $evento_id ?>">
    <input type="hidden" name="deporte_id" value="<?= $deporte_id ?>">

    <!-- Botón de enviar -->
    <div class="mt-6 text-right">
      <button type="submit" class="uk-button uk-button-primary bg-green-700 hover:bg-green-800 text-white px-6 py-2">✅ Registrar equipo</button>
    </div>
  </form>
</section>

<!-- JS -->
<script>
  let integrantes = [];
  let editandoIndex = null;

  function agregarIntegrante() {
    const nombre = document.getElementById('nombre').value.trim();
    const cedula = document.getElementById('cedula').value.trim();
    const telefono = document.getElementById('telefono').value.trim();
    const btn = document.querySelector('button[onclick="agregarIntegrante()"]');

    if (!nombre || !cedula || !telefono) {
      alert("⚠️ Completa todos los campos del integrante.");
      return;
    }

    const nuevo = { nombre, cedula, telefono };

    if (editandoIndex !== null) {
      integrantes[editandoIndex] = nuevo;
      editandoIndex = null;
      btn.textContent = "➕ Agregar integrante";
      document.getElementById('modo-edicion').classList.add('hidden');
    } else {
      integrantes.push(nuevo);
    }

    renderTabla();
    document.getElementById('integrantes_json').value = JSON.stringify(integrantes);

    // Limpiar campos
    document.getElementById('nombre').value = "";
    document.getElementById('cedula').value = "";
    document.getElementById('telefono').value = "";
  }

  function eliminarIntegrante(index) {
    if (confirm("¿Eliminar a este integrante?")) {
      integrantes.splice(index, 1);
      renderTabla();
      document.getElementById('integrantes_json').value = JSON.stringify(integrantes);
    }
  }

  function editarIntegrante(index) {
    const integrante = integrantes[index];
    document.getElementById('nombre').value = integrante.nombre;
    document.getElementById('cedula').value = integrante.cedula;
    document.getElementById('telefono').value = integrante.telefono;

    editandoIndex = index;

    const btn = document.querySelector('button[onclick="agregarIntegrante()"]');
    btn.textContent = "✏️ Actualizar integrante";
    document.getElementById('modo-edicion').classList.remove('hidden');
  }

  function renderTabla() {
    const tbody = document.querySelector('#tabla-integrantes tbody');
    tbody.innerHTML = "";

    integrantes.forEach((item, index) => {
      tbody.innerHTML += `
        <tr>
          <td class="border px-3 py-2">${item.nombre}</td>
          <td class="border px-3 py-2">${item.cedula}</td>
          <td class="border px-3 py-2">${item.telefono}</td>
          <td class="border px-3 py-2 text-center space-x-2">
                <button type="button" onclick="editarIntegrante(${index})" class="text-blue-600 hover:underline">✏️ Editar</button>
                <button type="button" onclick="eliminarIntegrante(${index})" class="text-red-600 hover:underline">❌ Eliminar</button>
          </td>
        </tr>
      `;
    });
  }
</script>

</body>
</html>