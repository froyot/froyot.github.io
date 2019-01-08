---
layout: post
title: 从YII2 和ThinkPHP5 中看PHP如何获取所有请求头
category: PHP
comments: true
description: 从YII2 和ThinkPHP5 中看PHP如何获取所有请求头
keywords: YII2,ThinkPHP5,获取请求头,写PHP的老王
---

开发当中，很多信息除了通过参数传递之外，还会有一些数据通过请求头来传递。分析Yii2和ThinkPHP5 框架代码，看如何用PHP语言获取请求头。

### Yii2 获取所有请求头
```php
    public function getHeaders()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            
        } elseif (function_exists('http_get_request_headers')) {
            $headers = http_get_request_headers();
            
        } else {
            foreach ($_SERVER as $name => $value) {
                if (strncmp($name, 'HTTP_', 5) === 0) {
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$name] = $value;
                }
            }
        }
        return $headers;
    }
```

<!-- more -->

Yii2中采用了两个函数```getallheaders```，```http_get_request_headers``` 尝试获取请求头。```getallheaders```函数是函数```apache_request_headers```的别名。如果函数不存在，再通过_SERVER获取。_SERVER获取请求头，将下划线转换成中划线，首字母大写的请求头。

### Thinkphp5获取所有请求头
```php
    public function getHeaders()
    {
        $headers = [];
        if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
            $headers = $result;
        } else {
            foreach ($_SERVER as $key => $val) {
                if (0 === strpos($key, 'HTTP_')) {
                    $key          = str_replace('_', '-', strtolower(substr($key, 5)));
                    $headers[$key] = $val;
                }
            }
            
        }
        $headers = array_change_key_case($headers);
    }
```
Thinkphp5中采用了两个函数```apache_request_headers``` 尝试获取请求头。如果函数不存在，再通过_SERVER获取。_SERVER获取请求头，将下划线转换成中划线，小写字母请求头。

### 获取请求头的函数
* apache_request_headers函数是Apache下才支持的函数。NGINX不支持！！
* http_get_request_headers,函数依赖PECL pecl_http >= 0.10.0

>微信公众号：**[写PHP的老王]**
关注可了解更多的关于PHP代码问题。联系请公众号留言;