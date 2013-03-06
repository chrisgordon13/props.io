<?php
if (isset($_SERVER['DEPLOY']) && $_SERVER['DEPLOY'] == 'dev') {
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
	$site = 'rfp.io';
} else {
	ini_set("display_errors", 0);
	ini_set("log_errors", 1);
	error_reporting(0);
	$site = 'props.io';
}

set_include_path($_SERVER['DOCUMENT_ROOT'] . PATH_SEPARATOR . get_include_path());

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require $_SERVER['DOCUMENT_ROOT'] . '/lib/autoload.php';

$dependencies = new DependencyHelper();

$dependencies['host'] = $_SERVER['HTTP_HOST'];
$dependencies['site'] = $site;
$dependencies['list'] = str_ireplace(array('.' . $dependencies['site'], $dependencies['site']), '', $_SERVER['HTTP_HOST']);

$dependencies['pass_salt'] = '$2a$07$t.gJX1313.FZZ4hPp1y2CN$';

$dependencies['ean_rev'] = '21';
$dependencies['ean_shop_cid'] = '55505';
$dependencies['ean_book_cid'] = '415085';
$dependencies['ean_api_key'] = 'emrkjutmt5nvtbxmg8nhkwqd';

$dependencies['arrival_date'] = date('m/d/Y', strtotime('next tuesday', strtotime('+4 weeks')));
$dependencies['departure_date'] = date('m/d/Y', strtotime('+2 days', strtotime($dependencies['arrival_date'])));

$dependencies['db_dsn'] = 'mysql:host=66.175.217.53;dbname=propsio';
$dependencies['db_user'] = 'props';
$dependencies['db_pass'] = 'prop$4me';
$dependencies['db'] = 	$dependencies->share(function($d) {
	
	$db = new PDO($d['db_dsn'], $d['db_user'], $d['db_pass']);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	return $db;
});

$dependencies['view'] = $dependencies->share(function($d) {

	$view_loader = new Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'] . '/lib/views');
	$view = new Twig_Environment($view_loader, array('debug' => true));
	$view->addExtension(new Twig_Extension_Debug());

    return $view;
});

$dependencies['tools'] = $dependencies->share(function($d) {
	
	return new ToolHelper();
});

$dependencies['user'] = $dependencies->share(function($d) {

	return new UserModel($d);
});

$dependencies['request'] = $dependencies->share(function($d) {
	
	return new RequestHelper();
});

$dependencies['cache_host'] = 'localhost';
$dependencies['cache_port'] = 11211;
$dependencies['cache'] = $dependencies->share(function($d) {

	$cache = new Memcached;
    $cache->addServer($d['cache_host'], $d['cache_port']); 
	return $cache;
});
?>