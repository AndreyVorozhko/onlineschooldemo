<?php

define("DEBUG", 0);
define("ADMINNAME", 'admin');// Used unly for admin url
define("ROOT", dirname(__DIR__));
define("WWW", ROOT . '/public');
define("APP", ROOT . '/app');
define("CORE", ROOT . '/vendor/vorozhok/core');
define("LIBS", CORE . '/libs');
define("CACHE", ROOT . '/tmp/cache');
define("CONF", ROOT . '/config');
define("LAYOUT", 'default');
define("DS", DIRECTORY_SEPARATOR);
define("KEYS", CONF . DS . 'keys');

//Для публичного сервера:
//$prot = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
//Для локального сервера
$prot = 'http';

//Находим url главной страницы
$app_path = "$prot://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}";
$app_path = preg_replace("#[^/]+$#", '', $app_path);
$app_path = str_replace('/public/', '', $app_path);
$app_path .= '/';
define("PATH", $app_path);

//Путь к админке
define("ADMIN", PATH . ADMINNAME);

require ROOT . '/vendor/autoload.php';