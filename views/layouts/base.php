<?php $flash = getFlash(); $formErrorDescription = $flash && $flash['type'] === 'error' ? ' aria-describedby="flash-message"' : ''; if (isset($viewData) && is_array($viewData)) { extract($viewData); } ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo (int) filemtime(APP_ROOT . '/public/assets/css/styles.css'); ?>">
</head>
<body>
    <header class="topbar">
        <div class="container nav-wrap">
            <a class="brand" href="index.php?page=home">
                <span class="brand-mark" aria-hidden="true"><svg class="brand-icon" viewBox="0 0 24 24" fill="none"><path d="M14.4 6.1a5 5 0 0 0 6 6l-3.1-3.1-3.4 3.4 3.1 3.1a5 5 0 0 0-6-6l-7.7 7.7a2.1 2.1 0 0 0 3 3l7.7-7.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span>OficioClick</span>
            </a>

            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" aria-label="Abrir menú de navegación">
                <span class="nav-toggle-icon" aria-hidden="true"></span>
                <span class="sr-only">Abrir menú de navegación</span>
            </button>

            <nav class="main-nav" id="primary-navigation">
                <a href="index.php?page=home">Inicio</a>
                <a href="index.php?page=catalog">Buscar servicios</a>
                <?php if (isLoggedIn()): ?>
                    <?php if ($_SESSION['user']['role'] === 'Cliente'): ?>
                        <a href="index.php?page=client-dashboard">Mi panel</a>
                        <a href="index.php?page=profile">Mi perfil</a>
                    <?php elseif ($_SESSION['user']['role'] === 'Trabajador'): ?>
                        <a href="index.php?page=worker-dashboard">Mi panel</a>
                        <a href="index.php?page=profile">Mi perfil</a>
                    <?php elseif ($_SESSION['user']['role'] === 'Administrador'): ?>
                        <a href="index.php?page=admin-dashboard">Administración</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="nav-actions">
                <?php if (isLoggedIn()): ?>
                    <span class="user-pill"><?php echo e($_SESSION['user']['name']); ?> · <?php echo e($_SESSION['user']['role']); ?></span>
                    <form method="post" action="index.php?page=logout"<?php echo $formErrorDescription; ?>>
                        <input type="hidden" name="action" value="logout">
                        <?php echo csrfField(); ?>
                        <button class="btn btn-secondary" type="submit">Cerrar sesión</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-secondary" href="index.php?page=login">Iniciar sesión</a>
                    <a class="btn btn-primary" href="index.php?page=register">Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="container alert-container">
            <div id="flash-message" class="alert alert-<?php echo e($flash['type']); ?>" role="<?php echo $flash['type'] === 'error' ? 'alert' : 'status'; ?>" aria-live="<?php echo $flash['type'] === 'error' ? 'assertive' : 'polite'; ?>"><?php echo e($flash['message']); ?></div>
        </div>
    <?php endif; ?>

    <main class="page-shell">
        <?php include $viewFile; ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <a class="brand brand-light" href="index.php?page=home">
                    <span class="brand-mark" aria-hidden="true"><svg class="brand-icon" viewBox="0 0 24 24" fill="none"><path d="M14.4 6.1a5 5 0 0 0 6 6l-3.1-3.1-3.4 3.4 3.1 3.1a5 5 0 0 0-6-6l-7.7 7.7a2.1 2.1 0 0 0 3 3l7.7-7.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span>OficioClick</span>
                </a>
                <p>Servicios confiables por horas en Medellín y el área metropolitana.</p>
            </div>
            <div>
                <h2>Explora</h2>
                <a href="index.php?page=catalog">Buscar servicios</a>
                <a href="index.php?page=register">Crear una cuenta</a>
            </div>
            <div>
                <h2>Categorías</h2>
                <p>Plomería · Electricidad · Obra blanca · Aseo general</p>
            </div>
        </div>
        <div class="container footer-bottom">© <?php echo date('Y'); ?> OficioClick. Servicios confiables para tu hogar.</div>
    </footer>

    <script src="assets/js/app.js"></script>
</body>
</html>
