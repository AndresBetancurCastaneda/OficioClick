<section class="section">
    <div class="container dashboard-shell">
        <div class="section-header">
            <div>
                <span class="eyebrow">Cliente</span>
                <h1>Panel del cliente</h1>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="panel-card wide-panel">
                <h2>Historial de reservas</h2>
                <?php if (empty($reservations)): ?>
                    <div class="empty-state"><p>No tienes reservas registradas.</p></div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Servicio</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Precio</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $reservation): ?>
                                    <tr>
                                        <td><?php echo e($reservation['titulo']); ?></td>
                                        <td><?php echo e($reservation['fecha']); ?> - <?php echo e($reservation['hora']); ?></td>
                                        <td><span class="status-badge status-<?php echo e(strtolower($reservation['estado'])); ?>"><?php echo e($reservation['estado']); ?></span></td>
                                        <td><?php echo formatMoney((float) $reservation['precio']); ?></td>
                                        <td>
                                            <?php if ($reservation['estado'] === 'finalizada'): ?>
                                                <form method="post" action="index.php" class="inline-form">
                                                    <input type="hidden" name="action" value="submit-review">
                                                    <input type="hidden" name="reservation_id" value="<?php echo (int) $reservation['id']; ?>">
                                                    <select name="stars" class="compact-select">
                                                        <option value="5">5★</option>
                                                        <option value="4">4★</option>
                                                        <option value="3">3★</option>
                                                        <option value="2">2★</option>
                                                        <option value="1">1★</option>
                                                    </select>
                                                    <textarea name="comment" rows="2" placeholder="Comentario opcional"></textarea>
                                                    <button class="btn btn-primary btn-small" type="submit">Calificar</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="muted-text">Pendiente</span>
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
    </div>
</section>
