<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Panel Admin - SportUTM</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="bg-gray-100 min-h-screen">

<nav class="bg-green-700 text-white px-6 py-4 flex justify-between items-center">
  <h1 class="text-xl font-bold flex items-center gap-2">
    <i class="fas fa-user-shield"></i> Panel Admin
  </h1>
  <div>
    <span>👤 <?= htmlspecialchars($_SESSION['usuario']) ?></span>
    <a href="../config/logout.php" class="ml-4 bg-white text-green-700 px-3 py-1 rounded hover:bg-gray-200 transition">Cerrar sesión</a>
  </div>
</nav>

<main class="max-w-4xl mx-auto p-8 grid grid-cols-1 md:grid-cols-3 gap-6">

  <a href="admin_eventos.php" class="bg-green-600 hover:bg-green-700 transition text-white rounded p-6 flex flex-col items-center justify-center shadow">
    <i class="fas fa-calendar-plus fa-3x mb-4"></i>
    <span class="text-xl font-semibold">Eventos</span>
  </a>

  <a href="gestionar_noticias.php" class="bg-green-600 hover:bg-green-700 transition text-white rounded p-6 flex flex-col items-center justify-center shadow">
    <i class="fas fa-newspaper fa-3x mb-4"></i>
    <span class="text-xl font-semibold">Noticias</span>
  </a>

  <a href="gestionar_publicidad.php" class="bg-green-600 hover:bg-green-700 transition text-white rounded p-6 flex flex-col items-center justify-center shadow">
    <i class="fas fa-image fa-3x mb-4"></i>
    <span class="text-xl font-semibold">Publicidad</span>
  </a>

</main>

</body>
</html>