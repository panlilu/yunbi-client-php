<?php

/**
 *
 * A Simple PHP Yunbi API Client
 *
 * @author Panlilu <panlilu@gmail.com>
 * @version 0.0.1
 */

class YunbiClientException extends Exception {}

class YunbiClient
{
  public $options=[];

  public function __construct($options=array()) {
    $default_options = array(
      'api_host'    => "https://yunbi.com/",
      'access_key'  => "Your Access Key",
      'secret_key'  => "Your Secret Key",
      'timeout_sec' => 30,
      'user_agent'  => "Yunbi API Client/0.0.1",
    );
    $this->options = array_merge($default_options, $options);
  }

  public function set_option($key, $value){
    $this->options[$key] = $value;
  }

  public function get($path, $params=null) {
    return $this->yunbi_api_req($path, $params);
  }

  public function post($path, $params=null) {
    return $this->yunbi_api_req($path, $params, 'POST');
  }

  // get tonce
  private function get_tonce() {
    $mt = explode(' ', microtime());
    return $mt[1].substr($mt[0], 2, 6);
  }

  private function yunbi_api_req($path, $params=null, $canonical_verb='GET') {

    $api_host    = $this->options['api_host'];
    $access_key  = $this->options['access_key'];
    $secret_key  = $this->options['secret_key'];
    $timeout_sec = $this->options['timeout_sec'];
    $user_agent  = $this->options['user_agent'];

    // only support GET & POST
    $is_post = ($canonical_verb == 'POST');
    if (!$is_post) {
      $canonical_verb = 'GET';
    }

    $canonical_uri = strtolower($path);

    // full the tonce
    $tonce = $this->get_tonce();
    $params['tonce'] = $tonce;

    // full the access_key
    $params['access_key'] = $access_key;

    if (substr($api_host , -1) == '/') {
      $api_host  = substr($api_host , 0, -1);
    }
    $url = $api_host .$path;

    // full the signature
    ksort($params);
    $canonical_query = http_build_query($params);
    $sign_str = $canonical_verb.'|'.$canonical_uri.'|'.$canonical_query;
    $signature = hash_hmac('SHA256', $sign_str, $secret_key);
    $params['signature'] = $signature;

    // query_str for curl_get_contents
    $query_str = http_build_query($params);

    if ($is_post) {
      if (!empty($params)) {
        $content = $this->curl_get_contents($url, $query_str, $timeout_sec, $user_agent);
      }
    } else {
      if (!empty($params)) {
        $url .= '?' . $query_str;
      }
      $content = $this->curl_get_contents($url, null, $timeout_sec, $user_agent);
    }
    if (empty($content)) {
      throw new YunbiClientException("Content is empty.");
    }
    $obj = json_decode($content, 1);
    if (empty($obj)) {
      throw new YunbiClientException("JSON decode failed, content: " .$content);
    }
    return $obj;


  }

  private function curl_get_contents($url, $post_params=null, $timeout_sec=5, $user_agent) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout_sec);

    if (!empty($post_params)) {
      curl_setopt($ch, CURLOPT_POST, 1);
      if (is_array($post_params)) {
        $post_params = http_build_query($post_params);
      }
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
    }

    $r = curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);
    if ($curl_errno > 0) {
      throw new YunbiClientException("cURL Error ($curl_errno): $curl_error, url: {$url}");
    }
    return $r;
  }

}