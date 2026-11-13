<section class="auth-section">
    <div class="container auth-shell narrow">
        <div class="auth-card">
            <div class="auth-header">
                <span class="eyebrow">Acceso</span>
                <h1>Iniciar sesión</h1>
            </div>

            <form method="post" action="index.php">
                <input type="hidden" name="action" value="login">
                <label>
                    Correo electrónico
                    <input type="email" name="email" required>
                </label>
                <label>
                    Contraseña
                    <input type="password" name="password" required>
                </label>
                <div class="form-actions">
                    <button class="btn btn-primary btn-block" type="submit">Entrar</button>
                </div>
            </form>

            <p class="inline-note">¿No tienes cuenta? <a href="index.php?page=register">Regístrate aquí</a></p>
        </div>
    </div>
</section>
