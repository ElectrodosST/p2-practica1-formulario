La tienda utiliza PHP, MySQL y XAMPP. Para ponerla en marcha:

1. Copia la carpeta del proyecto dentro de `C:\xampp\htdocs`.
2. Inicia Apache y MySQL desde el panel de XAMPP.
3. Abre phpMyAdmin, crea la base de datos `shein_db` e importa `shein_db.sql`. El archivo crea las tablas de usuarios, productos, carritos y pedidos, y carga los productos iniciales.
4. Si tu configuración local de MySQL no usa `root` sin contraseña, actualiza las credenciales en `conexion\db.php`.
5. Abre `http://localhost/nombre-de-la-carpeta/index.html`.

El catálogo y sus precios se consultan desde MySQL. Los productos se pueden agregar desde el inicio o desde cada sección; el carrito se mantiene en la base de datos durante la sesión, y para registrar un pedido es necesario iniciar sesión o crear una cuenta. Los pedidos y sus productos quedan guardados en las tablas `pedidos` y `pedido_detalles`.

El inicio de sesión reconoce la contraseña de texto plano incluida en la cuenta de demostración existente y la convierte a un hash seguro al iniciar sesión.
