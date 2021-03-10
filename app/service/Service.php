<?php

namespace app\service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

use vorozhok\App;
use vorozhok\Cache;

use samdark\sitemap\Sitemap;
use samdark\sitemap\Index;

// Это служебный класс для методов, принадлежащий только этому приложению
class Service{
	
    public static function mail(array $data){
		//array data sample

		// Создаем письмо
		$mail = new PHPMailer();
		$mail->isSMTP();                   // Отправка через SMTP
		$mail->Host   = App::$reg->getProperty('smtp_host');  // Адрес SMTP сервера
		$mail->SMTPAuth   = true;          // Enable SMTP authentication
		$mail->Username   = App::$reg->getProperty('smtp_login');       // ваше имя пользователя (без домена и @)
		$mail->Password   = App::$reg->getProperty('smtp_password');    // ваш пароль
		$mail->CharSet = App::$reg->getProperty('charset'); // Кодировка, иначе будут кракозябры
		$mail->SMTPSecure = 'ssl';         // шифрование ssl
		$mail->Port   = 465;               // порт подключения
		 
		$mail->setFrom($data['fromaddr'], $data['fromname']);    // от кого
		$mail->addAddress($data['toaddr'], $data['toname']); // кому
		 
		$mail->Subject = $data['sub'];
		$mail->msgHTML($data['html']);

		// Отправляем
		if (!$mail->send()) {
		  return ['status' => 'fail', 'error' => "Ошибка: {$mail->ErrorInfo}"];
		}
		
		return ['status' => 'success'];
	}
	
	public static function geo(array $options){
		
		//$json = file_get_contents('http://ip-api.com/json/' . $options['ip'] . '?lang=ru');
		
		$handler = curl_init("http://ip-api.com/json/{$options['ip']}?lang=ru");
		curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handler, CURLOPT_HEADER, false);
		curl_setopt($handler, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, false);
		$json = curl_exec($handler);
		curl_close($handler);
		$geo = json_decode($json, TRUE);
		
		if($geo['status'] != 'success'){
			$long_ip = ip2long($options['ip']);
			$geo = \R::getRow("SELECT g.country, c.name FROM gbase AS g INNER JOIN city AS c ON c.id=g.city_id WHERE g.long_ip1<=$long_ip AND g.long_ip2>=$long_ip LIMIT 1");
			$geo = (!empty($geo)) ? $geo['name'] : 'неизвестно';
		}
		return $geo;
	}
	
	public static function cooCity(){
		
		if(!isset($_COOKIE['city'])){
			$geo = Service::geo(['ip' => $_SERVER['REMOTE_ADDR']]);
			$city = $geo['city'] ?? '';
			setcookie('city', $city, time()+86400);
		}else{
			$city = $_COOKIE['city'];
		}
		
        return $city;
	}
	
	public static function rbLogs(){
		$logs = \R::getDatabaseAdapter()
            ->getDatabase()
            ->getLogger();
		$out = print_r($logs->grep( 'SELECT' ), true);
		return "<pre>$out</pre>";
	}
	
	public static function capGen() {
        //session_start();
		
		$width = 100;
		$height = 60;
		$font_size = 16;
		$length = 4;
		$bg_length = 30;
		
		$letters = ['h', 'e', 'u', 'i', 'c', 'k', 'w', 'n','j','m','s','z'];
		$colors = ['90', '110', '130', '150', '170', '190', '210'];
		
		$font = WWW . DS . 'fonts/OCR-b.ttf';
        $src = \imagecreatetruecolor($width, $height);
        $bg = \imagecolorallocate($src, 255, 255, 255);
        \imageFill($src, 0, 0, $bg);
        for ($i = 0; $i < $bg_length; $i++) {
            $color = imagecolorallocatealpha($src, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), 100);
            $letter = $letters[mt_rand(0, count($letters) - 1)];
            $size = mt_rand($font_size - 1, $font_size + 1);
            imagettftext($src, $size, mt_rand(0, 45), mt_rand($width * 0.1, $width * 0.9),
            mt_rand($height * 0.1, $height * 0.9), $color, $font, $letter);
        }
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $color = imagecolorallocatealpha($src, $colors[mt_rand(0, count($colors) - 1)],
            $colors[mt_rand(0, count($colors) - 1)],
            $colors[mt_rand(0, count($colors) - 1)], mt_rand(20, 40));
            $letter = $letters[mt_rand(0, count($letters) - 1)];
            $size = mt_rand($font_size * 2 - 2, $font_size * 2 + 2);
            $x = ($i + 1) * $font_size  + mt_rand(1, 5);
            $y = $height * 2  / 3 + mt_rand(1, 5);
            $code .= $letter;
            imagettftext($src, $size, rand(0, 15), $x, $y, $color, $font, $letter);
        }
        $_SESSION['code'] = $code;
        //header('Content-type: image/gif');
		ob_start();
        imagegif($src);
		$image = ob_get_clean();
		
		return $image;
    }
	
	public static function capCheck($code) {
        if (!session_id()) session_start();
        return $code === $_SESSION['code'];
    }
	
	public static function isAuth(){
		if(isset($_SESSION['user']) && is_numeric($_SESSION['user']['id'])){
			return true;
		}
		return false;
	}
	
	public static function isAdmin(){
		if(self::isAuth() && $_SESSION['user']['role'] == 'admin'){
			return true;
		}
		return false;
	}
	
	public static function checkHoney($data){
		$cap = $data['captcha'];
		$addr = $data['addr'];
		if($cap == '' && preg_match('%^\d*-\d*$%',$addr)){
			return true;
		}else{
			ob_start();
				var_dump($cap);
			$cap = ob_get_clean();
			ob_start();
				var_dump($addr);
			$addr = ob_get_clean();
			$_SESSION['errors'] = "<ul><li>Вы используете очень старый браузер<br>Код ошибки: $cap|$addr</li></ul>";
			return false;
		}
	}
	
	public static function genSitemap(){
		$index = substr(PATH,0,-1);// delete last / in the string
		
		// create sitemap for static files
		$staticSitemap = new Sitemap(WWW . '/sitemap_static.xml');
		
		// add some URLs
		//$staticSitemap->addItem('https://someproject.ru/articles', time(), Sitemap::DAILY, 0.3);
		$staticLinks = require(CONF . DS . 'config_sitemap.php');
		foreach($staticLinks['sitemap_static'] as $item){
			$url =  "$index{$item['url']}";
			$view = APP . DS . 'views' . DS . $item['view'];
			$lastmod = (is_file($view)) ? filemtime($view) : time();
			$freq = $item['freq'];
			$prio = $item['prio'];

			$staticSitemap->addItem($url, $lastmod, $freq, $prio);
		}
		
		$staticSitemap->write();
		
		// create sitemap for dynamic files
		$dynamicSitemap = new Sitemap(WWW . '/sitemap_dynamic.xml');
		
		//all articles
		$lastmod_all = \R::getRow('SELECT GREATEST(MAX(pubdate), MAX(lastmod)) AS lastmod FROM article LIMIT 1');
		
		$dynamicSitemap->addItem("$index/articles", $lastmod_all['lastmod'], Sitemap::DAILY, 0.6);
		
		//categories
		$cats = \R::getAll('SELECT id, alias FROM acat');
		foreach($cats as $cat){
			$lastmod_cat = \R::getRow('SELECT GREATEST(MAX(pubdate), MAX(lastmod)) AS lastmod FROM article WHERE acat_id=? LIMIT 1', [$cat['id']]);			
			$dynamicSitemap->addItem("$index/articles/{$cat['alias']}", $lastmod_cat['lastmod'], Sitemap::DAILY, 0.7);
		}
		
		//articles
		$arts = \R::getAssoc('SELECT a.alias, a.lastmod, acat.alias AS cat_alias FROM article AS a INNER JOIN acat ON acat.id = a.acat_id');
		//debug($arts);
		foreach($arts as $art_name=>$info){
			$dynamicSitemap->addItem("$index/articles/{$info['cat_alias']}/$art_name", $info['lastmod'], Sitemap::DAILY, 0.8);
		}
		
		$dynamicSitemap->write();
		
		// get URLs of sitemaps written
		$staticSitemapUrls = $staticSitemap->getSitemapUrls(PATH);
		$dynamicSitemapUrls = $dynamicSitemap->getSitemapUrls(PATH);
		
		//all urls in one array
		$allSitemapsUrls = array_merge($staticSitemapUrls, $dynamicSitemapUrls);
		
		// create sitemap index file
		$index = new Index(WWW . '/sitemap_index.xml');
		
		// add all URLs
		foreach ($allSitemapsUrls as $sitemapUrl) {
			$index->addSitemap($sitemapUrl);
		}

		// write it
		$index->write();
	}
	
	// delete directory even if not empty (recursive directory remove)
	public static function rRmDir($dir) {

		$includes = new \FilesystemIterator($dir);

		foreach ($includes as $include) {
			if(is_dir($include) && !is_link($include)) {
				self::rRmDir($include);
			}else{
				unlink($include);
			}
		}
		rmdir($dir);
	}
	
	public static function genRandomStr($length = 16){

		$input = '0123456789abcdefghijklmnopqrstuvwxyz';
		$input_length = strlen($input);
		$random_string = '';
		for($i = 0; $i < $length; $i++) {
        $random_character = $input[mt_rand(0, $input_length - 1)];
        $random_string .= $random_character;
		}
 
		return $random_string;
	}
	
	public static function reactivation($uid, $ip, $oldemail){
		$user = \R::load('user', $uid);
		
		$fromaddr = App::$reg->getProperty('admin_email');
		$city = self::geo(['ip' => $ip]);
		
		$code = self::genRandomStr(rand(7,16));
		
		$cache = Cache::instance();
		$cache->set($code, $uid, 86400);
		
		$homepage = PATH;
		
		$data1 = [
		
			'toname' => $user->name,
			'toaddr' => $user->email,
			'fromname' => 'someproject.ru',
			'fromaddr' => $fromaddr,
			'sub' => 'Активация аккаунта на сайте someproject.ru',
			'html' => "<html>
						<body>
						<h1>Здравствуйте, {$user->name}!</h1>
						<p>Вы изменили адрес электронной почты в вашем аккаунте на сайте <a href=\"{$homepage}\">someproject.ru</a>.</p>
						<p>При изменении адреса электронной почты, требуется активация аккаунта.</p>
						<p>Для этого перейдите по следующей ссылке:</p>
						<p><a href=\"{$homepage}user/activation/$code\"></a></p>
						<p>Ссылка доступна в течение 24-х часов.</p>
						<p>Если у вас возникли проблемы с активацией, пожалуйста, <a href=\"{$homepage}feedback\">свяжитесь с нами</a>.</p>
						<p>С уважением,<br>
						Администрация проекта <a href=\"{$homepage}\">someproject.ru</a></p>
						</body>
					</html>"
		
		];
		
		$data2 = [
		
			'toname' => $user->name,
			'toaddr' => $oldemail,
			'fromname' => 'someproject.ru',
			'fromaddr' => $fromaddr,
			'sub' => 'Уведомление об изменении электронной почты',
			'html' => "<html>
						<body>
						<h1>Здравствуйте, {$user->name}!</h1>
						<p>Вы или кто-то изменил адрес вашего аккаунта на сайте <a href=\"https://someproject.ru\">someproject.ru</a>.</p>
						<p>Изменения произведены с IP: $ip ($city)</p>
						<p>Если это были не вы и вы не делали запросов на изменение, пожалуйста, <a href=\"{$homepage}feedback\">свяжитесь с нами</a>.</p>
						<p>Если письмо попало к вам по ошибке, просто проигнорируйте его.</p>
						<p>С уважением,<br>
						Администрация проекта <a href=\"{$homepage}\">someproject.ru</a></p>
						</body>
					</html>"
		
		];
		
		self::mail($data1);
		self::mail($data2);
	}
}