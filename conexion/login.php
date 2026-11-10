<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/db.php";
iniciarSesionTienda();

$datos = json_decode(file_get_contents("php://input"), true);
$usuarioForm = isset($datos["usuario"]) ? trim($datos["usuario"]) : "";
$contrasena = isset($datos["contrasena"]) ? $datos["contrasena"] : "";

if ($usuarioForm === "" || $contrasena === "") {
    http_response_code(400);
    echo json_encode(array("mensaje" => "Usuario y contraseña son obligatorios."));
    exit;
}

try {
    $stmt = $conexion->prepare("SELECT id, nombre, contrasena FROM usuarios WHERE usuario = :usuario LIMIT 1");
    $stmt->execute(array(":usuario" => $usuarioForm));
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    $contrasenaValida = $usuario && (
        password_verify($contrasena, $usuario["contrasena"]) ||
        hash_equals($usuario["contrasena"], $contrasena)
    );

    if (!$contrasenaValida) {
        http_response_code(401);
        echo json_encode(array("mensaje" => "Usuario o contraseña incorrectos."));
        exit;
    }

    if (password_needs_rehash($usuario["contrasena"], PASSWORD_DEFAULT)) {
        $actualizar = $conexion->prepare("UPDATE usuarios SET contrasena = :contrasena WHERE id = :id");
        $actualizar->execute(array(
            ":contrasena" => password_hash($contrasena, PASSWORD_DEFAULT),
            ":id" => $usuario["id"]
        ));
    }

    session_regenerate_id(true);
    $_SESSION["usuario_id"] = (int) $usuario["id"];
    $_SESSION["usuario_nombre"] = $usuario["nombre"];
    $asociarCarrito = $conexion->prepare("UPDATE carritos SET usuario_id = :usuario WHERE carrito_id = :carrito");
    $asociarCarrito->execute(array(":usuario" => $usuario["id"], ":carrito" => obtenerCarritoId()));
    echo json_encode(array("mensaje" => "Acceso concedido", "usuario" => $usuario["nombre"]));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array("mensaje" => "No fue posible iniciar sesión."));
}
?>
