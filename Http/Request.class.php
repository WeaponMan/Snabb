<?php

namespace Snabb\Http;

final class Request extends \Snabb\StaticClass {
  const METHOD_GET = 'GET';
  const METHOD_POST = 'POST';
  
  public static $method, $headers, $https = false, $uri, $query_string, $server_domain, $server_address, $server_port, $source_address, $source_port, $post, $get, $full_domain, $domain_for_cookie;
}

Request::$method = &$_SERVER['REQUEST_METHOD'];
Request::$headers = getallheaders();
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
  Request::$https = true;
Request::$uri = &$_SERVER['REQUEST_URI'];
Request::$query_string = &$_SERVER['QUERY_STRING'];
Request::$server_domain = &$_SERVER['SERVER_NAME'];
Request::$server_address = &$_SERVER['SERVER_ADDR'];
if(isset($_SERVER['SERVER_PORT']))
  Request::$server_port = (int)$_SERVER['SERVER_PORT'];
Request::$source_address = &$_SERVER['REMOTE_ADDR'];
if(isset($_SERVER['REMOTE_PORT']))
  Request::$source_port = (int)$_SERVER['REMOTE_PORT'];
Request::$post = &$_POST;
Request::$get = &$_GET;
Request::$full_domain = (Request::$https ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . ((Request::$server_port == 443 or Request::$server_port == 80) ? '' : ':' . Request::$server_port);
$domain_without_http = $_SERVER['HTTP_HOST'] .((Request::$server_port == 443 or Request::$server_port == 80) ? '' : ':' . Request::$server_port);
Request::$domain_for_cookie = $domain_without_http === 'localhost' ? null : $domain_without_http;
unset($domain_without_http);


