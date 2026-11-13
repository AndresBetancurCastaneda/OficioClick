<section class="section">
    <div class="container dashboard-shell">
        <div class="section-header">
            <div>
                <span class="eyebrow">Administrador</span>
                <h1>Panel administrativo</h1>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="panel-card wide-panel">
                <h2>Gestión de usuarios</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo e($user['nombre'] . ' ' . $user['apellido']); ?></td>
                                    <td><?php echo e($user['email']); ?></td>
                                    <td><?php echo e($user['role_name']); ?></td>
                                    <td><?php echo e($user['estado']); ?></td>
                                    <td>
                                        <?php if ((int) $user['id'] === (int) $_SESSION['user']['id']): ?>
                                            <span class="muted-text">Cuenta actual protegida</span>
                                        <?php else: ?>
                                            <form method="post" action="index.php" class="inline-form compact-inline">
                                                <input type="hidden" name="action" value="update-user">
                                                <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                                <select name="role" class="compact-select" aria-label="Rol de <?php echo e($user['nombre'] . ' ' . $user['apellido']); ?>">
                                                    <?php foreach (['Cliente', 'Trabajador', 'Administrador'] as $roleOption): ?>
                                                        <option value="<?php echo e($roleOption); ?>" <?php echo $user['role_name'] === $roleOption ? 'selected' : ''; ?>>
                                                            <?php echo e($roleOption); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <select name="status" class="compact-select" aria-label="Estado de <?php echo e($user['nombre'] . ' ' . $user['apellido']); ?>">
                                                    <option value="activo" <?php echo $user['estado'] === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                                    <option value="inactivo" <?php echo $user['estado'] === 'inactivo' ? 'selected' : ''; ?>>Bloqueado</option>
                                                </select>
                                                <button class="btn btn-small btn-primary" type="submit">Guardar</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel-card full-width-panel">
            <h2>Reservas y reportes</h2>
            <?php if (empty($reservations)): ?>
                <div class="empty-state"><p>No hay reservas registradas.</p></div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Servicio</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td><?php echo e($reservation['titulo']); ?></td>
                                    <td><?php echo e($reservation['client_name'] . ' ' . $reservation['client_lastname']); ?></td>
                                    <td><?php echo e($reservation['fecha']); ?> - <?php echo e($reservation['hora']); ?></td>
                                    <td><span class="status-badge status-<?php echo e(strtolower($reservation['estado'])); ?>"><?php echo e($reservation['estado']); ?></span></td>
                                    <td>
                                        <?php if (in_array($reservation['estado'], ['pendiente', 'confirmada'], true)): ?>
                                            <form method="post" action="index.php" class="inline-form">
                                                <input type="hidden" name="action" value="update-reservation-status">
                                                <input type="hidden" name="reservation_id" value="<?php echo (int) $reservation['id']; ?>">
                                                <input type="hidden" name="status" value="cancelada">
                                                <button class="btn btn-secondary btn-small" type="submit">Cancelar</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="muted-text">Sin acciones</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
