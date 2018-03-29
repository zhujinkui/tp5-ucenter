<?php
// 函数工具类库
// +----------------------------------------------------------------------
// | PHP version 5.4+
// +----------------------------------------------------------------------
// | Copyright (c) 2012-2014 http://www.17php.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhujinkui <developer@zhujinkui.com>
// +----------------------------------------------------------------------

namespace think\common;

Class Tools {
    public static $mail_suffix = array('@hotmail.com',
        '@msn.com',
        '@yahoo.com',
        '@gmail.com',
        '@aim.com',
        '@aol.com',
        '@mail.com',
        '@walla.com',
        '@inbox.com',
        '@126.com',
        '@163.com',
        '@sina.com',
        '@21cn.com',
        '@sohu.com',
        '@yahoo.com.cn',
        '@tom.com',
        '@qq.com',
        '@etang.com',
        '@eyou.com',
        '@56.com',
        '@x.cn',
        '@chinaren.com',
        '@sogou.com',
        '@citiz.com',
    );

    /**
     * 获取邮箱登陆URL
     * @param type $email
     * @return type
     */
    public static function getMailLoginUrl($email) {
        $domain = strstr($email, '@');
        $url = "http://www.baidu.com/s?wd={$domain}+邮箱登陆&ie=utf-8";
        if (in_array($domain, self::$mail_suffix)) {
            $domain = 'http://' . $domain;
            $url = str_replace("@", 'mail.', $domain);
        }
        return $url;
    }

    /**
     * 获取随机字符串
     * @param type $count
     * @param type $str
     * @return type
     */
    public static function getRandomChar($count, $str = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ") {
        $key = "";
        $len = strlen($str) - 1;
        for ($i = 0; $i < $count; $i++) {
            $key .= $str{mt_rand(0, $len)};    //生成php随机数
        }
        return $key;
    }

    /**
     * 获取16位唯一随机数字串
     * @return type
     */
    public static function getUniqueId() {
        $mtime = microtime();
        $key = __FUNCTION__ . time();
        $aExistData = S($key);
        if (empty($aExistData)) {
            $aExistData = array();
        }
        preg_match('/0\.(\d{4})\d{4}\s(\d{10})/', $mtime, $match);
        $sUniqueId = $match[2] . $match[1] . mt_rand(10, 99);
        if (in_array($sUniqueId, $aExistData)) {
            $sUniqueId = self::getUniqueId();
        } else {
            $aExistData[] = $sUniqueId;
            S($key, $aExistData, 1);
        }
        return $sUniqueId;
    }

    /**
     * 根据IP地址获取当前城市详情
     * @param type $ip
     * @return type
     */
    public static function getIpCity($ip = '') {
        if ($ip == '')
            $ip = Net_Net_GetIp();
        $ip_url = "http://ip.taobao.com/service/getIpInfo.php?ip=" . $ip;
        $message = file_get_contents($ip_url);
        $data = json_decode($message, true);
        return $data;
    }

    /**
     * 获取当前IP地址
     * @return string
     */
    public static function getIp() {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $cip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (!empty($_SERVER["REMOTE_ADDR"])) {
            $cip = $_SERVER["REMOTE_ADDR"];
        } else {
            $cip = "";
        }
        return $cip;
    }

    /**
     * 根据IP地址获取当前城市
     * @return boolean
     */
    public static function getCityByIp() {
        $ip = get_client_ip();
        $ip = $ip == "127.0.0.1" ? "122.226.178.42" : $ip;
        //122.226.178.42 60.194.13.0
        $city_info = PublicTool::getIpCity($ip);
        //未匹配ip
        $data = array();
        if ($city_info['data']['country'] == "未分配或者内网IP") {
            return false;
        }
        //北京市 省级市
        $data['region'] = $city_info['data']['region'];
        $data['city'] = $city_info['data']['city'];
        return $data;
    }

    /**
     * 删除文件
     * @param type $filePath
     */
    public static function delFile($filePath) {
        if (trim($filePath) != "") {
            unlink($filePath);
        }
    }

    /**
     * 删除文件夹
     * @param type $dir
     * @return boolean
     */
    public static function deldir($dir) {
		//先删除目录下的文件：
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    self::deldir($fullpath);
                }
            }
        }

        closedir($dh);
		//删除当前文件夹：
        if (rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 创建文件夹
     * @param type $dir
     * @return boolean
     */
    public static function mkdirs($dir) {
        if (!is_dir($dir)) {
            if (!self::mkdirs(dirname($dir))) {
                return false;
            }
            if (!mkdir($dir, 0777)) {
                return false;
            }
        }
        return true;
    }
}
