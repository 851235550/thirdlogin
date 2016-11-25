<?php

/**
 *   Author        ：wangyulu
 *   Date          ：2016/11/25 17:11:18
 *   Version       ：1.0
 *   Copyright (C) pianke 2016 All rights reserved.
 *
 *   FileName      ：ThirdLogin.php
 */
require_once(dirname(__FILE__).'/sdk/sina/saetv2.ex.class.php');
require_once(dirname(__FILE__).'/sdk/qq/qqAuth.class.php');
require_once(dirname(__FILE__).'/sdk/douban/DoubanOAuth.php');
class UserOauth
{
       	const COLLNAME = 'useroauth'; //数据集名
		
		/********各平台的AppSecret和AppID********/
       	const weiboAKey = '';//sina
       	const weiboSKey = '';

       	const qqAKey = '';//qq
       	const qqSKey = '';

       	const wxAKey = '';//wx
       	const wxSKey = '';

       	const doubanAKey = '';//douban
       	const doubanSKey = '';
		/**************************************************/
		
		/******************回调地址***********************/
       	const defaulturl = '';


       	/**
       	 * sina
       	 */
       	public static function sinaLogin($url='')
       	{
       		$weiboAKey = self::weiboAKey;
       		$weiboSKey = self::weiboSKey;
       		if(empty($url)){
       			$url = self::defaulturl;
       		}
       		$obj = new SaeTOAuthV2($weiboAKey,$weiboSKey);

       		$sinaUrl = $obj->getAuthorizeURL($url);

       		return $sinaUrl;
       	}

       	public static function sinaCallback($code,$callback='')
       	{
       		$weiboAKey = self::weiboAKey;
       		$weiboSKey = self::weiboSKey;
       		if(empty($callback)){
       			$callback = self::defaulturl;
       		}

       		try{
       			$obj = new SaeTOAuthV2($weiboAKey,$weiboSKey);
       			$keys = array(
       				'code'=>$code,
       				'redirect_uri'=>$callback,
       			);

       			$token = $obj->getAccessToken($type='code',$keys);

       		}catch(Exception $e){

       		}

       		if(isset($token['access_token']) && !isset($token['error'])){
       			$weibo = new SaeTClientV2($weiboAKey,$weiboSKey,$token['access_token']);
       			$userInfo = $weibo->show_user_by_id($token['uid']);
       			if(empty($userInfo) || isset($userInfo['error'])){
       				return false;
       			}
       			$token['expires_time'] = time() + intval($token['expires_in']) - 100;
       			$ret = array(
       				'ouid'=>$userInfo['idstr'],
       				'source'=>1,//sina
       				'uname'=>$userInfo['screen_name'],
       				'gender'=>$userInfo['gender'] == 'f' ? 2 : 1,
       				'desc'=>$userInfo['description'],
       				'icon' =>$userInfo['profile_image_url'],
       				'accessToken'=>$token,
       			);
       			return $ret;
       		}

       		return false;
       	}


       	/**
       	 * QQ
       	 */
       	public static function qqLogin($url='')
       	{
       		$akey = self::qqAKey;
       		$skey = self::qqSKey;
       		$qqsdk = new qqAuth($akey,$skey);
       		if(empty($url)){
       			$url = self::defaulturl;
       		}
       		$qqurl = $qqsdk->login($url);
       		return $qqurl;
       	}

       	public static function qqCallback($code,$url='')
       	{
       		$akey = self::qqAKey;
       		$skey = self::qqSKey;
       		if(empty($url)){
       			$url = self::defaulturl;
       		}
       		$qqsdk = new qqAuth($akey,$skey);
       		$_REQUEST['code'] = $code;
       		$res = $qqsdk->callback($url);
        if(isset($res['access_token'])){
            $open = $qqsdk->get_openid($res['access_token']);
            $userInfo = $qqsdk->get_user_info($open->openid,$res['access_token']);
            $res['open_id'] = $open->openid;
       			$res['expires_time'] = time() + intval($token['expires_in']) - 100;

       			$ret = array(
       				'ouid'=>$open->openid,
       				'source'=>2,//weixin
       				'uname'=>$userInfo['nickname'],
       				'gender'=>$userInfo['gender'] == '男' ? 2 : 1,
       				'desc'=>$userInfo['msg'],
       				'icon' =>$userInfo['figureurl_qq_1'],
       				'accessToken'=>$res,
       			);
       			return $ret;
       		}
       		return false;
       	}


       	/**
       	 * wx 登录
       	 */
       	public static function wxLogin($url='')
       	{
       		if(empty($url)){
       			$url = self::defaulturl;
       		}
       		$scope = 'snsapi_login';
       		$wxurl = 'http://open.weixin.qq.com/connect/qrconnect?appid='.self::wxAKey.'&redirect_uri='.urlencode($url).'&response_type=code&scope='.$scope.'#wechat_redirect';
       		return $wxurl;
       	}

       	public static function wxCallback($code,$url='')
       	{
       		$ak = self::wxAKey;
       		$sk = self::wxSKey;

       		$requestUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$ak.'&secret='.$sk.'&code='.$code.'&grant_type=authorization_code';
       		$token = self::httpRequest($requestUrl);
       		$res = json_decode($token, true);

       		$userInfo  = self::getWxUserInfo($res['access_token'],$res['openid']);
       		if(!empty($userInfo['openid'])){
       			$ret = array(
       				'ouid'=>$userInfo['openid'],
       				'source'=>5,//wx
       				'uname'=>$userInfo['nickname'],
       				'gender'=>$userInfo['sex'] == 1 ? 2 : 1,
       				'desc'=>'',
       				'icon' =>$userInfo['headimgurl'],
       				'accessToken'=>$res['access_token'],
       			);
       			return $ret;
       		}
        return false;
       	}

       	/**
       	 * 微信获取个人信息
       	 */
       	private static function getWxUserInfo($accessToken,$openId)
       	{
       		if(empty($accessToken) || empty($openId)){
       			return false;
       		}
       		$requestUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$accessToken.'&openid='.$openId.'&lang=zh_CN';
        $res = self::httpRequest($requestUrl);
        return json_decode($res, true);
       	}

       	/**
       	 * 豆瓣第三方登录//由于豆瓣回调地址目前无法修改，所以未完成
       	 */
       	public static function doubanLogin($url='')
       	{
       		if(empty($url)){
       			$url = self::defaulturl;
       		}
       		$clientId = self::doubanAKey;
       		$doubanSKey = self::doubanSKey;
       		$config['key'] = $clientId;
       		$config['secret'] = $doubanSKey;
       		$config['redirect_url'] = $url.'/oauth/douban/callback.php';
       		$doubansdk = new DoubanOAuth($config);

       		$retUrl = $doubansdk->getAuthorizeURL();
       		return  $retUrl;
       	}


       	/**
       	 * http 请求 支持https，支持post，get
       	 */
       	private static function httpRequest($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }


}

