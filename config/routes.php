<?php

//В этом файле будут находиться правила маршрутизации

use vorozhok\Router;

//User special routes must be above from default routes

$std_con = '(?P<controller>[a-z-]+)';
$std_act = '(?:/(?P<action>[a-z-]+))?';
$std_query = '(?:/(?P<query>(?:[0-9a-z-/]+)?))?';

Router::add("^(?P<action>about|cert|feedback|portfolio|testimonials|fbform){$std_query}$", ['controller' => 'Main']);

Router::add("^articles(?:/(?P<category>[0-9a-z-_]+))?{$std_query}$", ['controller' => 'Articles', 'action' => 'view']);
// не забыть если категории не существует, выдать 404 ошибку

//default routes admin
Router::add('^' .ADMINNAME. '$', ['controller' => 'Main', 'action' => 'index', 'prefix' => 'admin']);

Router::add('^' .ADMINNAME. "/{$std_con}{$std_act}{$std_query}$", ['action' => 'index', 'prefix' => 'admin']);

// default routes user
Router::add('^$', ['controller' => 'Main', 'action' => 'index']);//Пустая строка запроса - главная страница сайта (рег. выражение: начало и конец строки, а между ними ничего нет

Router::add("^{$std_con}{$std_act}{$std_query}$", ['controller' => 'Main', 'action' => 'index']);
