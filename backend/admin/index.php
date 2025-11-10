<?php
define("APPLICATION_PATH", realpath('.'));
$paths = array(
    APPLICATION_PATH.'/controllers',
    APPLICATION_PATH.'/models',
    APPLICATION_PATH.'/views',
    APPLICATION_PATH.'/libs',
    APPLICATION_PATH.'/includes',
    get_include_path()
);

set_include_path(implode(PATH_SEPARATOR, $paths));

function __autoload($className){	
	
	$fileName = str_replace('\\','/', $className);

	if ($fileName=="login"){
		$fileName = "index";
	}

	require_once "$fileName.php";	
}

new Bootstrap();// 

?>