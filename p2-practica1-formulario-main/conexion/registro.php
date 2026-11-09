<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/db.php";
iniciarSesionTienda();

$datos = json_decode(file_get_contents("php://input"), true);
$nombre = isset($datos["nombre"]) ? trim($datos["nombre"]) : "";
$usuarioForm = isset($datos["usuario"]) ? trim($datos["usuario"]) : "";
$contrasena = isset($datos["contrasena"]) ? $datos["contrasena"] : "";

if ($nombre === "" || $usuarioForm === "" || strlen($contrasena) < 6) {
    http_response_code(400);
    echo json_encode(array("mensaje" => "Completa los campos y usa una contraseña de al menos 6 caracteres."));
    exit;
}

try {
    $insert = $conexion->prepare(
        "INSERT INTO usuarios (nombre, usuario, contrasena) VALUES (:nombre, :usuario, :contrasena)"
    );
    $insert->execute(array(
        ":nombre" => $nombre,
        ":usuario" => $usuarioForm,
        ":contrasena" => password_hash($contrasena, PASSWORD_DEFAULT)
    ));
    $_SESSION["usuario_id"] = (int) $conexion->lastInsertId();
    $_SESSION["usuario_nombre"] = $nombre;
    $asociarCarrito = $conexion->prepare("UPDATE carritos SET usuario_id = :usuario WHERE carrito_id = :carrito");
    $asociarCarrito->execute(array(":usuario" => $_SESSION["usuario_id"], ":carrito" => obtenerCarritoId()));
    echo json_encode(array("mensaje" => "¡Cuenta creada con éxito!"));
} catch (PDOException $e) {
    if ($e->getCode() === "23000") {
        http_response_code(400);
        echo json_encode(array("mensaje" => "El nombre de usuario ya está ocupado."));
    } else {
        http_response_code(500);
        echo json_encode(array("mensaje" => "No fue posible crear la cuenta."));
    }
}
?>
