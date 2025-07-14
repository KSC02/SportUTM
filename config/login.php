<?php
session_start();
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre_usuario'] ?? '');
  $clave = $_POST['contraseña'] ?? '';

  // Buscar usuario
  $stmt = $conn->prepare("SELECT id, nombre_usuario, contraseña, rol_usuario FROM usuarios WHERE nombre_usuario = :nombre");
  $stmt->execute(['nombre' => $nombre]);
  $usuario = $stmt->fetch();

  if ($usuario && password_verify($clave, $usuario['contraseña'])) {
    $_SESSION['usuario'] = $usuario['nombre_usuario'];
    $_SESSION['rol'] = $usuario['rol_usuario'];

    // Redirigir según el rol
    if ($usuario['rol_usuario'] === 'admin') {
      header("Location: ../admin/admin_home.php");
    } else {
      header("Location: ../user/home.php");
    }
    exit;
  } else {
    echo "<script>alert('❌ Usuario o contraseña incorrectos'); window.history.back();</script>";
  }
} else {
  header("Location: ../login.html");
  exit;
}
?>