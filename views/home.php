<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="eyebrow">Soluciones confiables para tu hogar</span>
            <h1>Contrata servicios por horas confiables en Medellín</h1>
            <p>OficioClick conecta clientes con trabajadores especializados en plomería, electricidad, obra blanca y aseo general.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="index.php?page=catalog">Explorar servicios</a>
                <a class="btn btn-secondary" href="index.php?page=register">Crear cuenta</a>
            </div>
            <ul class="stats-list">
                <li><strong>+1.200</strong><span>servicios activos</span></li>
                <li><strong>4.8/5</strong><span>calificación media</span></li>
                <li><strong>24h</strong><span>respuesta promedio</span></li>
            </ul>
        </div>
        <div class="hero-panel">
            <div class="mini-card">
                <span class="mini-label">Servicio destacado</span>
                <h3>Reparación de fugas</h3>
                <p>Atención rápida, certificada y con historial de clientes.</p>
                <div class="mini-meta">
                    <span>⭐ 4.9</span>
                    <span>Desde $12.000</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <div>
                <span class="eyebrow">Categorías</span>
                <h2>Categorías disponibles</h2>
            </div>
        </div>
        <div class="category-grid">
            <a class="category-card" href="index.php?page=catalog&category=Plomer%C3%ADa">
                <div class="icon-badge blue">💧</div>
                <h3>Plomería</h3>
                <p>Fugas, instalaciones y mantenimiento.</p>
            </a>
            <a class="category-card" href="index.php?page=catalog&category=Electricidad">
                <div class="icon-badge cyan">⚡</div>
                <h3>Electricidad</h3>
                <p>Instalaciones y reparaciones eléctricas.</p>
            </a>
            <a class="category-card" href="index.php?page=catalog&category=Obra%20blanca">
                <div class="icon-badge green">🧱</div>
                <h3>Obra blanca</h3>
                <p>Pintura, acabados y mejoras del hogar.</p>
            </a>
            <a class="category-card" href="index.php?page=catalog&category=Aseo%20general">
                <div class="icon-badge amber">🧽</div>
                <h3>Aseo general</h3>
                <p>Limpieza profunda y mantenimiento doméstico.</p>
            </a>
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
                    <div class="product-top">
                        <span class="pill"><?php echo e($service['category_name']); ?></span>
                        <span class="rating">★ <?php echo number_format(getAverageRating((int) $service['id']), 1); ?></span>
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
        </div>
    </div>
</section>
