<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - SportUTM</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- FontAwesome (íconos) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

  <!-- Contenedor principal -->
<div class="w-full max-w-md relative bg-white rounded-xl shadow-xl overflow-hidden">

  <!-- Selector de vista -->
  <div id="form-container" class="transition duration-500 ease-in-out">
    <!-- LOGIN -->
    <div id="login-form" class="p-8">
      <div class="text-center mb-6">
        <i class="fas fa-basketball-ball text-green-600 text-4xl mb-2"></i>
        <h1 class="text-2xl font-bold text-green-700">SportUTM</h1>
        <p class="text-sm text-gray-500">Inicia sesión para continuar</p>
      </div>
      <form action="config/login.php" method="POST" class="space-y-5">
        <!-- Usuario -->
        <div class="flex items-center border border-gray-300 rounded px-3 py-2">
          <i class="fas fa-user text-gray-400 mr-2"></i>
          <input type="text" name="nombre_usuario" placeholder="Usuario" class="w-full outline-none text-sm" required>
        </div>
        <!-- Contraseña -->
        <div class="flex items-center border border-gray-300 rounded px-3 py-2">
          <i class="fas fa-lock text-gray-400 mr-2"></i>
          <input type="password" name="contraseña" placeholder="••••••••" class="w-full outline-none text-sm" required>
        </div>
        <button type="submit" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 transition font-semibold">
          Ingresar
        </button>
        <p class="text-sm text-center mt-3 text-gray-600">
          ¿No tienes cuenta?
          <a href="#" id="toRegister" class="text-green-600 font-semibold hover:underline">Regístrate aquí</a>
        </p>
      </form>
    </div>

    <!-- REGISTRO (inicialmente oculto) -->
    <div id="register-form" class="hidden p-8">
      <div class="text-center mb-6">
        <i class="fas fa-user-plus text-green-600 text-4xl mb-2"></i>
        <h2 class="text-2xl font-bold text-green-700">Crear cuenta</h2>
        <p class="text-sm text-gray-500">Completa los datos para registrarte</p>
      </div>
      <form action="config/registro.php" method="POST" class="space-y-5">
        <div class="flex items-center border border-gray-300 rounded px-3 py-2">
          <i class="fas fa-user text-gray-400 mr-2"></i>
          <input type="text" name="nombre_usuario" placeholder="Usuario nuevo" class="w-full outline-none text-sm" required>
        </div>
        <div class="flex items-center border border-gray-300 rounded px-3 py-2">
          <i class="fas fa-lock text-gray-400 mr-2"></i>
          <input type="password" name="contraseña" placeholder="Contraseña" class="w-full outline-none text-sm" required>
        </div>
        <button type="submit" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 transition font-semibold">
          Registrarse
        </button>
        <p class="text-sm text-center mt-3 text-gray-600">
          ¿Ya tienes cuenta?
          <a href="#" id="toLogin" class="text-green-600 font-semibold hover:underline">Iniciar sesión</a>
        </p>
      </form>
    </div>
  </div>
</div>
</body>
<script>
  const loginForm = document.getElementById("login-form");
  const registerForm = document.getElementById("register-form");
  const toRegister = document.getElementById("toRegister");
  const toLogin = document.getElementById("toLogin");

  toRegister.addEventListener("click", (e) => {
    e.preventDefault();
    loginForm.classList.add("hidden");
    registerForm.classList.remove("hidden");
  });

  toLogin.addEventListener("click", (e) => {
    e.preventDefault();
    registerForm.classList.add("hidden");
    loginForm.classList.remove("hidden");
  });
</script>
</html>