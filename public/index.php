<?php
// 前端控制器：所有網址都由這裡進入（請將 Plesk 的「文件根目錄」設為 httpdocs/public）
require_once dirname(__DIR__) . '/app/bootstrap.php';

App\Core\App::run();
