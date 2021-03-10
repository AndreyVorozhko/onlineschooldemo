<?php

set_time_limit(0);

//phpinfo();

//$output = shell_exec('ls -lart');
//COMPOSER_HOME="/var/www/elixiri.dot.us70/data/www/someproject.ru/.composer" /opt/php71/bin/php -i|grep xmlw'
//

$output = shell_exec('

cd ../;
COMPOSER_HOME="/var/www/elixiri.dot.us70/data/www/someproject.ru/.composer" /opt/php71/bin/php composer.phar dumpautoload 2>&1'

);


//$output = shell_exec('whereis php');

//$output = shell_exec('curl -sS https://getcomposer.org/installer | php 2>&1');

//$output = shell_exec('su -u root -p myPass; whoami');

//$output = __DIR__;
echo "<pre>$output</pre>";
