<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Página inicial del Grupo 2.">
    <title>Juego de Preguntas</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>

    <header class="barra-superior">
        <div style="display: flex; justify-content: space-between;
        align-items: center; max-width: 1000px; margin: 0 auto; padding: 0 1rem;">
            <a href="/" class="marca">🧠 Juego de Preguntas</a>

            <nav>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <!-- si esta logueado -->
                    <span style="color: #fff; margin-right: 10px;">Hola, <?= htmlspecialchars($_SESSION['usuario']) ?></span>
                    <a href="/usuario/lobby" class="boton">Lobby</a>
                    <a href="/usuario/logout" class="boton boton-secundario">Salir</a>
                <?php else: ?>
                    <!-- si no esta logueado -->
                    <a href="/usuario/login" class="boton boton-secundario">Ingresar</a>
                    <a href="/usuario/registro" class="boton">Registrarse</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>