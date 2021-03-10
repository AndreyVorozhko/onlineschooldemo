<?php

function debug($arr){
    echo '<pre>' . print_r($arr, true) . '</pre>';
}

function redirect($http = false){
    if($http){
        $redirect = $http;
    }else{
        $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : PATH;
    }
    header("Location: $redirect");
    exit;
}

function secureStr($str){
    $str = trim($str);
    $str = stripslashes($str);
    $str = strip_tags($str);
    $str = htmlentities($str);
    return $str;
}

// Прошедшее время с указанного до текущего
/*
Use example :
echo time_elapsed_string('2013-05-01 00:22:35');
echo time_elapsed_string('@1367367755'); # timestamp input
echo time_elapsed_string('2013-05-01 00:22:35', true);
*/

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}