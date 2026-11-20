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
            <label>
                Precio mínimo por hora (COP)
                <input type="number" name="min_price" min="0" step="1000" value="<?php echo e((string) $filters['min_price']); ?>">
            </label>
            <label>
                Precio máximo por hora (COP)
                <input type="number" name="max_price" min="0" step="1000" value="<?php echo e((string) $filters['max_price']); ?>">
            </label>
            <label>
                Calificación mínima
                <select name="min_rating">
                    <option value="">Cualquier calificación</option>
                    <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                        <option value="<?php echo $rating; ?>" <?php echo $filters['min_rating'] === (string) $rating ? 'selected' : ''; ?>><?php echo $rating; ?> estrellas o más</option>
                    <?php endfor; ?>
                </select>
            </label>
            <input type="hidden" name="available_only" value="0">
            <label class="checkbox-line">
                <input type="checkbox" name="available_only" value="1" <?php echo $filters['available_only'] ? 'checked' : ''; ?>>
                Solo disponibles
            </label>
            <button class="btn btn-primary" type="submit">Filtrar</button>
        </form>

        <?php if ($totalServices !== null): ?>
            <p class="muted-text"><?php echo number_format($totalServices); ?> servicios encontrados</p>
        <?php endif; ?>

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
                            <?php $serviceRating = getAverageRating((int) $service['id']); ?>
                            <span class="rating"><?php echo $serviceRating === null ? 'Sin reseñas' : '★ ' . number_format($serviceRating, 1); ?></span>
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

        <?php if ($totalPages > 1): ?>
            <?php $pageQuery = $_GET; unset($pageQuery['page_number']); ?>
            <nav class="pagination" aria-label="Paginación del catálogo">
                <?php if ($currentPage > 1): ?>
                    <?php $previousQuery = array_merge($pageQuery, ['page_number' => $currentPage - 1]); ?>
                    <a class="btn btn-secondary btn-small" href="index.php?<?php echo e(http_build_query($previousQuery)); ?>">Anterior</a>
                <?php endif; ?>
                <span>Página <?php echo $currentPage; ?> de <?php echo $totalPages; ?></span>
                <?php if ($currentPage < $totalPages): ?>
                    <?php $nextQuery = array_merge($pageQuery, ['page_number' => $currentPage + 1]); ?>
                    <a class="btn btn-secondary btn-small" href="index.php?<?php echo e(http_build_query($nextQuery)); ?>">Siguiente</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>
