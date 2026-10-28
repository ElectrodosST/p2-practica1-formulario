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

    $nombre = $datos['nombre'];
    $usuario_form = $datos['usuario'];
    $contrasena = $datos['contrasena'];

    $stmt = $conexion->prepare("SELECT usuario FROM usuarios WHERE usuario = :usuario");
    $stmt->execute([':usuario' => $usuario_form]);
    
    if ($stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(["mensaje" => "El nombre de usuario ya está ocupado."]);
        exit;
    }

    $sql = "INSERT INTO usuarios (nombre, usuario, contrasena) VALUES (:nombre, :usuario, :contrasena)";
    $insert = $conexion->prepare($sql);
    $insert->execute([
        ':nombre' => $nombre,
        ':usuario' => $usuario_form,
        ':contrasena' => $contrasena
    ]);

    echo json_encode(["mensaje" => "¡Cuenta creada con éxito!"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["mensaje" => "Error en la base de datos: " . $e->getMessage()]);
}
$conexion = null;
?>
