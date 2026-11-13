<?php $flash = getFlash(); if (isset($viewData) && is_array($viewData)) { extract($viewData); } ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header class="topbar">
        <div class="container nav-wrap">
            <a class="brand" href="index.php?page=home">
                <span class="brand-mark">O</span>
                <span>OficioClick</span>
            </a>

            <nav class="main-nav">
                <a href="index.php?page=home">Inicio</a>
                <a href="index.php?page=catalog">Catálogo</a>
                <?php if (isLoggedIn()): ?>
                    <?php if ($_SESSION['user']['role'] === 'Cliente'): ?>
                        <a href="index.php?page=client-dashboard">Mi panel</a>
                    <?php elseif ($_SESSION['user']['role'] === 'Trabajador'): ?>
                        <a href="index.php?page=worker-dashboard">Mi panel</a>
                    <?php elseif ($_SESSION['user']['role'] === 'Administrador'): ?>
                        <a href="index.php?page=admin-dashboard">Administración</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="nav-actions">
                <?php if (isLoggedIn()): ?>
                    <span class="user-pill"><?php echo e($_SESSION['user']['name']); ?> · <?php echo e($_SESSION['user']['role']); ?></span>
                    <form method="post" action="index.php?page=logout">
                        <input type="hidden" name="action" value="logout">
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
            <div class="alert alert-<?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></div>
        </div>
    <?php endif; ?>

    <main class="page-shell">
        <?php include $viewFile; ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-content">
            <div>
                <h3>OficioClick</h3>
                <p>Contrata servicios por horas confiables en Medellín.</p>
            </div>
            <div>
                <p>Plomería · Electricidad · Obra blanca · Aseo general</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/app.js"></script>
</body>
</html>
