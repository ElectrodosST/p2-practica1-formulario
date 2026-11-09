<?php
$servidor = "localhost";
$usuario = "root";
$password = "";
$base_datos = "shein_db";

try {
    $conexion = new PDO(
        "mysql:host=$servidor;dbname=$base_datos;charset=utf8mb4",
        $usuario,
        $password,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch (PDOException $e) {
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(array("mensaje" => "No fue posible conectar con la base de datos."));
    exit;
}

function iniciarSesionTienda()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function obtenerCarritoId()
{
    iniciarSesionTienda();
    if (empty($_SESSION["carrito_id"])) {
        $_SESSION["carrito_id"] = bin2hex(random_bytes(24));
    }
    return $_SESSION["carrito_id"];
}
?>
