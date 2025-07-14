<?php
$host = "yamabiko.proxy.rlwy.net";
$port = "20199";
$dbname = "railway";
$user = "postgres";
$password = "PYvtGdWvUymBTCdCwccHhnMbHWDxSwFs";

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["success" => false, "message" => "Error de conexión: " . $e->getMessage()]));
}
?>