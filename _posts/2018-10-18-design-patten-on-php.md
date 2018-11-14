
---
layout: post
title: 设计模式(一),创建模式(如何创建，谁创建，什么时候创建)
category: 算法
comments: true
description: 设计模式(一),创建模式(如何创建，谁创建，什么时候创建)
keywords: 设计模式,工厂模式,单例模式,建造者模式
---


1、工厂模式
通过工厂类，创建不同的对象。工厂模式适合：凡是出现了大量的产品需要创建，并且具有共同的接口时，可以通过工厂方法模式进行创建。

以日志操作类为例
```php
abstract class BaseApp{
	protected $input;
	protected $response;
	protected $route;
	public function start(){
		return $this->handlerRequest();
	}
	public function setInput($input){
		$this->input = $input;
	}
	public function setResponse($response){
		$this->response = $response;
	}
	public function setRoute($route){
		$this->route = $route;
	}
	abstract public function handlerRequest();
}

class CliApp extends BaseApp{
	protected $args;
    public function handlerRequest(){
        //write to file
    }
}

class WebApp extends BaseApp{
	protected $urlparams;
	protected $requestMethod;
	protected $remoteIp;


    public function handlerRequest(){
        //write to db
    }
}

```

1)简单工厂模式，通过参数创建指定类

```php

class AppFactory{
	public function getApp($apptype){
		switch($apptype){
			case 'web': return new WebApp();
			case 'cli': return new CliApp();
		}
	}
}

$app = new AppFactory('web');
$app->start();

```

2) 多方法模式，通过指定方法创建指定类

```php

class AppFactory{
	public function getWebApp($apptype){
		return new WebApp();
	}
	public function getCliApp($apptype){
		return new CliApp();
	}
}

$logger = (new AppFactory())->getWebApp('web');
$logger->log($msg);

```

3) 多个静态方法

```php

class AppFactory{
	public static function getWebApp($apptype){
		return new WebApp();
	}
	public static function getCliApp($apptype){
		return new CliApp();
	}
}

$logger = AppFactory::getWebApp('web');
$logger->log($msg);

```


2、工厂方法模式（Factory Method）
简单工厂模式有一个问题就是，类的创建依赖工厂类，也就是说，如果想要拓展程序，必须对工厂类进行修改。工厂方法模式，创建一个工厂接口和创建多个工厂实现类，这样一旦需要增加新的功能，直接增加新的工厂类就可以了，不需要修改之前的代码。

```php

interface FactoryInterface{
	public function getApp();
}

class WebAppFactory implements FactoryInterface{
	public function getApp(){
		return new WebApp();
	}
}

class CliAppFactory implements FactoryInterface{
	public function getApp(){
		return new CliApp();
	}
}
$factory = new WebAppFactory();
$logger = $factory->getApp();
$logger->log($msg);

```

3、单例模式（Singleton）

省去了new操作符，降低了系统内存的使用频率。

```php
class App{
	public static $instance;

	public function getApp(){
		if(!App::$instance)
		{
			App::$instance = new App();
		}
		return App::$instance;
	}
}

```


4、建造者模式（builder）
是将一个复杂的对象的构建与它的表示分离，使得同样的构建过程可以创建不同的表示。即在原有直接创建对象的过程中添加一层创建者的封装，将整个构建过程封装在一起。**工厂模式的区别是：建造者模式更加关注与零件装配的顺序。**
角色:
1)、Builder：给出一个抽象接口，以规范产品对象的各个组成成分的建造。这个接口规定要实现复杂对象的哪些部分的创建，并不涉及具体的对象部件的创建。
2)、ConcreteBuilder：实现Builder接口，针对不同的商业逻辑，具体化复杂对象的各部分的创建。 在建造过程完成后，提供产品的实例。
3)、Director：调用具体建造者来创建复杂对象的各个部分，在指导者中不涉及具体产品的信息，只负责保证对象各部分完整创建或按某种顺序创建。
4)、Product：要创建的复杂对象。

使用场景： 
1、需要生成的对象具有复杂的内部结构。 
2、需要生成的对象内部属性本身相互依赖。

```php
abstract class BaseBuilder{
	public function buildApp($app);
	public function getApp();
}
class BuilderWebAppFromCliApp extends BaseBuilder{
	protected $webApp;

	public function buildApp($app){
		$args = $app->getArgs();
		$this->webApp = new WebApp();
		$this->webApp->setUrlparams($args[0]);
		$this->webApp->setClientIp('127.0.0.1');
		$this->webApp->setRequestMethod('GET');
	}

	public function setInput($input){
		$this->webApp->input = $input;
	}
	public function setResponse($response){
		$this->webApp->response = $response;
	}
	public function setRoute($route){
		$this->webApp->route = $route;
	}

	public function getApp(){
		return $this->webApp;
	}
}

class BuilderDirector {
	protected $builder;

	public function getWebAppFromCliApp(CliApp $app,$request){
		$builder = new BuilderWebAppFromCliApp($app);
		$builder->setInput($request);
		$builder->setInput($route);
		return $builder->getApp();
	}
}

$cliApp = new CliApp();
$director = new BuilderDirector();
$builderWebApp = $director->getWebAppFromCliApp($cliApp)
$builderWebApp->start();

```


