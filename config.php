<?php
define('ROOT_DIR','/root/zhangshijing/');
define('UPLOAD_FILE', ROOT_DIR . 'file/');
define('SVN_FILE', ROOT_DIR . 'svn/');
define('CONNECT_IP','111.111.111.111');
define('PREFIX_PATH','/tmp/');
define('LOG_PATH', ROOT_DIR . 'log/');
define('PUBLIC_KEY',ROOT_DIR . 'key/Identity.pub');
define('PRIVATE_KEY',ROOT_DIR . 'key/Identity');
define('PASSWORD','123456');

function __autoload($name)
{
   if(is_file(SVN_FILE . $name . ".class.php"))
   {
        include_once  SVN_FILE .  "$name.class.php";
   }
}
