<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="trust-badge"><span aria-hidden="true">✓</span> Profesionales para el Valle de Aburrá</span>
            <h1>El oficio que necesitas, <span>a un clic.</span></h1>
            <p>Encuentra profesionales confiables para tu hogar. Reserva servicios por horas de forma simple, segura y sin complicaciones.</p>
            <form method="get" action="index.php" class="quick-search"<?php echo $formErrorDescription; ?>>
                <input type="hidden" name="page" value="catalog">
                <label>
                    ¿Qué servicio necesitas?
                    <select name="category">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo e($category['nombre']); ?>"><?php echo e($category['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Busca por servicio
                    <input type="search" name="search" placeholder="Ej. reparación de fugas">
                </label>
                <label>
                    Calificación mínima
                    <select name="min_rating">
                        <option value="">Cualquier calificación</option>
                        <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                            <option value="<?php echo $rating; ?>"><?php echo $rating; ?> estrellas o más</option>
                        <?php endfor; ?>
                    </select>
                </label>
                <button class="btn btn-primary" type="submit"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="2"/><path d="m16 16 4.2 4.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg> Buscar</button>
            </form>
            <div class="hero-actions">
                <a class="btn btn-secondary" href="index.php?page=register">Crear cuenta</a>
            </div>
            <ul class="hero-benefits">
                <li><span aria-hidden="true">✓</span> Cuatro categorías de oficios</li>
                <li><span aria-hidden="true">✓</span> Calificaciones de clientes</li>
                <li><span aria-hidden="true">✓</span> Reserva sin pagos en línea</li>
            </ul>
            <ul class="stats-list hero-stats">
                <li>
                    <strong><?php echo $metrics === null ? 'No disponible' : number_format($metrics['available_services']); ?></strong>
                    <span>servicios disponibles</span>
                </li>
                <li>
                    <strong><?php echo $metrics === null ? 'No disponible' : number_format($metrics['reservations_total']); ?></strong>
                    <span>reservas registradas</span>
                </li>
                <li>
                    <strong>
                        <?php if ($metrics === null): ?>
                            No disponible
                        <?php elseif ($metrics['available_average_rating'] === null): ?>
                            Sin reseñas
                        <?php else: ?>
                            <?php echo number_format($metrics['available_average_rating'], 1); ?> / 5
                        <?php endif; ?>
                    </strong>
                    <span>
                        <?php echo $metrics === null
                            ? 'promedio de servicios disponibles'
                            : number_format($metrics['available_review_count']) . ' reseñas de servicios disponibles'; ?>
                    </span>
                </li>
            </ul>
        </div>
        <div class="hero-visual">
            <div class="hero-art" aria-hidden="true">
                <div class="art-sun"></div>
                <div class="art-house">
                    <div class="art-roof"></div>
                    <div class="art-window"></div>
                    <div class="art-door"></div>
                    <div class="art-wall-pattern"></div>
                </div>
                <div class="art-worker"><span></span><i></i></div>
            </div>
            <div class="confirmation-card">
                <span class="confirmation-icon" aria-hidden="true">✓</span>
                <span><strong>Reserva en un solo paso</strong><small>Elige fecha y hora desde el detalle</small></span>
            </div>
            <?php if (!empty($services)): ?>
                <?php $featuredService = $services[0]; ?>
                <a class="worker-card" href="index.php?page=service&id=<?php echo (int) $featuredService['id']; ?>">
                    <span class="worker-avatar"><?php echo e(strtoupper(substr($featuredService['worker_name'], 0, 1) . substr($featuredService['worker_lastname'], 0, 1))); ?></span>
                    <span><strong><?php echo e($featuredService['titulo']); ?></strong><small><?php echo e($featuredService['worker_name'] . ' ' . $featuredService['worker_lastname']); ?> · Ver servicio</small></span>
                </a>
            <?php else: ?>
                <div class="worker-card">
                    <span class="worker-avatar" aria-hidden="true">OC</span>
                    <span><strong>Servicios para tu hogar</strong><small>Explora las categorías disponibles</small></span>
                </div>
            <?php endif; ?>
            <div class="hero-count"><strong><?php echo count($categories); ?></strong><span>categorías disponibles</span></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <div>
                <span class="eyebrow">Categorías</span>
                <h2>Categorías del catálogo</h2>
            </div>
        </div>
        <div class="category-grid">
            <?php if (empty($categories)): ?>
                <div class="empty-state"><p>No se encontraron categorías.</p></div>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                    <a class="category-card" href="index.php?page=catalog&amp;category=<?php echo e(rawurlencode($category['nombre'])); ?>">
                        <div class="icon-badge blue" aria-hidden="true">✦</div>
                        <h3><?php echo e($category['nombre']); ?></h3>
                        <p>Explora los servicios clasificados en esta categoría.</p>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section muted-section">
    <div class="container">
        <div class="section-header">
            <div>
                <span class="eyebrow">Destacados</span>
                <h2>Servicios recomendados</h2>
            </div>
            <a class="text-link" href="index.php?page=catalog">Ver catálogo completo</a>
        </div>

        <div class="product-grid">
            <?php foreach (array_slice($services, 0, 4) as $service): ?>
                <article class="product-card">
                    <div class="service-art" aria-hidden="true"><span>✦</span><small>Disponible</small></div>
                    <div class="product-top">
                        <span class="pill"><?php echo e($service['category_name']); ?></span>
                        <?php $serviceRating = getAverageRating((int) $service['id']); ?>
                        <span class="rating"><?php echo $serviceRating === null ? 'Sin reseñas' : '★ ' . number_format($serviceRating, 1); ?></span>
                    </div>
                    <h3><?php echo e($service['titulo']); ?></h3>
                    <p><?php echo e(substr($service['descripcion'], 0, 95)); ?>...</p>
                    <div class="product-meta">
                        <span><?php echo e($service['worker_name'] . ' ' . $service['worker_lastname']); ?></span>
                        <strong><?php echo formatMoney((float) $service['precio']); ?></strong>
                    </div>
                    <a class="btn btn-primary btn-block" href="index.php?page=service&id=<?php echo (int) $service['id']; ?>">Ver detalle</a>
                </article>
            <?php endforeach; ?>
            <?php if (empty($services)): ?>
                <div class="empty-state"><p>No hay servicios disponibles en este momento.</p></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="how-it-works">
    <div class="container">
        <div class="how-heading">
            <span class="eyebrow">Fácil y transparente</span>
            <h2>Resuelve en tres pasos</h2>
        </div>
        <div class="steps-grid">
            <article class="step-card">
                <span class="step-icon" aria-hidden="true">⌕</span>
                <span class="step-number">1</span>
                <h3>Busca y compara</h3>
                <p>Explora las cuatro categorías y elige según tarifa y calificación.</p>
            </article>
            <article class="step-card">
                <span class="step-icon" aria-hidden="true">▦</span>
                <span class="step-number">2</span>
                <h3>Solicita tu reserva</h3>
                <p>Define fecha y hora. El profesional confirmará tu solicitud.</p>
            </article>
            <article class="step-card">
                <span class="step-icon" aria-hidden="true">☆</span>
                <span class="step-number">3</span>
                <h3>Recibe y califica</h3>
                <p>Coordina directamente el pago y comparte tu experiencia.</p>
            </article>
        </div>
    </div>
</section>
