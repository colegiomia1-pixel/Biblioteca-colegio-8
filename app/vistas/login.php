<div class="login-wrapper">
    <div class="login-card">
        
        <!-- Ícono flotante superior -->
        <div class="login-badge-icon">
            <i class="bi bi-person-fill" id="icoHeader"></i>
        </div>

        <div class="login-card-content">
            <h2 class="login-title">Inicia sesión ahora</h2>
            <p class="login-subtitle">Ingresá tus credenciales para acceder</p>

            <!-- Selector de perfil Lector / Administrador -->
            <div class="login-profile-selector">
                <button type="button" 
                        id="btnPerfilLector" 
                        class="login-profile-btn <?= ($perfilSeleccionado ?? 'lector') === 'lector' ? 'active' : '' ?>" 
                        onclick="seleccionarPerfil('lector')">
                    <i class="bi bi-person-fill"></i> Lector
                </button>
                <button type="button" 
                        id="btnPerfilAdmin" 
                        class="login-profile-btn <?= ($perfilSeleccionado ?? 'lector') === 'admin' ? 'active' : '' ?>" 
                        onclick="seleccionarPerfil('admin')">
                    <i class="bi bi-shield-lock-fill"></i> Administrador
                </button>
            </div>

            <?php if (!empty($error)): ?>
                <div class="login-alert-error">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php?modulo=<?= ($perfilSeleccionado ?? 'lector') === 'admin' ? 'bibliotecario' : 'lector' ?>" id="formLogin" class="login-form">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="perfil_tipo" id="inputPerfilTipo" value="<?= htmlspecialchars($perfilSeleccionado ?? 'lector') ?>">

                <!-- Campo Usuario -->
                <div class="login-form-group">
                    <label for="usuarioInput">Usuario / Email *</label>
                    <input type="text" name="usuario" class="login-input-pill" id="usuarioInput" placeholder="Ingresá tu usuario o email" required autofocus autocomplete="username">
                </div>

                <!-- Campo Contraseña -->
                <div class="login-form-group">
                    <label for="passInput">Contraseña *</label>
                    <input type="password" name="password" class="login-input-pill" id="passInput" placeholder="Ingresá tu contraseña" required autocomplete="current-password">
                </div>

                <!-- Opciones / Recordar -->
                <div class="login-check-group">
                    <label class="login-checkbox-label">
                        <input type="checkbox" name="recordar" id="recordarCheck">
                        <span>Recordar mi sesión</span>
                    </label>
                </div>

                <!-- Botón de Acceso -->
                <button type="submit" class="login-submit-btn">
                    ACCESO
                </button>
            </form>

        </div>
    </div>
</div>

<script>
function seleccionarPerfil(tipo) {
    document.getElementById('inputPerfilTipo').value = tipo;
    
    var btnLector = document.getElementById('btnPerfilLector');
    var btnAdmin  = document.getElementById('btnPerfilAdmin');
    var icoHeader = document.getElementById('icoHeader');
    var form      = document.getElementById('formLogin');

    if (tipo === 'admin') {
        btnAdmin.classList.add('active');
        btnLector.classList.remove('active');
        icoHeader.className = 'bi bi-shield-lock-fill';
        if (form) form.action = 'login.php?modulo=bibliotecario';
    } else {
        btnLector.classList.add('active');
        btnAdmin.classList.remove('active');
        icoHeader.className = 'bi bi-person-fill';
        if (form) form.action = 'login.php?modulo=lector';
    }
}

// Ejecutar al cargar la pantalla para fijar la acción correcta desde el inicio
document.addEventListener('DOMContentLoaded', function() {
    var perfilInicial = document.getElementById('inputPerfilTipo').value;
    seleccionarPerfil(perfilInicial);
});
</script>