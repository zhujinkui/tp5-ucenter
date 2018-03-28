<?php
// Ucenter类库
// +----------------------------------------------------------------------
// | PHP version 5.4+
// +----------------------------------------------------------------------
// | Copyright (c) 2012-2014 http://www.17php.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhujinkui <developer@zhujinkui.com>
// +----------------------------------------------------------------------

namespace think;
use think\common\Tools;
use think\Config;

class Ucenter {
    /**
     * [back 事件接收 UCenter 相关信息更新，接收同步操作]
     */
    public static function callBack() {
        define('IN_DISCUZ', TRUE);

        define('UC_CLIENT_VERSION', '1.5.0');   //note UCenter 版本标识
        define('UC_CLIENT_RELEASE', '20081031');

        define('API_DELETEUSER', 1);        //note 用户删除 API 接口开关
        define('API_RENAMEUSER', 1);        //note 用户改名 API 接口开关
        define('API_GETTAG', 1);        //note 获取标签 API 接口开关
        define('API_SYNLOGIN', 1);      //note 同步登录 API 接口开关
        define('API_SYNLOGOUT', 1);     //note 同步登出 API 接口开关
        define('API_UPDATEPW', 1);      //note 更改用户密码 开关
        define('API_UPDATEBADWORDS', 1);    //note 更新关键字列表 开关
        define('API_UPDATEHOSTS', 1);       //note 更新域名解析缓存 开关
        define('API_UPDATEAPPS', 1);        //note 更新应用列表 开关
        define('API_UPDATECLIENT', 1);      //note 更新客户端缓存 开关
        define('API_UPDATECREDIT', 1);      //note 更新用户积分 开关
        define('API_GETCREDITSETTINGS', 1); //note 向 UCenter 提供积分设置 开关
        define('API_GETCREDIT', 1);     //note 获取用户的某项积分 开关
        define('API_UPDATECREDITSETTINGS', 1);  //note 更新应用积分设置 开关

        define('API_RETURN_SUCCEED', '1');
        define('API_RETURN_FAILED', '-1');
        define('API_RETURN_FORBIDDEN', '-2');

        //define('DISCUZ_ROOT', '../');

        //note 普通的 http 通知方式
        if(!defined('IN_UC')) {
            error_reporting(0);
            //set_magic_quotes_runtime(0);

            defined('MAGIC_QUOTES_GPC') || define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());

            $_DCACHE = $get = $post = array();

            $code = @$_GET['code'];
            parse_str(Tools::_authcode($code, 'DECODE', UC_KEY), $get);
            if(MAGIC_QUOTES_GPC) {
                $get = Tools::_stripslashes($get);
            }

            $timestamp = time();
            if($timestamp - $get['time'] > 3600) {
                exit('Authracation has expiried');
            }
            if(empty($get)) {
                exit('Invalid Request');
            }
            $action = $get['action'];

            require_once UCENTER_ROOT_PATH .'./uc_client/lib/xml.class.php';

            $post = xml_unserialize(file_get_contents('php://input'));

            if(in_array($get['action'], array('test', 'deleteuser', 'renameuser', 'gettag', 'synlogin', 'synlogout', 'updatepw', 'updatebadwords', 'updatehosts', 'updateapps', 'updateclient', 'updatecredit', 'getcreditsettings', 'updatecreditsettings'))) {
                require_once UCENTER_ROOT_PATH .'./include/db_mysql.class.php';
                $GLOBALS['db'] = new dbstuff;
                $GLOBALS['db']->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
                $GLOBALS['tablepre'] = $tablepre;
                unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);
                $uc_note = new uc_note();
                exit($uc_note->$get['action']($get, $post));
            } else {
                exit(API_RETURN_FAILED);
            }
        //note include 通知方式
        } else {
            require_once UCENTER_ROOT_PATH .'./config.inc.php';
            require_once UCENTER_ROOT_PATH .'./include/db_mysql.class.php';
            $GLOBALS['db'] = new dbstuff;
            $GLOBALS['db']->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
            $GLOBALS['tablepre'] = $tablepre;
            unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);
        }
    }

    public static function test()
    {
        return UCENTER_ROOT_PATH .'./config.inc.php';
    }
}