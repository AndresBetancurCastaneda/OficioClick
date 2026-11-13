<section class="section">
    <div class="container dashboard-shell">
        <div class="section-header">
            <div>
                <span class="eyebrow">Trabajador</span>
                <h1>Panel del trabajador</h1>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="panel-card">
                <h2>Publicar servicio</h2>
                <form method="post" action="index.php" class="stacked-form">
                    <input type="hidden" name="action" value="create-service">
                    <label>
                        Título
                        <input type="text" name="titulo" required>
                    </label>
                    <label>
                        Descripción
                        <textarea name="descripcion" rows="4" required></textarea>
                    </label>
                    <label>
                        Categoría
                        <select name="categoria" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo e($category['nombre']); ?>"><?php echo e($category['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Precio por hora
                        <input type="number" min="1" step="1000" name="precio" required>
                    </label>
                    <button class="btn btn-primary btn-block" type="submit">Publicar</button>
                </form>
            </div>

            <div class="panel-card wide-panel">
                <h2>Mis servicios</h2>
                <?php if (empty($services)): ?>
                    <div class="empty-state"><p>Aún no tienes servicios publicados.</p></div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Servicio</th>
                                    <th>Categoría</th>
                                    <th>Precio</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?php echo e($service['titulo']); ?></td>
                                        <td><?php echo e($service['category_name']); ?></td>
                                        <td><?php echo formatMoney((float) $service['precio']); ?></td>
                                        <td>
                                            <form method="post" action="index.php" class="inline-form compact-inline">
                                                <input type="hidden" name="action" value="delete-service">
                                                <input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>">
                                                <button class="btn btn-secondary btn-small" type="submit">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel-card full-width-panel">
            <h2>Solicitudes relacionadas</h2>
            <?php if (empty($reservations)): ?>
                <div class="empty-state"><p>No hay solicitudes asociadas a tus servicios.</p></div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Servicio</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Cambiar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td><?php echo e($reservation['client_name'] . ' ' . $reservation['client_lastname']); ?></td>
                                    <td><?php echo e($reservation['titulo']); ?></td>
                                    <td><?php echo e($reservation['fecha']); ?> - <?php echo e($reservation['hora']); ?></td>
                                    <td><span class="status-badge status-<?php echo e(strtolower($reservation['estado'])); ?>"><?php echo e($reservation['estado']); ?></span></td>
                                    <td>
                                        <?php if (in_array($reservation['estado'], ['pendiente', 'confirmada'], true)): ?>
                                        <form method="post" action="index.php" class="inline-form compact-inline">
                                            <input type="hidden" name="action" value="update-reservation-status">
                                            <input type="hidden" name="reservation_id" value="<?php echo (int) $reservation['id']; ?>">
                                            <select name="status" class="compact-select">
                                                <?php if ($reservation['estado'] === 'pendiente'): ?>
                                                    <option value="confirmada">Confirmar</option>
                                                <?php elseif ($reservation['estado'] === 'confirmada'): ?>
                                                    <option value="finalizada">Finalizar</option>
                                                <?php endif; ?>
                                            </select>
                                            <button class="btn btn-small btn-primary" type="submit">Guardar</button>
                                        </form>
                                        <?php else: ?>
                                            <span class="muted-text">Sin acciones disponibles</span>
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
