(function () {
    const catalogo = document.querySelector("[data-catalogo]");
    const paginaCarrito = document.querySelector("[data-carrito]");
    const api = (catalogo || paginaCarrito).dataset.api;

    function escapar(valor) {
        return String(valor).replace(/[&<>"']/g, function (caracter) {
            return {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#39;"
            }[caracter];
        });
    }

    function formatoPrecio(precio) {
        return new Intl.NumberFormat("es-MX", {
            style: "currency",
            currency: "MXN"
        }).format(precio);
    }

    async function solicitar(url, opciones) {
        const respuesta = await fetch(url, opciones || {});
        const resultado = await respuesta.json();
        if (!respuesta.ok) {
            const error = new Error(resultado.mensaje || "No fue posible completar la solicitud.");
            error.status = respuesta.status;
            throw error;
        }
        return resultado;
    }

    function actualizarContador(cantidad) {
        document.querySelectorAll("[data-cart-count]").forEach(function (elemento) {
            elemento.textContent = cantidad;
        });
    }

    async function refrescarContador() {
        try {
            const carrito = await solicitar(api + "?accion=carrito");
            actualizarContador(carrito.cantidad);
        } catch (error) {
            console.error(error);
        }
    }

    function urlImagen(imagen) {
        if (/^https?:\/\//i.test(imagen)) {
            return imagen;
        }
        return (api.indexOf("../") === 0 ? "../" : "") + imagen;
    }

    async function cargarCatalogo() {
        const contenedor = catalogo.querySelector("[data-productos]");
        const estado = catalogo.querySelector("[data-catalogo-estado]");
        try {
            const resultado = await solicitar(
                api + "?accion=productos&categoria=" + encodeURIComponent(catalogo.dataset.catalogo)
            );
            if (!resultado.productos.length) {
                estado.textContent = "No hay productos disponibles en esta sección.";
                return;
            }
            estado.hidden = true;
            contenedor.innerHTML = resultado.productos.map(function (producto) {
                return '<article class="product-card">' +
                    '<img src="' + escapar(urlImagen(producto.imagen)) + '" alt="' + escapar(producto.nombre) + '">' +
                    '<div class="product-info"><h2>' + escapar(producto.nombre) + '</h2>' +
                    '<p>' + escapar(producto.descripcion) + '</p>' +
                    '<strong>' + formatoPrecio(producto.precio) + '</strong>' +
                    '<button type="button" data-agregar="' + producto.id + '">Agregar al carrito</button></div>' +
                    '</article>';
            }).join("");
        } catch (error) {
            estado.textContent = error.message;
        }
    }

    async function cargarCarrito() {
        const contenido = paginaCarrito.querySelector("[data-carrito-contenido]");
        const estado = paginaCarrito.querySelector("[data-carrito-estado]");
        const resumenEl = paginaCarrito.querySelector("[data-carrito-resumen]");
        try {
            const carrito = await solicitar(api + "?accion=carrito");
            actualizarContador(carrito.cantidad);
            estado.hidden = true;
            if (!carrito.productos.length) {
                contenido.innerHTML = '<p class="carrito-vacio">Tu carrito está vacío. <a href="index.html">Explora los productos</a>.</p>';
                resumenEl.innerHTML = "";
                return;
            }
            contenido.innerHTML = '<div class="carrito-tabla"><table><thead><tr>' +
                '<th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th></th>' +
                '</tr></thead><tbody>' + carrito.productos.map(function (producto) {
                    return '<tr><td class="carrito-producto"><img src="' + escapar(urlImagen(producto.imagen)) +
                        '" alt=""><span>' + escapar(producto.nombre) + '</span></td><td>' +
                        formatoPrecio(producto.precio) + '</td><td><div class="cantidad-control">' +
                        '<button type="button" aria-label="Quitar una unidad" data-cantidad="' + producto.producto_id +
                        '" data-valor="' + (producto.cantidad - 1) + '">−</button><span>' + producto.cantidad +
                        '</span><button type="button" aria-label="Agregar una unidad" data-cantidad="' +
                        producto.producto_id + '" data-valor="' + (producto.cantidad + 1) + '">+</button></div></td><td>' +
                        formatoPrecio(producto.subtotal) + '</td><td><button class="btn-quitar" type="button" data-quitar="' +
                        producto.producto_id + '">Eliminar</button></td></tr>';
                }).join("") + '</tbody></table></div>';
            resumenEl.innerHTML = '<div><strong>Total: ' + formatoPrecio(carrito.total) +
                '</strong><button type="button" data-comprar>Finalizar compra</button></div>' +
                '<p>' + (carrito.usuario ? "Sesión: " + escapar(carrito.usuario) : 'Para finalizar, <a href="formulario.html?return=carrito">inicia sesión</a>.') + '</p>';
        } catch (error) {
            estado.textContent = error.message;
        }
    }

    async function enviarAccion(datos) {
        return solicitar(api, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(datos)
        });
    }

    if (catalogo) {
        cargarCatalogo();
        catalogo.addEventListener("click", async function (evento) {
            const boton = evento.target.closest("[data-agregar]");
            if (!boton) return;
            boton.disabled = true;
            try {
                const carrito = await enviarAccion({
                    accion: "agregar",
                    producto_id: Number(boton.dataset.agregar)
                });
                actualizarContador(carrito.cantidad);
                boton.textContent = "Agregado";
                setTimeout(function () {
                    boton.textContent = "Agregar al carrito";
                    boton.disabled = false;
                }, 900);
            } catch (error) {
                alert(error.message);
                boton.disabled = false;
            }
        });
    }

    if (paginaCarrito) {
        cargarCarrito();
        paginaCarrito.addEventListener("click", async function (evento) {
            const botonCantidad = evento.target.closest("[data-cantidad]");
            const botonQuitar = evento.target.closest("[data-quitar]");
            const botonComprar = evento.target.closest("[data-comprar]");
            try {
                if (botonCantidad) {
                    if (Number(botonCantidad.dataset.valor) < 1) {
                        await enviarAccion({
                            accion: "eliminar",
                            producto_id: Number(botonCantidad.dataset.cantidad)
                        });
                    } else {
                        await enviarAccion({
                            accion: "actualizar",
                            producto_id: Number(botonCantidad.dataset.cantidad),
                            cantidad: Number(botonCantidad.dataset.valor)
                        });
                    }
                    await cargarCarrito();
                } else if (botonQuitar) {
                    await enviarAccion({
                        accion: "eliminar",
                        producto_id: Number(botonQuitar.dataset.quitar)
                    });
                    await cargarCarrito();
                } else if (botonComprar) {
                    await enviarAccion({ accion: "comprar" });
                    alert("¡Compra registrada con éxito!");
                    await cargarCarrito();
                }
            } catch (error) {
                if (error.status === 401) {
                    window.location.href = "formulario.html?return=carrito";
                } else {
                    alert(error.message);
                }
            }
        });
    } else {
        refrescarContador();
    }

    window.addEventListener("scroll", function () {
        const header = document.getElementById("headerMain");
        if (header) header.classList.toggle("scrolled", window.scrollY > 30);
    });
})();
