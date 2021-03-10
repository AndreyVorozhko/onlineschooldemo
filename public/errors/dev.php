<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ошибка</title>
</head>
<body>

<h1>Произошла ошибка</h1>
<p><b>Код ошибки:</b> <?= $errno ?></p>
<p><b>Текст ошибки:</b> <?= $errstr ?></p>
<p><b>Файл, в котором произошла ошибка:</b> <?= $errfile ?></p>
<p><b>Строка, в которой произошла ошибка:</b> <?= $errline ?></p>

<p>Current route:</p>
<p><?= debug(\vorozhok\Router::getRoute()); ?></p>

<p>All routes:</p>
<p><?= debug(\vorozhok\Router::getRoutes()); ?></p>

<p>Session array</p>
<p><?= debug($_SESSION); ?></p>

</body>
</html>