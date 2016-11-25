<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of qqLogin
 *
 * @author jiawei
 */
//include_once 'qqconfig.php';

class qqAuth {
    
    public $appid;
    
    public $appkey;
    
    public $access_tokey;
    
    public $callback;
    
    public $url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id=";
    
    public $token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&";
    
    public $get_opid_url = "https://graph.qq.com/oauth2.0/me?access_token=";
    
    public $user_info_url = "https://graph.qq.com/user/get_user_info?access_token=";
    
    public $add_share = "https://graph.qq.com/share/add_share";
    
    public $add_pic_t = "https://graph.qq.com/t/add_pic_t";
    
    public $get_fanslist = "https://graph.qq.com/relation/get_fanslist";
    
    public $get_info = "https://graph.qq.com/user/get_info?access_token=";
    
    public function __construct($appid,$appkey) {
        $this->appid = $appid;
        $this->appkey = $appkey;
        return true;
    }
    
    
    
    public function login($callback){
        $state = md5(uniqid(rand(), TRUE));
        $url = $this->url.$this->appid."&redirect_uri=" . urlencode($callback) . "&state=" . $state
        . "&scope=".QQ_SCOPE;
        return $url;
    }
    public function callback($callback){
            
            $url = $this->token_url. "client_id=" . $this->appid. "&redirect_uri=" . urlencode($callback)
            . "&client_secret=" . $this->appkey. "&code=" . $_REQUEST["code"];
            $response = $this->get_url_contents($url);
            if (strpos($response, "callback") !== false)
                {
                    $lpos = strpos($response, "(");
                    $rpos = strrpos($response, ")");
                    $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
                    $msg = json_decode($response);
                }
                
                $params = array();
                parse_str($response, $params);
                
                return $params;

    }
    
    
    public function get_openid($token)
    {
        
        $graph_url = $this->get_opid_url. $token;
        
        $str  = $this->get_url_contents($graph_url);
        if (strpos($str, "callback") !== false)
        {
            $lpos = strpos($str, "(");
            $rpos = strrpos($str, ")");
            $str  = substr($str, $lpos + 1, $rpos - $lpos -1);
        }

        $user = json_decode($str);
        
        
        if (isset($user->error))
        {
            return false;
        }

        return $user;
    }
    
    public function get_user_info($openid,$token)
    {
            $get_user_info = $this->user_info_url
            . $token
            . "&oauth_consumer_key=" . $this->appid
            . "&openid=" . $openid
            . "&format=json";
        $info = $this->get_url_contents($get_user_info);

        $arr = json_decode($info,true);

        return $arr;
    }
    
    
    public function get_info($openid,$token)
    {
            $get_info = $this->get_info
            . $token
            . "&oauth_consumer_key=" . $this->appid
            . "&openid=" . $openid
            . "&format=json";
        $info = $this->get_url_contents($get_info);

        $arr = json_decode($info,true);

        return $arr;
    }
    
    
    public function add_share($params){
       
       $data = array();
       $data['access_token'] = $params['access_token'];
       $data['oauth_consumer_key'] = $this->appid;
       $data['openid'] = $params['open_id'];
       $data['format'] = 'json';
       $data['title'] = $params['title'];
       $data['url'] = $params['url'];
       $data['comment'] = $params['comment'];
       $data['summary'] = $params['summary'];
       $data['images'] = $params['img'];
       $data['site'] = $params['site'];
       $data['fromurl'] = $params['fromurl'];
              
       $info = $this->post_url_contents($this->add_share,$data);
       
       $arr = json_decode($info,true);
       
       return $arr;
	    
    }
    
    public function add_pic_t($params){
       
       $data = array();
       $data['access_token'] = $params['access_token'];
       $data['oauth_consumer_key'] = $this->appid;
       $data['openid'] = $params['open_id'];
       $data['format'] = 'json';
       $data['content'] = $params['content'];
       $data['pic'] = '@'.$params['img'];
              
       $info = $this->post_url_contents($this->add_pic_t,$data);
       
       $arr = json_decode($info,true); 
       
       return $arr;
	    
    }
    
    public function get_fanslist($params){
	    $data = array();
	    $data['access_token'] = $params['access_token'];
	    $data['oauth_consumer_key'] = $this->appid;
        $data['openid'] = $params['open_id'];
        $data['format'] = 'json';
        $data['reqnum'] = $params['pagesize'];
        $data['startindex'] = $params['pagesize']*($params['page']-1);
        $data['model'] = 1;
        $info = $this->post_url_contents($this->get_fanslist,$data);
        
        $user = $this->get_info($params['open_id'],$params['access_token']);
               
        $arr = json_decode($info,true);
        
        $arr['total_number'] = $user['data']['fansnum'];

       
        return $arr;
    }
    
    
    public function get_url_contents($url)
    {
        if (ini_get("allow_url_fopen") == "1")
            return file_get_contents($url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result =  curl_exec($ch);
        curl_close($ch);

        return $result;
    }
    public function post_url_contents($url,$params){
	    include_once SERVER_ROOT  . '/lib/Request/qRequest.php';
	    $qRequest = new qRequest();
	    $result = $qRequest->post($url, $params, array(CURLOPT_TIMEOUT => 5), false);
	    return $result;
    }
    
    
}

?>
