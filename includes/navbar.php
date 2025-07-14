<nav class="bg-green-700 text-white px-6 py-4 shadow-md">
  <div class="container mx-auto flex justify-between items-center">
    <!-- Logo -->
    <a href="index.php" class="text-2xl font-extrabold tracking-wide hover:text-white">Sport<span class="text-yellow-300">UTM</span></a>

    <!-- Menú principal -->
    <ul class="hidden md:flex gap-6 text-lg">
      <li><a href="index.php" class="hover:text-yellow-300 transition">Inicio</a></li>
      <li><a href="#" class="hover:text-yellow-300 transition">Nosotros</a></li>
      <li><a href="#" class="hover:text-yellow-300 transition">Eventos</a></li>
      <li><a href="login.php" class="hover:text-yellow-300 transition">Login</a></li>
    </ul>

    <!-- Botón hamburguesa para móvil -->
    <button class="md:hidden" uk-toggle="target: #offcanvas-nav">
      <span uk-icon="icon: menu; ratio: 1.5"></span>
    </button>
  </div>
</nav>

<!-- Menú lateral para móvil con UIkit -->
<div id="offcanvas-nav" uk-offcanvas="overlay: true">
  <div class="uk-offcanvas-bar bg-green-800 text-white">
    <button class="uk-offcanvas-close" type="button" uk-close></button>
    <ul class="uk-nav uk-nav-default mt-6 text-lg">
      <li><a href="index.php" class="hover:text-yellow-300">Inicio</a></li>
      <li><a href="#" class="hover:text-yellow-300">Nosotros</a></li>
      <li><a href="#" class="hover:text-yellow-300">Eventos</a></li>
      <li><a href="#" class="hover:text-yellow-300">Login</a></li>
    </ul>
  </div>
</div>