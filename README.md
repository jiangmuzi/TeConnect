# TeConnect
Typecho互联插件

暂只支持QQ、微博登录

启用插件后进入插件设置

    qq:appid,appkey,apptitle
    weibo:appid,appkey,apptitle
    ……

如：

    qq:12345678,askdkgfksdqklnndad,腾讯QQ

登录按钮

    <?php TeConnect_Plugin::show($format="<a href="{url}"><i class="icon-{type}"></i> {title}</a>");?>

把上述代码放在需要显示的地方即可

演示地址：http://www.ggzoo.com/login
