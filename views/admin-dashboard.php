<section class="section">
    <div class="container dashboard-shell">
        <div class="section-header">
            <div>
                <span class="eyebrow">Administrador</span>
                <h1>Panel administrativo</h1>
            </div>
        </div>

        <div class="panel-card">
            <h2>Resumen de datos</h2>
            <?php if ($metrics === null): ?>
                <div class="empty-state"><p>No fue posible consultar las métricas. Revisa la conexión y los registros de la aplicación.</p></div>
            <?php else: ?>
                <ul class="stats-list">
                    <li><strong><?php echo number_format($metrics['users_total']); ?></strong><span>usuarios registrados</span></li>
                    <li><strong><?php echo number_format($metrics['active_services']); ?></strong><span>servicios activos</span></li>
                    <li><strong><?php echo number_format($metrics['available_services']); ?></strong><span>servicios disponibles</span></li>
                    <li><strong><?php echo number_format($metrics['reservations_total']); ?></strong><span>reservas registradas</span></li>
                    <li>
                        <strong>
                            <?php echo $metrics['available_average_rating'] === null
                                ? 'Sin reseñas'
                                : number_format($metrics['available_average_rating'], 1) . ' / 5'; ?>
                        </strong>
                        <span><?php echo number_format($metrics['available_review_count']); ?> reseñas de servicios disponibles</span>
                    </li>
                </ul>
                <h3>Reservas por estado</h3>
                <ul class="stats-list">
                    <li><strong><?php echo number_format($metrics['reservations_pending']); ?></strong><span>Pendientes</span></li>
                    <li><strong><?php echo number_format($metrics['reservations_confirmed']); ?></strong><span>Confirmadas</span></li>
                    <li><strong><?php echo number_format($metrics['reservations_finished']); ?></strong><span>Finalizadas</span></li>
                    <li><strong><?php echo number_format($metrics['reservations_cancelled']); ?></strong><span>Canceladas</span></li>
                </ul>
                <p class="muted-text">El promedio se calcula con las calificaciones registradas para servicios activos y disponibles; el número de reseñas indica su denominador.</p>
            <?php endif; ?>
        </div>

        <div class="panel-card">
            <h2>Exportar datos a CSV</h2>
            <p>Descarga reportes de solo lectura con la información actual de la base de datos.</p>
            <div class="inline-form">
                <a class="btn btn-secondary" href="index.php?page=admin-export&amp;dataset=summary">Resumen</a>
                <a class="btn btn-secondary" href="index.php?page=admin-export&amp;dataset=reservations">Reservas</a>
                <a class="btn btn-secondary" href="index.php?page=admin-export&amp;dataset=services">Servicios</a>
                <a class="btn btn-secondary" href="index.php?page=admin-export&amp;dataset=users">Usuarios</a>
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
                                <th>Ciudad</th>
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
                                    <td><?php echo e($user['city_name'] ?? 'Sin ciudad registrada'); ?></td>
                                    <td><?php echo e($user['role_name']); ?></td>
                                    <td><?php echo e($user['estado']); ?></td>
                                    <td>
                                        <?php if ((int) $user['id'] === (int) $_SESSION['user']['id']): ?>
                                            <span class="muted-text">Cuenta actual protegida</span>
                                        <?php else: ?>
                                            <form method="post" action="index.php" class="inline-form compact-inline"<?php echo $formErrorDescription; ?>>
                                                <input type="hidden" name="action" value="update-user">
                                                <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                                <?php echo csrfField(); ?>
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

        <div class="panel-card">
            <h2>Gestión de servicios</h2>
            <?php if (empty($services)): ?>
                <div class="empty-state"><p>No hay servicios registrados.</p></div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Servicio</th>
                                <th>Categoría</th>
                                <th>Trabajador</th>
                                <th>Tarifa por hora</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><?php echo e($service['titulo']); ?></td>
                                    <td><?php echo e($service['category_name']); ?></td>
                                    <td><?php echo e($service['worker_name'] . ' ' . $service['worker_lastname']); ?></td>
                                    <td><?php echo formatMoney((float) $service['precio']); ?></td>
                                    <td><span class="status-badge status-<?php echo e(strtolower($service['estado'])); ?>"><?php echo e(ucfirst($service['estado'])); ?></span></td>
                                    <td>
                                        <details class="service-edit">
                                            <summary class="text-link">Editar</summary>
                                            <form method="post" action="index.php" class="stacked-form"<?php echo $formErrorDescription; ?>>
                                                <input type="hidden" name="action" value="admin-update-service">
                                                <input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>">
                                                <?php echo csrfField(); ?>
                                                <label>
                                                    Título
                                                    <input type="text" name="titulo" value="<?php echo e($service['titulo']); ?>" required>
                                                </label>
                                                <label>
                                                    Descripción
                                                    <textarea name="descripcion" rows="3" required><?php echo e($service['descripcion']); ?></textarea>
                                                </label>
                                                <label>
                                                    Categoría
                                                    <select name="categoria" required>
                                                        <?php foreach ($categories as $category): ?>
                                                            <option value="<?php echo e($category['nombre']); ?>" <?php echo $service['category_name'] === $category['nombre'] ? 'selected' : ''; ?>><?php echo e($category['nombre']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </label>
                                                <label>
                                                    Precio por hora
                                                    <input type="number" min="1" step="1000" name="precio" value="<?php echo e((string) $service['precio']); ?>" required>
                                                </label>
                                                <button class="btn btn-primary btn-small" type="submit">Guardar cambios</button>
                                            </form>
                                        </details>
                                        <form method="post" action="index.php" class="inline-form compact-inline"<?php echo $formErrorDescription; ?>>
                                            <input type="hidden" name="action" value="set-admin-service-status">
                                            <input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $service['estado'] === 'activo' ? 'inactivo' : 'activo'; ?>">
                                            <?php echo csrfField(); ?>
                                            <button class="btn btn-secondary btn-small" type="submit"><?php echo $service['estado'] === 'activo' ? 'Desactivar' : 'Activar'; ?></button>
                                        </form>
                                        <form method="post" action="index.php" class="inline-form compact-inline" data-confirm="¿Eliminar este servicio? Si tiene reservas asociadas, la operación será rechazada."<?php echo $formErrorDescription; ?>>
                                            <input type="hidden" name="action" value="admin-delete-service">
                                            <input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>">
                                            <?php echo csrfField(); ?>
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

        <div class="panel-card">
            <h2>Moderación de calificaciones</h2>
            <?php if (empty($reviews)): ?>
                <div class="empty-state"><p>No hay calificaciones para moderar.</p></div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Servicio</th>
                                <th>Cliente</th>
                                <th>Trabajador</th>
                                <th>Calificación</th>
                                <th>Comentario</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $review): ?>
                                <tr>
                                    <td><?php echo e($review['titulo']); ?></td>
                                    <td><?php echo e($review['client_name'] . ' ' . $review['client_lastname']); ?></td>
                                    <td><?php echo e($review['worker_name'] . ' ' . $review['worker_lastname']); ?></td>
                                    <td><?php echo (int) $review['estrellas']; ?> / 5</td>
                                    <td><?php echo e($review['comentario'] ?? 'Sin comentario'); ?></td>
                                    <td>
                                        <form method="post" action="index.php" class="inline-form" data-confirm="¿Retirar esta reseña del sistema?"<?php echo $formErrorDescription; ?>>
                                            <input type="hidden" name="action" value="delete-review">
                                            <input type="hidden" name="review_id" value="<?php echo (int) $review['id']; ?>">
                                            <?php echo csrfField(); ?>
                                            <button class="btn btn-secondary btn-small" type="submit">Retirar reseña</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel-card full-width-panel">
            <h2>Reservas y reportes</h2>
            <form method="get" action="index.php" class="filter-box"<?php echo $formErrorDescription; ?>>
                <input type="hidden" name="page" value="admin-dashboard">
                <label>
                    Estado
                    <select name="status">
                        <option value="">Todos</option>
                        <?php foreach (['pendiente', 'confirmada', 'finalizada', 'cancelada'] as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo $reservationFilters['status'] === $status ? 'selected' : ''; ?>><?php echo e(ucfirst($status)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Categoría
                    <select name="category_id">
                        <option value="">Todas</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int) $category['id']; ?>" <?php echo (string) $reservationFilters['category_id'] === (string) $category['id'] ? 'selected' : ''; ?>><?php echo e($category['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Desde
                    <input type="date" name="date_from" value="<?php echo e($reservationFilters['date_from']); ?>">
                </label>
                <label>
                    Hasta
                    <input type="date" name="date_to" value="<?php echo e($reservationFilters['date_to']); ?>">
                </label>
                <button class="btn btn-primary" type="submit">Filtrar reservas</button>
                <a class="btn btn-secondary" href="index.php?page=admin-dashboard">Limpiar</a>
            </form>
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
                                <th>Dirección</th>
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
                                    <td><?php echo e($reservation['direccion_atencion'] ?? 'No registrada'); ?></td>
                                    <td><span class="status-badge status-<?php echo e(strtolower($reservation['estado'])); ?>"><?php echo e($reservation['estado']); ?></span></td>
                                    <td>
                                        <?php if (in_array($reservation['estado'], ['pendiente', 'confirmada'], true)): ?>
                                            <form method="post" action="index.php" class="inline-form"<?php echo $formErrorDescription; ?>>
                                                <input type="hidden" name="action" value="update-reservation-status">
                                                <input type="hidden" name="reservation_id" value="<?php echo (int) $reservation['id']; ?>">
                                                <input type="hidden" name="status" value="cancelada">
                                                <?php echo csrfField(); ?>
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

        <div class="panel-card">
            <h2>Registro de auditoría administrativa</h2>
            <?php if (empty($adminActions)): ?>
                <div class="empty-state"><p>Aún no hay acciones administrativas registradas.</p></div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Administrador</th>
                                <th>Acción</th>
                                <th>Elemento</th>
                                <th>Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adminActions as $adminAction): ?>
                                <tr>
                                    <td><?php echo e($adminAction['created_at']); ?></td>
                                    <td><?php echo e($adminAction['admin_name'] . ' ' . $adminAction['admin_lastname']); ?></td>
                                    <td><?php echo e($adminAction['action']); ?></td>
                                    <td><?php echo e($adminAction['entity']); ?> #<?php echo (int) ($adminAction['entity_id'] ?? 0); ?></td>
                                    <td><?php echo e($adminAction['details'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
