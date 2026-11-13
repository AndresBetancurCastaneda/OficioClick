<section class="section">
    <div class="container detail-layout">
        <article class="detail-card">
            <span class="eyebrow"><?php echo e($service['category_name']); ?></span>
            <h1><?php echo e($service['titulo']); ?></h1>
            <div class="detail-meta">
                <span>Proveedor: <?php echo e($service['worker_name'] . ' ' . $service['worker_lastname']); ?></span>
                <span>✦ <?php echo number_format(getAverageRating((int) $service['id']), 1); ?> / 5</span>
            </div>
            <p><?php echo e($service['descripcion']); ?></p>
            <div class="detail-price"><?php echo formatMoney((float) $service['precio']); ?> por hora</div>
        </article>

        <?php if (isLoggedIn() && $_SESSION['user']['role'] === 'Cliente'): ?>
            <aside class="booking-card">
                <h3>Reservar servicio</h3>
                <form method="post" action="index.php">
                    <input type="hidden" name="action" value="reserve-service">
                    <input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>">

                    <label>
                        Fecha
                        <input type="date" name="reservation_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </label>
                    <label>
                        Hora
                        <input type="time" name="reservation_time" required>
                    </label>
                    <button class="btn btn-primary btn-block" type="submit">Solicitar reserva</button>
                </form>
            </aside>
        <?php else: ?>
            <aside class="booking-card">
                <h3>Necesitas una cuenta</h3>
                <p>Inicia sesión como cliente para reservar este servicio.</p>
                <a class="btn btn-primary btn-block" href="index.php?page=login">Iniciar sesión</a>
            </aside>
        <?php endif; ?>
    </div>

    <div class="container reviews-block">
        <div class="section-header">
            <div>
                <span class="eyebrow">Valoraciones</span>
                <h2>Comentarios y reseñas</h2>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <p>Aún no hay reseñas para este servicio.</p>
            </div>
        <?php else: ?>
            <div class="review-list">
                <?php foreach ($reviews as $review): ?>
                    <article class="review-item">
                        <div class="review-top">
                            <strong><?php echo e($review['nombre'] . ' ' . $review['apellido']); ?></strong>
                            <span><?php echo str_repeat('★', (int) $review['estrellas']); ?><?php echo str_repeat('☆', 5 - (int) $review['estrellas']); ?></span>
                        </div>
                        <p><?php echo e($review['comentario']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
