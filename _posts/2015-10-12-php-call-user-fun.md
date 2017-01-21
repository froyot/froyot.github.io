---
layout: post
title: PHP call_user_fun 不同版本区别
category: Developer
comments: true
---


PHP中call\_user\_fun可以调用用户回调函数。但是由于PHP本身的版本,
这个函数在不同的版本上表现还是有区别的，而且这个区别是致命的，
5.4版本下的代码在5.3的版本上直接报错。

先看看一段5.4下正常的代码

```php

<?php

class Api{
    public $onConnect;
    public $onMessage;
    public $name;
    public $data;
    function __construct($name, $data)
    {
        $this->name = $name;
        $this->data = $data;
    }
    public function connect()
    {
        //to connect data
        //onConnect
        if( $this->onConnect )
        {
            call_user_func( $this->onConnect, $this );
        }
    }

}

class Client{
    public $clientName;
    function __construct()
    {

    }

    public function afterGetData()
    {
        var_dump($this->clientName);
        //after get data
    }

    public function getData()
    {
        $api = new Api('client1', 'data1');

        $api->onConnect = function($api)
        {
            $this->clientName = $api->name;
            $this->afterGetData();
            //if I want to do more other thins, and can't get data from api
        };

        $api->onMessage = function($api)
        {
            //get message callback
        };

        $api->connect();
    }

}

$client = new Client();
$client->getData();

?>
```

这段代码在5.4是完全没问题的，但是在5.3的版本上居然报错，提示错误:
Using $this when not in object context,提示错误代码行

```php

$this->clientName = $api->name;
$this->afterGetData();

```
在这里，在回调函数中调用一些当前对象的函数以及使用当前对象的其他属性
是完全有可能的。但是！但是！5.3中不可以。

尝试向js中一样把$this改成$that, 然后发现，在回调函数中，$that指向的是
一个stdClass对象。

所以，一直不知道如何实现这个功能：
在php5.3中的call\_user\_fun中调用当前对象的一个非静态函数。就是代码中如何
实现调用client对象的afterGetData方式。



