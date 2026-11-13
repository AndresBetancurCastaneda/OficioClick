<section class="section">
    <div class="container">
        <div class="section-header">
            <div>
                <span class="eyebrow">Catálogo</span>
                <h1>Busca servicios por categoría y disponibilidad</h1>
            </div>
        </div>

        <form method="get" action="index.php" class="filter-box">
            <input type="hidden" name="page" value="catalog">
            <label>
                Categoría
                <select name="category">
                    <option value="">Todas</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo e($category['nombre']); ?>" <?php echo ($filters['category'] === $category['nombre']) ? 'selected' : ''; ?>><?php echo e($category['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Búsqueda
                <input type="text" name="search" value="<?php echo e($filters['search']); ?>" placeholder="Ej. plomería, limpieza...">
            </label>
            <label class="checkbox-line">
                <input type="checkbox" name="available_only" value="1" <?php echo $filters['available_only'] ? 'checked' : ''; ?>>
                Solo disponibles
            </label>
            <button class="btn btn-primary" type="submit">Filtrar</button>
        </form>

        <?php if (empty($services)): ?>
            <div class="empty-state">
                <h3>No hay resultados</h3>
                <p>Prueba con otros filtros o vuelve más tarde.</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($services as $service): ?>
                    <article class="product-card">
                        <div class="product-top">
                            <span class="pill"><?php echo e($service['category_name']); ?></span>
                            <span class="rating">★ <?php echo number_format(getAverageRating((int) $service['id']), 1); ?></span>
                        </div>
                        <h3><?php echo e($service['titulo']); ?></h3>
                        <p><?php echo e($service['descripcion']); ?></p>
                        <div class="product-meta">
                            <span><?php echo e($service['worker_name'] . ' ' . $service['worker_lastname']); ?></span>
                            <strong><?php echo formatMoney((float) $service['precio']); ?></strong>
                        </div>
                        <div class="product-actions">
                            <a class="btn btn-secondary" href="index.php?page=service&id=<?php echo (int) $service['id']; ?>">Detalle</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
