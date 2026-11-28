<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Página inicial del Grupo 2.">
    <title>Casino del Conocimiento</title>
    <?php
    // Cada vista carga style.css (estilos compartidos) y, si corresponde, la hoja propia de su pantalla.
    // $view es el nombre de la vista que está renderizando MustacheRender::renderiza().
    $hojasPorVista = [
            'login' => 'auth', 'registro' => 'auth', 'registroExitoso' => 'auth', 'validar' => 'auth',
            'lobby' => 'lobby',
            'ruleta' => 'ruleta',
            'partida' => 'partida', 'juegoVacio' => 'partida',
            'perfil' => 'perfil', 'perfilPublico' => 'perfil',
            'ranking' => 'ranking',
            'editorInicio' => 'editor', 'editorPreguntas' => 'editor', 'editorFormulario' => 'editor',
            'adminPanel' => 'admin',
    ];
    $hojas = ['style'];
    if (isset($view, $hojasPorVista[$view])) {
        $hojas[] = $hojasPorVista[$view];
    }
    foreach ($hojas as $hoja):
        $ruta = dirname(__DIR__, 2) . "/public/css/$hoja.css";
        // ?v= con la fecha del archivo: el navegador descarga de nuevo la hoja cada vez que cambia.
        $version = is_file($ruta) ? filemtime($ruta) : 0;
        ?>
        <link rel="stylesheet" href="/public/css/<?= $hoja ?>.css?v=<?= $version ?>">
    <?php endforeach; ?>
</head>
<body>

<header class="barra-superior">
    <div class="barra-contenido">
        <a href="/" class="marca">🧠 Juego de Preguntas</a>

        <nav class="barra-nav">
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <!-- si esta logueado -->
                <span class="saludo">Hola, <?= htmlspecialchars($_SESSION['usuario']) ?></span>
                <?php if (in_array((int)($_SESSION['rol_id'] ?? 0), [2, 3], true)): ?>
                    <a href="/pregunta/inicio" class="boton">Panel editor</a>
                <?php endif; ?>
                <?php if ((int)($_SESSION['rol_id'] ?? 0) === 3): ?>
                    <a href="/admin/panel" class="boton">Panel admin</a>
                <?php endif; ?>
                <a href="/usuario/lobby" class="boton">Mesa principal</a>
                <a href="/usuario/logout" class="boton boton-secundario">Salir</a>
            <?php else: ?>
                <!-- si no esta logueado -->
                <a href="/usuario/login" class="boton boton-secundario">Entrar al casino</a>
                <a href="/usuario/registro" class="boton">Abrir cuenta</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
