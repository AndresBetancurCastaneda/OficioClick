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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo e($user['nombre'] . ' ' . $user['apellido']); ?></td>
                                    <td><?php echo e($user['email']); ?></td>
                                    <td><?php echo e($user['role_name']); ?></td>
                                    <td><?php echo e($user['estado']); ?></td>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td><?php echo e($reservation['titulo']); ?></td>
                                    <td><?php echo e($reservation['worker_name'] . ' ' . $reservation['worker_lastname']); ?></td>
                                    <td><?php echo e($reservation['fecha']); ?> - <?php echo e($reservation['hora']); ?></td>
                                    <td><span class="status-badge status-<?php echo e(strtolower($reservation['estado'])); ?>"><?php echo e($reservation['estado']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
