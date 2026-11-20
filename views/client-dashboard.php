<?php
$pendingCount = 0;
$completedCount = 0;
$ratedCount = 0;
foreach ($reservations as $reservation) {
    if (in_array($reservation['estado'], ['pendiente', 'confirmada'], true)) {
        $pendingCount++;
    }
    if ($reservation['estado'] === 'finalizada') {
        $completedCount++;
    }
    if (!empty($reservation['calificacion_id'])) {
        $ratedCount++;
    }
}
?>

<section class="section dashboard-section">
    <div class="container dashboard-shell">
        <div class="dashboard-heading">
            <div>
                <span class="eyebrow">Panel del cliente</span>
                <h1>Hola, <?php echo e($_SESSION['user']['name']); ?></h1>
                <p>Gestiona tus reservas y servicios desde un solo lugar.</p>
            </div>
            <div class="date-chip"><span aria-hidden="true">▦</span><?php echo date('d/m/Y'); ?></div>
        </div>

        <div class="metric-grid">
            <article class="metric-card"><span class="metric-icon" aria-hidden="true">▦</span><span><small>Próximas reservas</small><strong><?php echo $pendingCount; ?></strong></span></article>
            <article class="metric-card"><span class="metric-icon" aria-hidden="true">✓</span><span><small>Servicios finalizados</small><strong><?php echo $completedCount; ?></strong></span></article>
            <article class="metric-card"><span class="metric-icon" aria-hidden="true">☆</span><span><small>Calificaciones dadas</small><strong><?php echo $ratedCount; ?></strong></span></article>
        </div>

        <section class="panel-card reservations-panel">
            <div class="panel-heading">
                <div><h2>Mis reservas</h2><p>Historial y estado de tus solicitudes.</p></div>
                <span class="reservation-count"><?php echo count($reservations); ?> en total</span>
            </div>
            <?php if (empty($reservations)): ?>
                <div class="empty-state"><h3>Aún no tienes reservas</h3><p>Explora los servicios disponibles y encuentra ayuda para tu hogar.</p><a class="btn btn-primary" href="index.php?page=catalog">Buscar servicios</a></div>
            <?php else: ?>
                <div class="reservation-list">
                    <?php foreach ($reservations as $reservation): ?>
                        <article class="reservation-item">
                            <span class="reservation-icon" aria-hidden="true">✦</span>
                            <div class="reservation-main">
                                <h3><?php echo e($reservation['titulo']); ?></h3>
                                <p>Con <?php echo e($reservation['worker_name'] . ' ' . $reservation['worker_lastname']); ?> · <?php echo e($reservation['category_name']); ?></p>
                            </div>
                            <div class="reservation-schedule">
                                <span><span aria-hidden="true">▦</span><?php echo e($reservation['fecha']); ?> · <?php echo e(substr($reservation['hora'], 0, 5)); ?></span>
                                <span><span aria-hidden="true">◷</span><?php echo formatMoney((float) $reservation['precio']); ?>/h</span>
                                <?php if (!empty($reservation['direccion_atencion'])): ?>
                                    <span>Atención en: <?php echo e($reservation['direccion_atencion']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="reservation-state">
                                <span class="status-badge status-<?php echo e(strtolower($reservation['estado'])); ?>"><?php echo e(ucfirst($reservation['estado'])); ?></span>
                            </div>
                            <div class="reservation-actions">
                                <?php if ($reservation['estado'] === 'finalizada'): ?>
                                    <?php if (empty($reservation['calificacion_id'])): ?>
                                        <form method="post" action="index.php" class="review-form"<?php echo $formErrorDescription; ?>>
                                            <input type="hidden" name="action" value="submit-review">
                                            <input type="hidden" name="reservation_id" value="<?php echo (int) $reservation['id']; ?>">
                                            <?php echo csrfField(); ?>
                                            <label class="sr-only" for="stars-<?php echo (int) $reservation['id']; ?>">Calificación</label>
                                            <select id="stars-<?php echo (int) $reservation['id']; ?>" name="stars" class="compact-select" required>
                                                <option value="5">5 estrellas</option>
                                                <option value="4">4 estrellas</option>
                                                <option value="3">3 estrellas</option>
                                                <option value="2">2 estrellas</option>
                                                <option value="1">1 estrella</option>
                                            </select>
                                            <label class="sr-only" for="comment-<?php echo (int) $reservation['id']; ?>">Comentario opcional</label>
                                            <textarea id="comment-<?php echo (int) $reservation['id']; ?>" name="comment" rows="1" placeholder="Comentario opcional"></textarea>
                                            <button class="btn btn-primary btn-small" type="submit">Calificar servicio</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="muted-text">Ya calificada</span>
                                    <?php endif; ?>
                                <?php elseif (in_array($reservation['estado'], ['pendiente', 'confirmada'], true)): ?>
                                    <form method="post" action="index.php" class="inline-form"<?php echo $formErrorDescription; ?>>
                                        <input type="hidden" name="action" value="update-reservation-status">
                                        <input type="hidden" name="reservation_id" value="<?php echo (int) $reservation['id']; ?>">
                                        <input type="hidden" name="status" value="cancelada">
                                        <?php echo csrfField(); ?>
                                        <button class="btn btn-secondary btn-small" type="submit">Cancelar reserva</button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted-text">Sin acciones disponibles</span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>
