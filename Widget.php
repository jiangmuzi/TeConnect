<?php
// +----------------------------------------------------------------------
// | SISOME 
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://sisome.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 绛木子 <master@lixianhua.com>
// +----------------------------------------------------------------------

class TeConnect_Widget extends Widget_Abstract_Users{

    private $auth;
    
	/**
     * 风格目录
     *
     * @access private
     * @var string
     */
    private $_themeDir;
    
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->_themeDir = rtrim($this->options->themeFile($this->options->theme), '/') . '/';
		
		/** 初始化皮肤函数 */
        $functionsFile = $this->_themeDir . 'functions.php';
        if (file_exists($functionsFile)) {
            require_once $functionsFile;
            if (function_exists('themeInit')) {
                themeInit($this);
            }
        }
    }
	
    public function oauth(){
        $type = $this->request->get('type');
        if(is_null($type)){
            $this->widget('Widget_Notice')->set(array('请选择登录方式!'),'error');
            $this->response->goBack();
        }
        $options = TeConnect_Plugin::options();
        $tyle = strtolower($type);
        //不在开启的登陆方式内直接返回
        if(!isset($options[$type])){
            $this->widget('Widget_Notice')->set(array('暂不支持该登录方式!'),'error');
            $this->response->goBack();
        }
        $callback_url = Typecho_Common::url('/oauth_callback?type='.$type, $this->options->index);
        
        require_once 'Connect.php';
        $this->response->redirect(Connect::getLoginUrl($type, $callback_url));
    }
    
    public function callback(){
		if(!isset($_SESSION)){
			session_start();
			if(isset($_SESSION['__typecho_auth']))
				$this->auth = $_SESSION['__typecho_auth'];
		}

		if($this->request->isPost()){
			$do = $this->request->get('do');
			if(!in_array($do,array('bind','reg'))){
				$this->widget('Widget_Notice')->set(array('错误数据!'),'error');
				$this->response->goBack();
			}

			if(!isset($this->auth['openid']) || !isset($this->auth['type'])){
				$this->response->redirect($this->options->index);
			}
			$func = 'doCallback'.ucfirst($do);
			$this->$func();
			unset($_SESSION['__typecho_auth']);
			$this->response->redirect($this->options->index);
		}
	
		$options = TeConnect_Plugin::options();
		
		if(empty($this->auth)){
			
			$this->auth['type'] = $this->request->get('type','');
			$this->auth['code'] = $this->request->get('code','');
			//不在开启的登陆方式内直接返回
			if(!isset($options[$this->auth['type']])){
				$this->response->redirect($this->options->index);
			}
			if(empty($this->auth['code'])){
				$this->response->redirect($this->options->index);
			}
			
			$callback_url = Typecho_Common::url('/oauth_callback?type='.$this->auth['type'], $this->options->index);
			
			$this->auth['openid'] = '';
			
			require_once 'Connect.php';
			//换取access_token
			$this->auth['token'] = Connect::getToken($this->auth['type'], $callback_url, $this->auth['code']);

			if(empty($this->auth['token'])){
				$this->response->redirect($this->options->index);
			}
			
			//获取openid
			$this->auth['openid'] = Connect::getOpenId($this->auth['type']);
			
			if(empty($this->auth['openid'])){
				$this->response->redirect($this->options->index);
			}
		}

		//已经登录，重新绑定
		if ($this->user->hasLogin()) {
            /** 绑定用户 */
            $this->bindUser($this->user->uid,$this->auth['openid'],$this->auth['type']);
            //提示绑定成功，并跳转
            // add 跳转提示
            $this->widget('Widget_Notice')->set(array('成功绑定账号!'));
            $this->response->redirect($this->options->index);
        }
		//已经绑定，直接登录
		$isConnect = $this->findConnectUser($this->auth['openid'],$this->auth['type']);
		
		if($isConnect){
			$this->useUidLogin($isConnect['uid']);
			// add 跳转提示
			$this->widget('Widget_Notice')->set(array('已成功登陆!'));
			$this->response->redirect($this->options->index);
		}
		
		if(!isset($_SESSION['__typecho_auth']))
			$_SESSION['__typecho_auth'] = $this->auth;
		
		//未绑定，显示界面
		$this->render('callback.php');
	}
    //绑定已有用户
	protected function doCallbackBind(){
		$name = $this->request->get('name');
		$password = $this->request->get('password');
		
		if(empty($name) || empty($password)){
			$this->widget('Widget_Notice')->set(array('帐号或密码不能为空!'),'error');
			$this->response->goBack();
		}
		$isLogin = $this->user->login($name,$password);
		if($isLogin){
			$this->widget('Widget_Notice')->set(array('已成功绑定并登陆!'));
			$this->bindUser($this->user->uid,$this->auth['openid'],$this->auth['type']);
		}else{
			$this->widget('Widget_Notice')->set(array('帐号或密码错误!'),'error');
			$this->response->goBack();
		}
	}
	//注册新用户
	protected function doCallbackReg(){
		$url = $this->request->get('url');
		
		$validator = new Typecho_Validate();
		$validator->addRule('mail', 'required', _t('必须填写电子邮箱'));
        $validator->addRule('mail', array($this, 'mailExists'), _t('电子邮箱地址已经存在'));
        $validator->addRule('mail', 'email', _t('电子邮箱格式错误'));
        $validator->addRule('mail', 'maxLength', _t('电子邮箱最多包含200个字符'), 200);
		
		$validator->addRule('screenName', 'required', _t('必须填写昵称'));
		$validator->addRule('screenName', 'xssCheck', _t('请不要在昵称中使用特殊字符'));
		$validator->addRule('screenName', array($this, 'screenNameExists'), _t('昵称已经存在'));
		
		if($url){
			$validator->addRule('url', 'url', _t('个人主页地址格式错误'));
		}
		
		/** 截获验证异常 */
        if ($error = $validator->run($this->request->from('mail', 'screenName', 'url'))) {
            /** 设置提示信息 */
            $this->widget('Widget_Notice')->set($error);
            $this->response->goBack();
        }
		
		$dataStruct = array(
            'mail'      =>  $this->request->mail,
            'screenName'=>  $this->request->screenName,
            'created'   =>  $this->options->gmtTime,
            'group'     =>  'subscriber'
        );
		
		$insertId = $this->insert($dataStruct);
		if($insertId){
			$this->bindUser($insertId,$this->auth['openid'],$this->auth['type']);
			$this->useUidLogin($insertId);
			$this->widget('Widget_Notice')->set(array('已成功注册并登陆!'));
		}
	}

    //绑定用户
    protected function bindUser($uid,$openid,$type){
		
		$connect = $this->db->fetchRow($this->db->select()
            ->from('table.connect')
            ->where('uid = ?', $uid)
            ->limit(1));
		if(empty($connect)){
			$this->db->query($this->db->insert('table.connect')->rows(array(
				'uid'=>$uid,
				$type.'OpenId' => $openid
			)));   
		}else{
			$this->db->query($this->db
            ->update('table.connect')
            ->rows(array($type.'OpenId' => $openid))
            ->where('uid = ?', $uid));
		}
		     
    }
    //查找已绑定用户
    protected function findConnectUser($openid,$type){
        if(empty($openid)) return 0;
        $user = $this->db->fetchRow($this->db->select()
            ->from('table.connect')
            ->where($type.'OpenId = ?', $openid)
            ->limit(1));
    
        return empty($user)? 0 : $user;
    }
    //使用用户uid登录
    protected function useUidLogin($uid,$expire = 0){
        $authCode = function_exists('openssl_random_pseudo_bytes') ?
        bin2hex(openssl_random_pseudo_bytes(16)) : sha1(Typecho_Common::randString(20));
        $user = array('uid'=>$uid,'authCode'=>$authCode);
    
        Typecho_Cookie::set('__typecho_uid', $uid, $expire);
        Typecho_Cookie::set('__typecho_authCode', Typecho_Common::hash($authCode), $expire);
    
        //更新最后登录时间以及验证码
        $this->db->query($this->db
            ->update('table.users')
            ->expression('logged', 'activated')
            ->rows(array('authCode' => $authCode))
            ->where('uid = ?', $uid));
    }
    
    public function render($themeFile){
        /** 文件不存在 */
        if (!file_exists($this->_themeDir . $themeFile)) {
            Typecho_Common::error(500);
        }
        /** 输出模板 */
        require_once $this->_themeDir . $themeFile;
    }
    /**
     * 获取主题文件
     *
     * @access public
     * @param string $fileName 主题文件
     * @return void
     */
    public function need($fileName){
        require $this->_themeDir . $fileName;
    }
}