CREATE TABLE IF NOT EXISTS `typecho_connect` (
  `uid` int(10) NOT NULL COMMENT '用户ID',
  `qqOpenId` char(64) NOT NULL DEFAULT '' COMMENT 'QQ登录',
  `weiboOpenId` char(64) NOT NULL DEFAULT '' COMMENT '微博登录',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=%charset%;