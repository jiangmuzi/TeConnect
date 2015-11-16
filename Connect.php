<?php
// +----------------------------------------------------------------------
// | SISOME 
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://sisome.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 绛木子 <master@lixianhua.com>
// +----------------------------------------------------------------------
class Connect{
    static public $connect = array();
    
    static public function getConnect($type){
		$options = TeConnect_Plugin::options($type);
		if(!isset(self::$connect[$type])){
			require_once 'Sdk/'.$type.'.php';
			self::$connect[$type] = new $type($options['id'],$options['key']);
		}
		return self::$connect[$type];
		
    }
    
    static public function getLoginUrl($type,$callback){
        if($type=='qq'){
            $login_url = self::getConnect($type)->login_url($callback,'get_user_info,add_share');
        }else{
            $login_url = self::getConnect($type)->login_url($callback);
        }
        return $login_url;
    }
    
    static public function getToken($type,$callback,$code){
        $rs = self::getConnect($type)->access_token($callback,$code);
        
        if(isset($rs['access_token']) && $rs['access_token']!=''){
            self::setToken($type, $rs['access_token']);
            return $rs['access_token'];
        }
        return '';
    }
    
    static public function setToken($type,$token){
        self::getConnect($type)->access_token = $token;
    }
    
    static public function getOpenId($type){
        $openid = '';
        if($type=='qq'){
            $rs = self::getConnect($type)->get_openid();
            if(isset($rs['openid']) && $rs['openid']!=''){
                $openid = $rs['openid'];
            }
        }elseif($type=='weibo'){
            $rs = self::getConnect($type)->get_uid();
            if(isset($rs['uid']) && $rs['uid']!=''){
                $openid = $rs['uid'];
            }
        }
        return $openid;
    }
}