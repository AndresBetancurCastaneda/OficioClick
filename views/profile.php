<section class="section">
    <div class="container dashboard-shell">
        <div class="section-header">
            <div>
                <span class="eyebrow">Mi cuenta</span>
                <h1>Mi perfil</h1>
                <p>Actualiza tus datos de contacto y ubicación.</p>
            </div>
        </div>

        <div class="panel-card">
            <form method="post" action="index.php" class="form-grid"<?php echo $formErrorDescription; ?>>
                <input type="hidden" name="action" value="update-profile">
                <?php echo csrfField(); ?>
                <label>
                    Nombre
                    <input type="text" name="nombre" value="<?php echo e($profile['nombre']); ?>" required>
                </label>
                <label>
                    Apellido
                    <input type="text" name="apellido" value="<?php echo e($profile['apellido']); ?>" required>
                </label>
                <label>
                    Correo electrónico
                    <input type="email" name="email" value="<?php echo e($profile['email']); ?>" required>
                </label>
                <label>
                    Teléfono
                    <input type="tel" name="telefono" value="<?php echo e((string) ($profile['telefono'] ?? '')); ?>" required>
                </label>
                <label>
                    Documento de identidad
                    <input type="text" name="documento_identidad" value="<?php echo e((string) ($profile['documento_identidad'] ?? '')); ?>" required>
                </label>
                <label>
                    Dirección
                    <input type="text" name="direccion" value="<?php echo e((string) ($profile['direccion'] ?? '')); ?>" required>
                </label>
                <label>
                    Ciudad
                    <select name="ciudad_id" required>
                        <option value="">Selecciona una ciudad</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo (int) $city['id']; ?>" <?php echo (int) $profile['ciudad_id'] === (int) $city['id'] ? 'selected' : ''; ?>>
                                <?php echo e($city['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="form-actions full-width">
                    <button class="btn btn-primary" type="submit">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</section>
