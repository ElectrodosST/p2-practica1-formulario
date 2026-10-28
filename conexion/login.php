<?php
header("Content-Type: application/json");

$servidor = "localhost";
$usuario  = "root";
$password = "";
$base_datos = "shein_db"; 

try {
    $conexion = new PDO("mysql:host=$servidor;dbname=$base_datos;charset=utf8", $usuario, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $json = file_get_contents('php://input');
    $datos = json_decode($json, true);

    $usuario_form = $datos['usuario'];
    $contrasena = $datos['contrasena'];

    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND contrasena = :contrasena");
    $stmt->execute([':usuario' => $usuario_form, ':contrasena' => $contrasena]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["mensaje" => "Acceso concedido"]);
    } else {
        http_response_code(401);
        echo json_encode(["mensaje" => "Usuario o contraseña incorrectos."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["mensaje" => "Error del servidor: " . $e->getMessage()]);
}
$conexion = null;
?>
