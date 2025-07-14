<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'jugador') {
  header("Location: ../login.html");
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Inicio - SportUTM</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="bg-gray-100 text-gray-800">

<!-- Navbar -->
<nav class="bg-green-700 text-white px-6 py-4 flex justify-between items-center shadow">
  <h1 class="text-xl font-bold flex items-center gap-2">
    <i class="fas fa-futbol"></i> SportUTM
  </h1>
  <div class="space-x-4 text-sm">
    <span>👋 Bienvenido, <?= htmlspecialchars($_SESSION['usuario']) ?></span>
    <a href="../pages/eventos.php" class="bg-white text-green-700 px-3 py-1 rounded hover:bg-gray-100 transition">Eventos</a>
    <a href="../config/logout.php" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">Cerrar sesión</a>
  </div>
</nav>

<!-- Hero -->
<section class="bg-green-100 py-12 text-center shadow-inner">
  <h2 class="text-4xl font-bold text-green-700 mb-2">¡Explora lo mejor del deporte universitario!</h2>
  <p class="text-gray-700 mb-6">Noticias, eventos y participación en tiempo real de tu facultad.</p>
  <a href="../pages/eventos.php" class="inline-block bg-green-700 text-white px-6 py-3 rounded hover:bg-green-800 transition text-lg shadow">
    ⚽ Ver todos los eventos deportivos
  </a>
</section>

<!-- Noticias -->
<section class="max-w-6xl mx-auto mt-14 px-4">
  <h3 class="text-2xl font-semibold text-green-700 mb-6">📰 Noticias recientes</h3>
  <div class="grid md:grid-cols-3 gap-6">
    <div class="bg-white rounded-lg shadow hover:shadow-lg transition p-4">
      <img src="https://picsum.photos/200/300" class="rounded mb-4 w-full h-40 object-cover" alt="Noticia 1">
      <h4 class="font-bold text-lg">Interfacultades arranca con gran energía</h4>
      <p class="text-sm text-gray-600 mt-1">El torneo de fútbol masculino comenzó con emocionantes partidos de la FCI y FCA.</p>
    </div>
    <div class="bg-white rounded-lg shadow hover:shadow-lg transition p-4">
      <img src="https://picsum.photos/200/301" class="rounded mb-4 w-full h-40 object-cover" alt="Noticia 2">
      <h4 class="font-bold text-lg">Baloncesto femenino: jornada vibrante</h4>
      <p class="text-sm text-gray-600 mt-1">Equipos de la salud y humanidades brillaron en la cancha este fin de semana.</p>
    </div>
    <div class="bg-white rounded-lg shadow hover:shadow-lg transition p-4">
      <img src="https://picsum.photos/200/302" class="rounded mb-4 w-full h-40 object-cover" alt="Noticia 3">
      <h4 class="font-bold text-lg">Nueva fecha para torneo mixto de vóley</h4>
      <p class="text-sm text-gray-600 mt-1">Debido al feriado, los partidos del grupo B se reprogramaron al martes.</p>
    </div>
  </div>
</section>

<!-- Eventos -->
<section class="max-w-6xl mx-auto mt-16 px-4">
  <h3 class="text-2xl font-semibold text-green-700 mb-6">📅 Eventos deportivos actuales</h3>
  <ul class="space-y-4">
    <li class="bg-white rounded shadow p-4 flex justify-between items-center hover:shadow-md transition">
      <div>
        <h4 class="font-bold text-lg">Fútbol Masculino - Grupo A</h4>
        <p class="text-sm text-gray-500">Hoy - 16:00 - Cancha UTM</p>
      </div>
      <span class="text-green-600 font-semibold">En curso</span>
    </li>
    <li class="bg-white rounded shadow p-4 flex justify-between items-center hover:shadow-md transition">
      <div>
        <h4 class="font-bold text-lg">Baloncesto Femenino - Grupo B</h4>
        <p class="text-sm text-gray-500">Mañana - 10:00 - Coliseo Central</p>
      </div>
      <span class="text-yellow-500 font-semibold">Próximo</span>
    </li>
  </ul>
</section>

<!-- Footer -->
<footer class="mt-20 bg-green-700 text-white text-center py-4 shadow-inner">
  <p>&copy; <?= date("Y") ?> SportUTM - Todos los derechos reservados.</p>
</footer>

</body>
</html>