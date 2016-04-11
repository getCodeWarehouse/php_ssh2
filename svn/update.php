<?php
include_once '/root/zhangshijing/svn/config.php';
error_reporting(E_ALL);
// 设置下字符集，有不认识的字符，也会导致不可更新
putenv("LC_CTYPE=zh_CN.UTF-8");
$retAry = array();
// $handle = exec('svn co --username zhangshijing --password 123456 file:///opt/svn/svnrepos /tmp/',$retAry);
$handle = exec('svn up --username zhangshijing --password 123456 /root/zhangshijing/file 2>&1',$retAry);

array_pop($retAry);

$ssh2 = new Ssh2(array('prefixPath'=>PREFIX_PATH,'publicKey'=>PUBLIC_KEY,'privateKey'=>PRIVATE_KEY,'logPath'=>LOG_PATH));

$ssh2->handle($retAry);
