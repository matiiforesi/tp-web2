<footer class="pie">
    <p>Trabajo Práctico Final - Programación Web</p>
</footer>
<?php $versionMusica = @filemtime(dirname(__DIR__, 2) . "/public/js/musica.js") ?: 0; ?>
<script src="/public/js/musica.js?v=<?= $versionMusica ?>" defer></script>
</body>
</html>
