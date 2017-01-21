---
layout: post
title: Yii 作为模块被调用
category: PHP
comments: true
description: " Yii 作为模块被调用 "
---


Yii以及其他PHP框架，通常是通过一个入口文件把框架类库，引入进来。
然后根据route找到指定的控制器执行业务逻辑。一般的框架都可以很容易的集成第三方类库。
可是，如果说，多个项目之间需要相互调用，而且多个项目之间不是用相同的框架写的，但是是同一个语言。
如果不是相同语言，就只好是各个框架之间开放不同的接口，通过rest或者soap的形式进行接口调用。
虽然把各个模块封装成接口，可以很大的降低项目之间的耦合。但是接口同时也包含代码调用的形式。
项目中用到了workman作为消息发送，业务逻辑采用yii处理。那么问题来了，workman如何调用yii

## 问题
workman是用php命令方式执行的，yii2是跑在apache上面。如果采用rest调用方式，
消息经workman转发到yii2上进行处理，然后在返回到workman中。这个过程就会参数一个内部的网络io,
同时每个请求都需要一个apache进程，以及workman的进程才能进行处理。对主机有一定的压力。
采用node，每秒创建一个socket连接，创建3000个,每次连接上之后，workman根据连接创建一个uuid,
并返回到workman的客户端。如果采用rest的方式请求yii,请求在处理一部分的时候，服务器就挂掉了(本地采用xampp默认配置)。
如果数据不到400个。而如果直接通过workman直接进行数据库操作，数据都可以入库。

## 解决
workman与yii本来就在一台主机上，如果两个项目能够融合，直接在代码层进行调用，那数据的丢失率就可以下降。
所以要做的是将yii2z作为一个模块被其他项目调用。观察yii2的入口文件：

```php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_ENV_TEST') or define('YII_ENV_TEST', true);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/common/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require (__DIR__ . '/common/config/main.php'),
    require (__DIR__ . '/common/config/main-local.php'),
    require (__DIR__ . '/frontend/config/main.php'),
    require (__DIR__ . '/frontend/config/main-local.php')
);

$application = new yii\web\Application($config);
$application->run();

```

yii2在入口创建一个application，调用application的run方法处理请求,run方法代码:

```php
    $this->state = self::STATE_BEFORE_REQUEST;
    $this->trigger(self::EVENT_BEFORE_REQUEST);

    $this->state = self::STATE_HANDLING_REQUEST;
    $response = $this->handleRequest($this->getRequest());

    $this->state = self::STATE_AFTER_REQUEST;
    $this->trigger(self::EVENT_AFTER_REQUEST);

    $this->state = self::STATE_SENDING_RESPONSE;
    $response->send();

    $this->state = self::STATE_END;

    return $response->exitStatus;
```

```php
    public function handleRequest($request) {
        if (empty($this->catchAll)) {
            list($route, $params) = $request->resolve();
        } else {
            $route = $this->catchAll[0];
            $params = $this->catchAll;
            unset($params[0]);
        }

        try {
            Yii::trace("Route requested: '$route'", __METHOD__);
            $this->requestedRoute = $route;

            $result = $this->runAction($route, $params);

            if ($result instanceof Response) {
                return $result;
            } else {

                $response = $this->getResponse();
                if ($result !== null) {
                    $response->data = $result;
                }

                return $response;
            }
        } catch (InvalidRouteException $e) {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'), $e->getCode(), $e);
        }
    }
```

*   第一个问题，如何将参数通过传参而非网络请求的形式传进来，同时从参数中获取路由。

可以看到，yii2通过Request的resolve()函数获得请求的地址以及参数。
因此在resolve()函数中添加一下代码

```
    if ($this->_route) {
            return [$this->_route, $this->getQueryParams()];
    }
```

并在Request中添加一个_route私有属性，通过函数setRoute设置路由

```
    public function setRoute($value) {
        $this->_route = $value;
    }
```
路由参数已经传入，需要传入请求参数。请求参数可以调用Request的
```
setQueryParams($value),设置GET参数
setBodyParams($value),设置post参数
```

*   第二个问题，如何获取返回。

查看Response代码，请求处理之后的结果格式化后的结果内容在Response的content当中，
yii\web\Response中通过send向终端输出数据。因此$application->run();之后可以给根
据Yii::$app->response->content获取返回接口，return给调用函数。

*   第三个问题，异常怎么处理

yii2通过设置errorHandler处理程序中的异常，保证返回结果不会太难看。如果程序调用之后呢？
通过跟踪程序发现，抛出异常之后，会执行errorHandler，并执行Response send的方法。
但是，**调用函数还是会抛出异常**。为什么，我也不知道！！

*   其他问题：

    如果重写Response的send方法，去掉输出，程序是报错的。。。。。，只要有输出(echo,var_dump,print..)，程序有没问题了。

    需要设置scriptUrl,scriptFile;

最终入口文件

```
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_ENV_TEST') or define('YII_ENV_TEST', true);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/common/config/bootstrap.php';

require __DIR__ . '/common/helper/apiApplication/Application.php';

function callYiiController($headerDatas, $data) {

    $config = \yii\helpers\ArrayHelper::merge(
        require (__DIR__ . '/common/config/main.php'),
        require (__DIR__ . '/common/config/main-local.php'),
        require (__DIR__ . '/rest/config/main.php'),
        require (__DIR__ . '/rest/config/main-local.php')
    );
    $config['components']['request']['scriptUrl'] = "/" . basename(__FILE__);
    $config['components']['request']['scriptFile'] = basename(__FILE__);
    $config['components']['response']['class'] = 'common\helper\apiApplication\Response';
    $config['components']['response']['format'] = \common\helper\apiApplication\Response::FORMAT_JSON;
    $application = new \common\helper\apiApplication\Application($config);

    $headers = Yii::$app->request->getHeaders();
    foreach ($headerDatas as $key => $headerData) {
        $headers->add($key, $headerData);
    }
    Yii::$app->request->setBodyParams(['_json' => $data]);

    $application->request->setRoute('api/device/socket/index');
    try {
        $application->run();

    } catch (\Exception $e) {
        $errorHandler = $application->errorHandler;
        $errorHandler->handleException($e);
    }
    Yii::$app->db->close();

    return $application->response->content;
}
```



