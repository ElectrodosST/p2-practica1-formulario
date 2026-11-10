<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/db.php";

function responder($datos, $codigo = 200)
{
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

function obtenerResumenCarrito($conexion, $carritoId)
{
    $consulta = $conexion->prepare(
        "SELECT d.producto_id, p.nombre, p.categoria, p.imagen, p.precio, d.cantidad,
                (p.precio * d.cantidad) AS subtotal
         FROM carrito_detalles d
         JOIN productos p ON p.id = d.producto_id
         WHERE d.carrito_id = :carrito
         ORDER BY d.id"
    );
    $consulta->execute(array(":carrito" => $carritoId));
    $productos = $consulta->fetchAll(PDO::FETCH_ASSOC);
    $total = 0;
    $cantidad = 0;
    foreach ($productos as &$producto) {
        $producto["producto_id"] = (int) $producto["producto_id"];
        $producto["cantidad"] = (int) $producto["cantidad"];
        $producto["precio"] = (float) $producto["precio"];
        $producto["subtotal"] = (float) $producto["subtotal"];
        $total += $producto["subtotal"];
        $cantidad += $producto["cantidad"];
    }
    return array("productos" => $productos, "total" => $total, "cantidad" => $cantidad);
}

try {
    $carritoId = obtenerCarritoId();
    $crearCarrito = $conexion->prepare(
        "INSERT IGNORE INTO carritos (carrito_id, usuario_id) VALUES (:carrito, :usuario)"
    );
    $usuarioId = isset($_SESSION["usuario_id"]) ? $_SESSION["usuario_id"] : null;
    $crearCarrito->execute(array(":carrito" => $carritoId, ":usuario" => $usuarioId));

    $metodo = $_SERVER["REQUEST_METHOD"];
    $accion = isset($_GET["accion"]) ? $_GET["accion"] : "";

    if ($metodo === "GET" && $accion === "productos") {
        $categorias = array("ropa", "accesorios", "cosmeticos", "maquillaje");
        $categoria = isset($_GET["categoria"]) ? $_GET["categoria"] : "todos";
        if ($categoria === "todos") {
            $consulta = $conexion->query(
                "SELECT id, nombre, descripcion, categoria, imagen, precio FROM productos WHERE activo = 1 ORDER BY id"
            );
        } elseif (in_array($categoria, $categorias, true)) {
            $consulta = $conexion->prepare(
                "SELECT id, nombre, descripcion, categoria, imagen, precio FROM productos
                 WHERE activo = 1 AND categoria = :categoria ORDER BY id"
            );
            $consulta->execute(array(":categoria" => $categoria));
        } else {
            responder(array("mensaje" => "Categoría no válida."), 400);
        }
        $productos = $consulta->fetchAll(PDO::FETCH_ASSOC);
        foreach ($productos as &$producto) {
            $producto["id"] = (int) $producto["id"];
            $producto["precio"] = (float) $producto["precio"];
        }
        responder(array("productos" => $productos));
    }

    if ($metodo === "GET" && $accion === "carrito") {
        $resumen = obtenerResumenCarrito($conexion, $carritoId);
        $resumen["usuario"] = isset($_SESSION["usuario_nombre"]) ? $_SESSION["usuario_nombre"] : null;
        responder($resumen);
    }

    if ($metodo !== "POST") {
        responder(array("mensaje" => "Acción no válida."), 405);
    }

    $datos = json_decode(file_get_contents("php://input"), true);
    if (!is_array($datos)) {
        responder(array("mensaje" => "Solicitud no válida."), 400);
    }
    $accion = isset($datos["accion"]) ? $datos["accion"] : "";

    if ($accion === "agregar") {
        $productoId = isset($datos["producto_id"]) ? filter_var($datos["producto_id"], FILTER_VALIDATE_INT) : false;
        $cantidad = isset($datos["cantidad"]) ? filter_var($datos["cantidad"], FILTER_VALIDATE_INT) : 1;
        if (!$productoId || !$cantidad || $cantidad < 1 || $cantidad > 99) {
            responder(array("mensaje" => "Producto o cantidad no válidos."), 400);
        }
        $producto = $conexion->prepare("SELECT id FROM productos WHERE id = :id AND activo = 1");
        $producto->execute(array(":id" => $productoId));
        if (!$producto->fetchColumn()) {
            responder(array("mensaje" => "El producto ya no está disponible."), 404);
        }
        $agregar = $conexion->prepare(
            "INSERT INTO carrito_detalles (carrito_id, producto_id, cantidad)
             VALUES (:carrito, :producto, :cantidad)
             ON DUPLICATE KEY UPDATE cantidad = LEAST(cantidad + VALUES(cantidad), 99)"
        );
        $agregar->execute(array(
            ":carrito" => $carritoId,
            ":producto" => $productoId,
            ":cantidad" => $cantidad
        ));
        responder(obtenerResumenCarrito($conexion, $carritoId));
    }

    if ($accion === "actualizar" || $accion === "eliminar") {
        $productoId = isset($datos["producto_id"]) ? filter_var($datos["producto_id"], FILTER_VALIDATE_INT) : false;
        if (!$productoId) {
            responder(array("mensaje" => "Producto no válido."), 400);
        }
        if ($accion === "eliminar") {
            $actualizar = $conexion->prepare(
                "DELETE FROM carrito_detalles WHERE carrito_id = :carrito AND producto_id = :producto"
            );
            $actualizar->execute(array(":carrito" => $carritoId, ":producto" => $productoId));
        } else {
            $cantidad = isset($datos["cantidad"]) ? filter_var($datos["cantidad"], FILTER_VALIDATE_INT) : false;
            if ($cantidad === false || $cantidad < 1 || $cantidad > 99) {
                responder(array("mensaje" => "La cantidad debe estar entre 1 y 99."), 400);
            }
            $actualizar = $conexion->prepare(
                "UPDATE carrito_detalles SET cantidad = :cantidad
                 WHERE carrito_id = :carrito AND producto_id = :producto"
            );
            $actualizar->execute(array(
                ":cantidad" => $cantidad,
                ":carrito" => $carritoId,
                ":producto" => $productoId
            ));
        }
        responder(obtenerResumenCarrito($conexion, $carritoId));
    }

    if ($accion === "comprar") {
        if (empty($_SESSION["usuario_id"])) {
            responder(array("mensaje" => "Inicia sesión para finalizar la compra."), 401);
        }
        $resumen = obtenerResumenCarrito($conexion, $carritoId);
        if (!$resumen["productos"]) {
            responder(array("mensaje" => "Tu carrito está vacío."), 400);
        }

        $conexion->beginTransaction();
        $pedido = $conexion->prepare(
            "INSERT INTO pedidos (usuario_id, total) VALUES (:usuario, :total)"
        );
        $pedido->execute(array(":usuario" => $_SESSION["usuario_id"], ":total" => $resumen["total"]));
        $pedidoId = (int) $conexion->lastInsertId();
        $detallePedido = $conexion->prepare(
            "INSERT INTO pedido_detalles (pedido_id, producto_id, nombre_producto, precio, cantidad)
             VALUES (:pedido, :producto, :nombre, :precio, :cantidad)"
        );
        foreach ($resumen["productos"] as $producto) {
            $detallePedido->execute(array(
                ":pedido" => $pedidoId,
                ":producto" => $producto["producto_id"],
                ":nombre" => $producto["nombre"],
                ":precio" => $producto["precio"],
                ":cantidad" => $producto["cantidad"]
            ));
        }
        $vaciar = $conexion->prepare("DELETE FROM carrito_detalles WHERE carrito_id = :carrito");
        $vaciar->execute(array(":carrito" => $carritoId));
        $conexion->commit();
        responder(array("mensaje" => "¡Compra registrada con éxito!", "pedido_id" => $pedidoId));
    }

    responder(array("mensaje" => "Acción no válida."), 400);
} catch (PDOException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    responder(array("mensaje" => "Ocurrió un error al procesar la solicitud."), 500);
}
?>
