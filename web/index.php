<?php
error_reporting(E_ALL);
session_start();

//autoloader
$autoload = function($class){
    $matches = array();
    preg_match('/(?P<namespace>.+\\\)?(?P<class>[^\\\]+$)/', $class, $matches);
    $filepath = '../src/'.str_replace('\\', '/', $matches['namespace']).$matches['class'].'.php';
    if(file_exists($filepath)){
        include $filepath;
        return $class;
    }
    return false;
};
spl_autoload_register($autoload);

$config = require '../config.php';

//database
$dbConfig = $config['db'];
$dsn = sprintf('mysql:host=%s;dbname=%s;',$dbConfig['host'],$dbConfig['schema']);
$user = $dbConfig['username'];
$pass = $dbConfig['password'];
$dbAdapter = new PDO($dsn,$user,$pass,array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
\Database\PDOAdapterProvider::setAdapter($dbAdapter);
\User::$dbAdapter = \Database\PDOAdapterProvider::getAdapter();

//setting language
if(array_key_exists('l', $_REQUEST) && $_REQUEST['l'] != ''){
    $_SESSION['l'] = $_REQUEST['l'];
}

//simple routing
$parsedUrl = parse_url($_SERVER['REQUEST_URI']);
$urlChunks = array_values(
    array_filter(explode('/', $parsedUrl['path']), function($el){
        if(is_string($el) && strlen($el) > 0) return true;
    })
);
for($i=1; $i <= count($urlChunks); $i++){
    $params[] = array_key_exists($i, $urlChunks)? $urlChunks[$i] : null;
}

//index controller alone, so url is for actions
$controller = new \Controller\Index();
$actionName = (isset($urlChunks[0]) ? $urlChunks[0] : 'index').'Action';
$params = isset($params) ? $params : null;
$content = $controller->$actionName($params);

print $content;
