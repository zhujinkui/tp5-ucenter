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

Class UcNote {
    var $dbconfig = '';
    var $db       = '';
    var $tablepre = '';
    var $appdir   = '';

    function _serialize($arr, $htmlon = 0) {
        if(!function_exists('xml_serialize')) {
            include_once UCENTER_ROOT_PATH .'./uc_client/lib/xml.class.php';
        }

        return xml_serialize($arr, $htmlon);
    }

    function uc_note() {
        //$this->appdir = substr(dirname(__FILE__), 0, -3);
        $this->appdir = UCENTER_ROOT_PATH;
        $this->dbconfig = $this->appdir.'./config.inc.php';
        $this->db = $GLOBALS['db'];
        $this->tablepre = $GLOBALS['tablepre'];
    }

    function test($get, $post) {
        return API_RETURN_SUCCEED;
    }

    function deleteuser($get, $post) {
        $uids = $get['ids'];
        !API_DELETEUSER && exit(API_RETURN_FORBIDDEN);

        //note 用户删除 API 接口
        $threads = array();

        $query = $this->db->query("SELECT f.fid, t.tid FROM ".$this->tablepre."threads t LEFT JOIN ".$this->tablepre."forums f ON t.fid=f.fid WHERE t.authorid IN ($uids) ORDER BY f.fid");
        while($thread = $this->db->fetch_array($query)) {
            $threads[$thread['fid']] .= ($threads[$thread['fid']] ? ',' : '').$thread['tid'];
        }

        if($threads) {
            require_once $this->appdir.'./forumdata/cache/cache_settings.php';
            foreach($threads as $fid => $tids) {
                $query = $this->db->query("SELECT attachment, thumb, remote FROM ".$this->tablepre."attachments WHERE tid IN ($tids)");
                while($attach = $this->db->fetch_array($query)) {
                    @unlink($_DCACHE['settings']['attachdir'].'/'.$attach['attachment']);
                    $attach['thumb'] && @unlink($_DCACHE['settings']['attachdir'].'/'.$attach['attachment'].'.thumb.jpg');
                }

                foreach(array('threads', 'threadsmod', 'relatedthreads', 'posts', 'polls', 'polloptions', 'trades', 'activities', 'activityapplies', 'debates', 'debateposts', 'attachments', 'favorites', 'mythreads', 'myposts', 'subscriptions', 'typeoptionvars', 'forumrecommend') as $value) {
                    $this->db->query("DELETE FROM ".$this->tablepre."$value WHERE tid IN ($tids)", 'UNBUFFERED');
                }

                require_once $this->appdir.'./include/post.func.php';
                updateforumcount($fid);
            }
            if($globalstick && $stickmodify) {
                require_once $this->appdir.'./include/cache.func.php';
                updatecache('globalstick');
            }
        }

        $query = $this->db->query("DELETE FROM ".$this->tablepre."members WHERE uid IN ($uids)");
        $this->db->query("DELETE FROM ".$this->tablepre."access WHERE uid IN ($uids)", 'UNBUFFERED');
        $this->db->query("DELETE FROM ".$this->tablepre."memberfields WHERE uid IN ($uids)", 'UNBUFFERED');
        $this->db->query("DELETE FROM ".$this->tablepre."favorites WHERE uid IN ($uids)", 'UNBUFFERED');
        $this->db->query("DELETE FROM ".$this->tablepre."moderators WHERE uid IN ($uids)", 'UNBUFFERED');
        $this->db->query("DELETE FROM ".$this->tablepre."subscriptions WHERE uid IN ($uids)", 'UNBUFFERED');

        $query = $this->db->query("SELECT uid, attachment, thumb, remote FROM ".$this->tablepre."attachments WHERE uid IN ($uids)");
        while($attach = $this->db->fetch_array($query)) {
            @unlink($_DCACHE['settings']['attachdir'].'/'.$attach['attachment']);
            $attach['thumb'] && @unlink($_DCACHE['settings']['attachdir'].'/'.$attach['attachment'].'.thumb.jpg');
        }
        $this->db->query("DELETE FROM ".$this->tablepre."attachments WHERE uid IN ($uids)");

        $this->db->query("DELETE FROM ".$this->tablepre."posts WHERE authorid IN ($uids)");
        $this->db->query("DELETE FROM ".$this->tablepre."trades WHERE sellerid IN ($uids)");

        return API_RETURN_SUCCEED;
    }

    function renameuser($get, $post) {
        $uid = $get['uid'];
        $usernameold = $get['oldusername'];
        $usernamenew = $get['newusername'];
        if(!API_RENAMEUSER) {
            return API_RETURN_FORBIDDEN;
        }

        //note 获取标签 API 接口
        require $this->dbconfig;
        require_once $this->appdir.'./include/db_'.$database.'.class.php';

        $db = new dbstuff;
        $this->db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset ? $dbcharset : $charset);
        unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

        $this->db->query("UPDATE ".$this->tablepre."announcements SET author='$usernamenew' WHERE author='$usernameold'");
        $this->db->query("UPDATE ".$this->tablepre."banned SET admin='$usernamenew' WHERE admin='$usernameold'");
        $this->db->query("UPDATE ".$this->tablepre."forums SET lastpost=REPLACE(lastpost, '\t$usernameold', '\t$usernamenew')");
        $this->db->query("UPDATE ".$this->tablepre."members SET username='$usernamenew' WHERE uid='$uid'");
        $this->db->query("UPDATE ".$this->tablepre."pms SET msgfrom='$usernamenew' WHERE msgfromid='$uid'");
        $this->db->query("UPDATE ".$this->tablepre."posts SET author='$usernamenew' WHERE authorid='$uid'");
        $this->db->query("UPDATE ".$this->tablepre."threads SET author='$usernamenew' WHERE authorid='$uid'");
        $this->db->query("UPDATE ".$this->tablepre."threads SET lastposter='$usernamenew' WHERE lastposter='$usernameold'");
        $this->db->query("UPDATE ".$this->tablepre."threadsmod SET username='$usernamenew' WHERE uid='$uid'");
        return API_RETURN_SUCCEED;
    }

    function gettag($get, $post) {
        $name = $get['id'];
        if(!API_GETTAG) {
            return API_RETURN_FORBIDDEN;
        }

        //note 获取标签 API 接口
        require $this->dbconfig;
        require_once $this->appdir.'./include/db_'.$database.'.class.php';

        $db = new dbstuff;
        $this->db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset ? $dbcharset : $charset);
        unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

        $name = trim($name);
        if(empty($name) || !preg_match('/^([\x7f-\xff_-]|\w|\s)+$/', $name) || strlen($name) > 20) {
            return API_RETURN_FAILED;
        }

        require_once $this->appdir.'./include/misc.func.php';

        $tag = $this->db->fetch_first("SELECT * FROM ".$this->tablepre."tags WHERE tagname='$name'");
        if($tag['closed']) {
            return API_RETURN_FAILED;
        }

        $tpp = 10;
        $PHP_SELF = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $boardurl = 'http://'.$_SERVER['HTTP_HOST'].preg_replace("/\/+(api)?\/*$/i", '', substr($PHP_SELF, 0, strrpos($PHP_SELF, '/'))).'/';
        $query = $this->db->query("SELECT t.* FROM ".$this->tablepre."threadtags tt LEFT JOIN ".$this->tablepre."threads t ON t.tid=tt.tid AND t.displayorder>='0' WHERE tt.tagname='$name' ORDER BY tt.tid DESC LIMIT $tpp");
        $threadlist = array();
        while($tagthread = $this->db->fetch_array($query)) {
            if($tagthread['tid']) {
                $threadlist[] = array(
                    'subject' => $tagthread['subject'],
                    'uid' => $tagthread['authorid'],
                    'username' => $tagthread['author'],
                    'dateline' => $tagthread['dateline'],
                    'url' => $boardurl.'viewthread.php?tid='.$tagthread['tid'],
                );
            }
        }

        $return = array($name, $threadlist);
        return $this->_serialize($return, 1);
    }

    function synlogin($get, $post) {
        $uid = $get['uid'];
        $username = $get['username'];
        if(!API_SYNLOGIN) {
            return API_RETURN_FORBIDDEN;
        }

        //note 同步登录 API 接口
        require $this->dbconfig;
        require_once $this->appdir.'./include/db_'.$database.'.class.php';
        require_once $this->appdir.'./forumdata/cache/cache_settings.php';

        $db = new dbstuff;
        $this->db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset ? $dbcharset : $charset);
        unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);
        $cookietime = 2592000;
        $discuz_auth_key = md5($_DCACHE['settings']['authkey'].$_SERVER['HTTP_USER_AGENT']);
        header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
        $uid = intval($uid);
        $query = $this->db->query("SELECT username, uid, password, secques FROM ".$this->tablepre."members WHERE uid='$uid'");
        if($member = $this->db->fetch_array($query)) {
            _setcookie('sid', '', -86400 * 365);
            _setcookie('cookietime', $cookietime, 31536000);
            _setcookie('auth', _authcode("$member[password]\t$member[secques]\t$member[uid]", 'ENCODE', $discuz_auth_key), $cookietime);
        } else {
            _setcookie('cookietime', $cookietime, 31536000);
            _setcookie('loginuser', $username, $cookietime);
            _setcookie('activationauth', _authcode($username, 'ENCODE', $discuz_auth_key), $cookietime);
        }
    }

    function synlogout($get, $post) {
        if(!API_SYNLOGOUT) {
            return API_RETURN_FORBIDDEN;
        }

        //note 同步登出 API 接口
        header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
        _setcookie('auth', '', -86400 * 365);
        _setcookie('sid', '', -86400 * 365);
        _setcookie('loginuser', '', -86400 * 365);
        _setcookie('activationauth', '', -86400 * 365);
    }

    function updatepw($get, $post) {
        if(!API_UPDATEPW) {
            return API_RETURN_FORBIDDEN;
        }
        $username = $get['username'];
        $password = $get['password'];
        require $this->dbconfig;
        require_once $this->appdir.'./include/db_'.$database.'.class.php';
        $db = new dbstuff;
        $this->db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset ? $dbcharset : $charset);
        unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

        $newpw = md5(time().rand(100000, 999999));
        $this->db->query("UPDATE ".$this->tablepre."members SET password='$newpw' WHERE username='$username'");
        return API_RETURN_SUCCEED;
    }

    function updatebadwords($get, $post) {
        if(!API_UPDATEBADWORDS) {
            return API_RETURN_FORBIDDEN;
        }
        $cachefile = $this->appdir.'./uc_client/data/cache/badwords.php';
        $fp = fopen($cachefile, 'w');
        $data = array();
        if(is_array($post)) {
            foreach($post as $k => $v) {
                $data['findpattern'][$k] = $v['findpattern'];
                $data['replace'][$k] = $v['replacement'];
            }
        }
        $s = "<?php\r\n";
        $s .= '$_CACHE[\'badwords\'] = '.var_export($data, TRUE).";\r\n";
        fwrite($fp, $s);
        fclose($fp);
        return API_RETURN_SUCCEED;
    }

    function updatehosts($get, $post) {
        if(!API_UPDATEHOSTS) {
            return API_RETURN_FORBIDDEN;
        }
        $cachefile = $this->appdir.'./uc_client/data/cache/hosts.php';
        $fp = fopen($cachefile, 'w');
        $s = "<?php\r\n";
        $s .= '$_CACHE[\'hosts\'] = '.var_export($post, TRUE).";\r\n";
        fwrite($fp, $s);
        fclose($fp);
        return API_RETURN_SUCCEED;
    }

    function updateapps($get, $post) {
        if(!API_UPDATEAPPS) {
            return API_RETURN_FORBIDDEN;
        }
        $UC_API = $post['UC_API'];

        //note 写 app 缓存文件
        $cachefile = $this->appdir.'./uc_client/data/cache/apps.php';
        $fp = fopen($cachefile, 'w');
        $s = "<?php\r\n";
        $s .= '$_CACHE[\'apps\'] = '.var_export($post, TRUE).";\r\n";
        fwrite($fp, $s);
        fclose($fp);

        //note 写配置文件
        if(is_writeable($this->appdir.'./config.inc.php')) {
            $configfile = trim(file_get_contents($this->appdir.'./config.inc.php'));
            $configfile = substr($configfile, -2) == '?>' ? substr($configfile, 0, -2) : $configfile;
            $configfile = preg_replace("/define\('UC_API',\s*'.*?'\);/i", "define('UC_API', '$UC_API');", $configfile);
            if($fp = @fopen($this->appdir.'./config.inc.php', 'w')) {
                @fwrite($fp, trim($configfile));
                @fclose($fp);
            }
        }

        global $_DCACHE;
        require_once $this->appdir.'./forumdata/cache/cache_settings.php';
        require_once $this->appdir.'./include/cache.func.php';
        foreach($post as $appid => $app) {
            $_DCACHE['settings']['ucapp'][$appid]['viewprourl'] = $app['url'].$app['viewprourl'];
        }
        updatesettings();

        return API_RETURN_SUCCEED;
    }

    function updateclient($get, $post) {
        if(!API_UPDATECLIENT) {
            return API_RETURN_FORBIDDEN;
        }
        $cachefile = $this->appdir.'./uc_client/data/cache/settings.php';
        $fp = fopen($cachefile, 'w');
        $s = "<?php\r\n";
        $s .= '$_CACHE[\'settings\'] = '.var_export($post, TRUE).";\r\n";
        fwrite($fp, $s);
        fclose($fp);
        return API_RETURN_SUCCEED;
    }

    function updatecredit($get, $post) {
        if(!API_UPDATECREDIT) {
            return API_RETURN_FORBIDDEN;
        }
        $credit = $get['credit'];
        $amount = $get['amount'];
        $uid = $get['uid'];
        require $this->dbconfig;
        require_once $this->appdir.'./include/db_'.$database.'.class.php';
        require_once $this->appdir.'./forumdata/cache/cache_settings.php';

        $db = new dbstuff;
        $this->db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset ? $dbcharset : $charset);
        unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

        $this->db->query("UPDATE ".$this->tablepre."members SET extcredits$credit=extcredits$credit+'$amount' WHERE uid='$uid'");

        $discuz_user = $this->db->result_first("SELECT username FROM ".$this->tablepre."members WHERE uid='$uid'");

        $this->db->query("INSERT INTO ".$this->tablepre."creditslog (uid, fromto, sendcredits, receivecredits, send, receive, dateline, operation)
                VALUES ('$uid', '$discuz_user', '0', '$credit', '0', '$amount', '$timestamp', 'EXC')");
        return API_RETURN_SUCCEED;
    }

    function getcredit($get, $post) {
        if(!API_GETCREDIT) {
            return API_RETURN_FORBIDDEN;
        }
        require $this->dbconfig;
        require_once $this->appdir.'./include/db_'.$database.'.class.php';

        $db = new dbstuff;
        $this->db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset ? $dbcharset : $charset);
        unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

        $uid = intval($get['uid']);
        $credit = intval($get['credit']);
        echo $credit >= 1 && $credit <= 8 ? $this->db->result_first("SELECT extcredits$credit FROM ".$this->tablepre."members WHERE uid='$uid'") : 0;
    }

    function getcreditsettings($get, $post) {
        if(!API_GETCREDITSETTINGS) {
            return API_RETURN_FORBIDDEN;
        }
        require_once $this->appdir.'./forumdata/cache/cache_settings.php';
        $credits = array();
        foreach($_DCACHE['settings']['extcredits'] as $id => $extcredits) {
            $credits[$id] = array(strip_tags($extcredits['title']), $extcredits['unit']);
        }
        return $this->_serialize($credits);
    }

    function updatecreditsettings($get, $post) {
        if(!API_UPDATECREDITSETTINGS) {
            return API_RETURN_FORBIDDEN;
        }
        $credit = $get['credit'];
        require $this->dbconfig;
        $outextcredits = array();
        if($credit) {
            foreach($credit as $appid => $credititems) {
                if($appid == UC_APPID) {
                    foreach($credititems as $value) {
                        $outextcredits[] = array(
                            'appiddesc' => $value['appiddesc'],
                            'creditdesc' => $value['creditdesc'],
                            'creditsrc' => $value['creditsrc'],
                            'title' => $value['title'],
                            'unit' => $value['unit'],
                            'ratiosrc' => $value['ratiosrc'],
                            'ratiodesc' => $value['ratiodesc'],
                            'ratio' => $value['ratio']
                        );
                    }
                }
            }
        }

        global $_DCACHE;
        require_once $this->appdir.'./include/db_'.$database.'.class.php';
        require_once $this->appdir.'./forumdata/cache/cache_settings.php';
        require_once $this->appdir.'./include/cache.func.php';

        $db = new dbstuff;
        $this->db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset ? $dbcharset : $charset);
        unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

        $this->db->query("REPLACE INTO ".$this->tablepre."settings (variable, value) VALUES ('outextcredits', '".addslashes(serialize($outextcredits))."');", 'UNBUFFERED');

        $tmp = array();
        foreach($outextcredits as $value) {
            $key = $value['appiddesc'].'|'.$value['creditdesc'];
            if(!isset($tmp[$key])) {
                $tmp[$key] = array('title' => $value['title'], 'unit' => $value['unit']);
            }
            $tmp[$key]['ratiosrc'][$value['creditsrc']] = $value['ratiosrc'];
            $tmp[$key]['ratiodesc'][$value['creditsrc']] = $value['ratiodesc'];
            $tmp[$key]['creditsrc'][$value['creditsrc']] = $value['ratio'];
        }

        $_DCACHE['settings']['outextcredits'] = $tmp;

        updatesettings();

        return API_RETURN_SUCCEED;
    }
}