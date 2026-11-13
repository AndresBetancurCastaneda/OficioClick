<section class="auth-section">
    <div class="container auth-shell">
        <div class="auth-card wide">
            <div class="auth-header">
                <span class="eyebrow">Registro</span>
                <h1>Crear una cuenta en OficioClick</h1>
            </div>
            <form method="post" action="index.php" class="form-grid">
                <input type="hidden" name="action" value="register">

                <label>
                    Nombre
                    <input type="text" name="nombre" required>
                </label>
                <label>
                    Apellido
                    <input type="text" name="apellido" required>
                </label>
                <label>
                    Correo electrónico
                    <input type="email" name="email" required>
                </label>
                <label>
                    Teléfono
                    <input type="tel" name="telefono">
                </label>
                <label>
                    Documento de identidad
                    <input type="text" name="documento_identidad">
                </label>
                <label>
                    Dirección
                    <input type="text" name="direccion">
                </label>
                <label>
                    Contraseña
                    <input type="password" name="password" required>
                </label>
                <label>
                    Rol
                    <select name="rol" required>
                        <option value="Cliente">Cliente</option>
                        <option value="Trabajador">Trabajador</option>
                        <option value="Administrador">Administrador</option>
                    </select>
                </label>

                <div class="form-actions full-width">
                    <button class="btn btn-primary" type="submit">Registrarme</button>
                    <a class="btn btn-secondary" href="index.php?page=login">Ya tengo cuenta</a>
                </div>
            </form>
        </div>
    </div>
</section>
