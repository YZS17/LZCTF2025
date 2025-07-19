<?php
$serve = '127.0.0.1';
$username = 'test';
$password = '123456';
$dbname = 'users';
$mysqli = new Mysqli($serve,$username,$password,$dbname);
if($mysqli->connect_error){
    die('connect error:'.$mysqli->connect_errno);
}
$mysqli->set_charset('UTF-8'); // 设置数据库字符集
