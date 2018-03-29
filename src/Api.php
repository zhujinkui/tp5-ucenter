<?php
// 日志文件
// +----------------------------------------------------------------------
// | PHP version 5.4+
// +----------------------------------------------------------------------
// | Copyright (c) 2012-2014 http://www.17php.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhujinkui <developer@zhujinkui.com>
// +----------------------------------------------------------------------

namespace think;

use think\common\Tools;

define('UCENTER_MODULE_PATH', rtrim(dirname(dirname(__FILE__)), '/\\') . DIRECTORY_SEPARATOR);

require_cache(UCENTER_MODULE_PATH . '/config.php');

abstract class Api{

    public static $logIndex = 1;

    public static function log($text) {
        if(!APP_DEBUG){
            return ;
        }

        if (self::$logIndex == 1) {
            $log = '*************************测试分割线*************************' . PHP_EOL;
            $log .= date("Y-m-d H:i:s", time()) . PHP_EOL;
        }

        $logPath = '/debug/';

        if (!is_dir($logPath)) {
            Tools::mkdirs($logPath);
        }

        $logFile = $logPath . 'log'.date("Y-m-d", time()).'.log';

        if (is_array($text)) {
            $log = self::$logIndex . ', array==>';
            $text = var_export($text, true);
        } else {
            $log .= self::$logIndex . ', str==>';
        }

        $log .= $text . PHP_EOL;
        file_put_contents($logFile, $log, FILE_APPEND);
        self::$logIndex++;
    }
}

