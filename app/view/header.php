<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Página inicial del Grupo 2.">
    <title>Juego de Preguntas</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <!-- <link rel="stylesheet" href="/tp-web2/public/css/style.css"> -->
</head>
<body>

<header class="barra-superior">
    <div class="barra-contenido">
        <a href="/" class="marca">🧠 Juego de Preguntas</a>

        <nav class="barra-nav">
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <!-- si esta logueado -->
                <span class="saludo">Hola, <?= htmlspecialchars($_SESSION['usuario']) ?></span>
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
