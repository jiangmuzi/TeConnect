<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho互联；暂只支持QQ、微博
 * 
 * @package TeConnect 
 * @author 绛木子
 * @version 1.0
 * @link http://lixianhua.com
 * 
 * SDK使用了 http://git.oschina.net/piscdong 发布的sdk
 */
class TeConnect_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        $info = self::installDb();
		
		//SNS帐号登录
        Helper::addRoute('oauth', '/oauth','TeConnect_Widget','oauth');
        Helper::addRoute('oauth_callback', '/oauth_callback','TeConnect_Widget','callback');
		
		return _t($info);
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
		Helper::removeRoute('oauth');
		Helper::removeRoute('oauth_callback');
	}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** 互联配置 */
        $connect = new Typecho_Widget_Helper_Form_Element_Textarea('connect', NULL, NULL, _t('互联配置'), _t('一行一个配置，格式为：‘type:appid,appkey,title’，如：‘qq:12345678,asdiladaldns,腾讯QQ’'));
		$form->addInput($connect);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
	
    /**
	 * 安装数据库
	 */
	public static function installDb(){
		$db = Typecho_Db::get();
		$type = explode('_', $db->getAdapterName());
		$type = array_pop($type);
		$prefix = $db->getPrefix();
		$scripts = file_get_contents('usr/plugins/TeConnect/'.$type.'.sql');
		$scripts = str_replace('typecho_', $prefix, $scripts);
		$scripts = str_replace('%charset%', 'utf8', $scripts);
		$scripts = explode(';', $scripts);
		try {
			foreach ($scripts as $script) {
				$script = trim($script);
				if ($script) {
					$db->query($script, Typecho_Db::WRITE);
				}
			}
			return '建立Typecho互联数据表，插件启用成功';
		} catch (Typecho_Db_Exception $e) {
			$code = $e->getCode();
			if(('Mysql' == $type && 1050 == $code) ||
					('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
				try {
					$script = 'SELECT `uid` from `' . $prefix . 'connect`';
					$db->query($script, Typecho_Db::READ);
					return '检测到Typecho互联数据表，Typecho互联插件启用成功';					
				} catch (Typecho_Db_Exception $e) {
					$code = $e->getCode();
					if(('Mysql' == $type && 1054 == $code) ||
							('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
						return self::updateDb($db, $type, $prefix);
					}
					throw new Typecho_Plugin_Exception('数据表检测失败，Typecho互联插件启用失败。错误号：'.$code);
				}
			} else {
				throw new Typecho_Plugin_Exception('数据表建立失败，Typecho互联插件启用失败。错误号：'.$code);
			}
		}
	}
	
	public static function show($format='<a href="{url}"><i class="fa fa-{type}"></i> {title}</a>'){
		$list = self::options();
		if(empty($list)) return '';
		$html = '';
		foreach($list as $type=>$v){
			$url = Typecho_Common::url('/oauth?type='.$type,Typecho_Widget::Widget('Widget_Options')->index);
			$html .= str_replace(
					array('{type}','{title}','{url}'),
					array($type,$v['title'],$url),$format);
		}
		echo $html;
	}
	
	public static function options($type=''){
		static $options = array();
		if(empty($options)){
			$connect = Typecho_Widget::Widget('Widget_Options')->plugin('TeConnect')->connect;
			$connect = preg_split('/[;\r\n]+/', trim($connect, ",;\r\n"));
			foreach($connect as $v){
				$v = explode(':',$v);
				if(isset($v[1])){
					$tmp = explode(',',$v[1]);
				}
				if(isset($tmp[1])){
					$options[$v[0]] = array(
						'id'=>trim($tmp[0]),
						'key'=>trim($tmp[1]),
						'title'=>isset($tmp[2]) ? $tmp[2] : $v[0]
						);
				}
			}
		}
		return empty($type) ? $options : (isset($options[$type]) ? $options[$type] : array());
	}
}
