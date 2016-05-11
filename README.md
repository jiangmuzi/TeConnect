# TeConnect
Typecho互联插件

安装步骤
----

 1. 解压插件后放在Plugins目录下，把TeConnect插件目录下的callback.php文件拷贝到当前使用的主题跟目录下面
 2. 在后台启用插件，并配置插件参数
 3. 在当前使用的模版的适当位置添加`TeConnect_Plugin::show()`方法，
 4. 查看页面效果并测试
 

参数配置介绍
------

TeConnect暂只支持QQ及微博，并做了扩展性的兼容，所以配置是直接以文本形式填写的
在配置中一行为一个帐号系统的参数，具体为：

    type:appid,appkey,title

 - type:帐号类型如：qq 
 - appid：申请的应用id
 - appkey：申请的应用key
 - title:显示登录按钮的标题

配置示例

    qq:12345678,askdkgfksdqklnndad,腾讯QQ
    weibo:87654321,kahdkashduafodsf,微博
