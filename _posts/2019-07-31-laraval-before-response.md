
---
layout: post
title: Laravel 如何完成beforeResponse功能?
category: php
comments: true
description: 关于 select 返回数据的顺序的几个问题
keywords: MYSQL,SELECT，分组第一条，TOP1
---

### 背景

一般的项目需求都会要求统一的输出结构，特别是对于api应用而言。因此，如果有beforeResponse的功能，则可以在数据输出之前对response进行统一格式化处理。

假设这么一种场景，应用做api开发，使用抛异常的方式(自定义异常类ApiException)返回无效非法请求的情况。正常请求则返回合法数据(数组或可序列化的模型)，希望返回的数据格式

<!-- more -->

正常请求返回数据格式:

```json
{
  "code":0,
  "data":[

  ],
  "message":""
}
```

异常请求返回数据格式:

```json
{
  "code":400,
  "data":[

  ],
  "message":"错误提示"
}
```

### Laravel 的设计如何实现

Laravel中的中间件确实支持beforeResponse操作，支持在中间件中进行格式化。但是，这里仅限于正常返回。那么如果控制器抛了异常又改怎么办呢？

Laravel的调用链使得控制器里的异常在正常情况下，还没有抛到中间件就被系统注册的ExceptionHandler类拦截处理了。github上也有关于中间件不能捕获控制器异常的问题[Can't catch exception in middleware](https://github.com/laravel/framework/issues/14573#)

作者给出的结论是，Laravel本身的设计就是将异常处理放在ExceptionHandler中。
>Yes, this is the beavhiour starting from L5.2. Throwing an exception causes the response to be set as that returned from the exception handler, and then the middleware is allowed to backout from that point.

>We don't recommend you write try catch blocks in middleware. Instead, handle exceptions inside the exception handler. Perhaps [https://github.com/GrahamCampbell/Laravel-Exceptions](https://github.com/GrahamCampbell/Laravel-Exceptions) would be of use to you?



那么，按照Laravel的设计，正常的请求，我们在一个中间件``FormaterResponse``处理,处理逻辑如下:

```php

<?php
namespace App\Http\Middleware;
use App\Http\Middleware\Closure;
use \Exception;
class FormaterResponse
{
  public function handle($request, \Closure $next)
  {
    $response = $next($request);
    $content = $response->getData();
    $content = [
      'code'=>0,
      'message'=>'',
      'data'=>$content
    ];
    $response->setData($content);
    return $response;
  }
}


```
错误返回，我们在``app\Exceptions\Handler`` 中`` render ``方法处理，格式化，处理逻辑如下:

```php

public function render($request, Exception $e)
{
  if($e instanceof ApiException)
  {
    $response = [
      'code'=>$e->getCode(),
      'message'=>$e->getMessage(),
      'data'=>[]
    ];
    return response()->json($response, 200);
  }
  parent::render($request,$e);
}
```

### 更好的方式

上面的这种做法有一个弊端，如果某些模块下想要的数据格式返回不一样，对应异常情况的处理会比较麻烦。因为ExceptionHandler是对一个全局的处理。如果能把数据格式化都放在中间件处理，则可以非常灵活。

其实需要改动的内容非常上，只需要在ExceptionHandler中的handle方法中，对于自定义异常类``ApiException``继续向上抛出去就可以在``middleware``捕获到异常，进而对异常放回进行格式化。

修改之后``App\Exceptions\Handler`` 中render的代码如下:

```php
public function render($request, Exception $e)
{
  if($e instanceof ApiException)
  {
    throw $e;
  }
  parent::render($request,$e);
}
```

```php
<?php

namespace App\Http\Middleware;
use App\Http\Middleware\Closure;
use App\Exceptions\ApiException;
class FormaterResponse
{
  public function handle($request, \Closure $next)
  {
    $code = 0;
    $msg = '';
    $data = [];
    try{
      $response = $next($request);
      $data = $response->getData();
    }catch(ApiException $e){
      $code = $e->getCode();
      $msg = $e->getMessage();
      $response = response()->json([],200);
    }
    $content = [
      'code'=>$code,
      'message'=>$msg,
      'data'=>$data
    ];
    $response->setData($content);
    return $response;
  }
}
```

这样就可以在所有应用``FormaterResponse``的路由中实现beforeRespons 功能，格式化统一的数据输出。



