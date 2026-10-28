function cerrarModal() {
    const modal = document.getElementById('modalOverlay');
    if (modal) {
        modal.style.display = 'none';
    }
}

function switchForm(formType) {
    const formLogin = document.getElementById('formLogin');
    const formRegister = document.getElementById('formRegister');
    
    if (!formLogin || !formRegister) return;

    const loginError = document.getElementById('loginError');
    const registerError = document.getElementById('registerError');
    const registerOk = document.getElementById('registerOk');

    if (loginError) loginError.innerText = '';
    if (registerError) registerError.innerText = '';
    if (registerOk) registerOk.innerText = '';

    if (formType === 'register') {
        formLogin.style.display = 'none';
        formRegister.style.display = 'block'; 
    } else {
        formRegister.style.display = 'none';
        formLogin.style.display = 'block';
    }
}


async function doLogin() {
    const user = document.getElementById('loginUsername').value.trim();
    const pass = document.getElementById('loginPassword').value.trim();
    const errorDiv = document.getElementById('loginError');

    if (!user || !pass) {
        if (errorDiv) errorDiv.innerText = 'Por favor, completa todos los campos.';
        return;
    }

    if (errorDiv) errorDiv.innerText = '';

    try {
       
        const respuesta = await fetch('conexion/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario: user, contrasena: pass })
        });

        const resultado = await respuesta.json();

        if (respuesta.ok) {
            alert('¡Inicio de sesión exitoso!');
            window.location.href = 'index.html';
        } else {
            if (errorDiv) errorDiv.innerText = resultado.mensaje;
        }
    } catch (error) {
        console.error(error);
        if (errorDiv) errorDiv.innerText = 'Error al comunicar con el servidor PHP.';
    }
}

async function doRegister() {
    const nombre = document.getElementById('regNombre').value.trim();
    const user = document.getElementById('regUsername').value.trim();
    const pass = document.getElementById('regPassword').value.trim();
    const errorDiv = document.getElementById('registerError');
    const okDiv = document.getElementById('registerOk');

    if (!nombre || !user || !pass) {
        if (errorDiv) errorDiv.innerText = 'Todos los campos son obligatorios.';
        if (okDiv) okDiv.innerText = '';
        return;
    }

    if (pass.length < 6) {
        if (errorDiv) errorDiv.innerText = 'La contraseña debe tener al menos 6 caracteres.';
        if (okDiv) okDiv.innerText = '';
        return;
    }

    if (errorDiv) errorDiv.innerText = '';

    try {
        // Apuntamos al archivo PHP local
        const respuesta = await fetch('conexion/registro.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, usuario: user, contrasena: pass })
        });

        const resultado = await respuesta.json();

        if (respuesta.ok) {
            if (okDiv) okDiv.innerText = '¡Cuenta creada con éxito! Redirigiendo...';
            
            document.getElementById('regNombre').value = '';
            document.getElementById('regUsername').value = '';
            document.getElementById('regPassword').value = '';

            setTimeout(() => {
                window.location.href = 'index.html';
            }, 1500);
        } else {
            if (errorDiv) errorDiv.innerText = resultado.mensaje;
        }
    } catch (error) {
        console.error(error);
        if (errorDiv) errorDiv.innerText = 'Error al comunicar con el servidor PHP.';
    }
}
