<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Access-Control-Allow-Headers: *');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}


die("这里是云备份的项目代码，由于涉及到雨见账号的加密逻辑，所以先不开源，不影响项目的本地运行。");