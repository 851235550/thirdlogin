# thirdlogin
第三方登录，目前支持新浪微博,qq,微信,豆瓣.其他第三方参照此就可以

1.function xxLogin() 返回url地址，请求改地址可以拿到code，然后将code传入callback方法

2.function xxCallback() 返回userinfo
