<?php include('includes/header.php'); ?>
<?php include('includes/navbar.php'); ?>

<!-- Hero con imagen grande centrada -->
<section class="relative w-full h-screen overflow-hidden">
  <img src="assets/img/fondo.jpg" alt="Fondo deportivo" class="absolute inset-0 w-full h-full object-cover z-0" />

  <!-- Cuadro de bienvenida con glassmorphism -->
  <div class="relative z-10 flex items-center justify-center h-full px-6">
    <div class="backdrop-blur-md bg-white/70 p-10 rounded-2xl shadow-lg text-center max-w-2xl w-full">
      <h1 class="text-4xl font-extrabold text-green-700 mb-4">¡Bienvenido a SportUTM!</h1>
      <p class="text-lg text-gray-800 mb-6">Conéctate con el deporte universitario. Registra tu equipo, consulta eventos y participa en torneos representando a tu facultad.</p>
      <a href="pages/about.php" class="uk-button uk-button-primary bg-green-600 hover:bg-green-800 transition">Explorar más</a>
    </div>
  </div>
</section>

<!-- Sección de características o funcionalidades -->
<section class="py-12 bg-white text-center">
  <h2 class="text-3xl font-semibold text-green-700 mb-8">¿Qué puedes hacer con SportUTM?</h2>
  <div class="grid md:grid-cols-3 gap-8 px-6 max-w-6xl mx-auto">

    <div class="uk-card uk-card-default uk-card-body rounded-xl shadow hover:shadow-lg transition">
      <span uk-icon="icon: users; ratio: 2" class="text-green-600 mb-4 block"></span>
      <h3 class="text-xl font-bold mb-2">Registrar Equipos</h3>
      <p>Inscribe tu equipo en campeonatos de fútbol, baloncesto y más.</p>
    </div>

    <div class="uk-card uk-card-default uk-card-body rounded-xl shadow hover:shadow-lg transition">
      <span uk-icon="icon: calendar; ratio: 2" class="text-green-600 mb-4 block"></span>
      <h3 class="text-xl font-bold mb-2">Ver Calendario</h3>
      <p>Consulta fechas de partidos, grupos y resultados.</p>
    </div>

    <div class="uk-card uk-card-default uk-card-body rounded-xl shadow hover:shadow-lg transition">
      <span uk-icon="icon: trophy; ratio: 2" class="text-green-600 mb-4 block"></span>
      <h3 class="text-xl font-bold mb-2">Ranking y Clasificación</h3>
      <p>Visualiza la tabla de posiciones y estadísticas en tiempo real.</p>
    </div>

  </div>
</section>

<?php include('includes/footer.php'); ?>