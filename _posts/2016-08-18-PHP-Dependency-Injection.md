---
layout: post
title: PHP 依赖注入
category: PHP
comments: true
description: " PHP 依赖注入,注入容器和服务定位器 "
---

## PHP 依赖注入
考虑一个问题，如果一个web应用需要一个日志服务，日志服务可以是文本，数据库或者邮件的形式，
而且日志需要将获取的信息格式化指定的形式。应用可以根据需要，任意切换日志服务是文本还是数
据库还是邮件。如果以传统的方式，日志记录的代码类似下面的形式:

```php


    class Logger{
        public function formatLog($log)
        {
            //TO-DO format logger
            return $log;
        }

        public function log($str,$type)
        {
            $logTxt = $this->format($str);
            swtich($type)
            {
                case 'database':
                    $this->databaseLog($logTxt);break;
                case 'file':
                    $this->fileLog($$logTxt);break;
                case 'email':
                    $this->emailLog($$logTxt);break;
            }
        }

        public function databaseLog($str)
        {
            $db = new DbHelper();
            $db->save('logger',$str);
        }

        public function fileLog($str)
        {
            $file = new FileHelper();
            $file->save('logger',$str);
        }

        public function emailLog($str)
        {
            $email = new EmailHelper();
            $email->send(ADMIN_EMAIL,$str);
        }
        ...
    }


```

从上面的代码中可以看出来，logger类依赖数据库操作类DbHelper，依赖文件操作类FileHelper,
依赖邮箱操作类FileHelper。但是这些关系都是写在Logger这个类文件中的，耦合性高。如果项目
中希望在不同的页面将日志记录在不同的文件，数据表，或者发送给不同的管理者邮箱呢？这个时
候就需要对类的依赖在不同的地方进行注入。在百度百科上,[依赖注入](http://baike.baidu.com/link?url=HXcWVbRVhF2g2v0GaLdeuo62PWGBJtT_5nvZ8QQvzYRi_MGMt1JA6q7tD-VNWA52TueUgCMz1IjnpGLXJ6v2LgDvO4uvAZJ55mASRodeiI2QuxHA7YGjcewB7r_OOZ8k2VxKpJxFd5ZbGSqfE0kiXNe_DbPzM3eurNiAknvkSL1vavH-i6-Adf749DoQUdLI)
说的太高深了，就是解除代码之间的耦合关系。

注入有一下几种方式

*  通过构造函数传递参数

```
    class Logger{
        public $loggerHnadler;
        public __construct($loggerHandler)
        {
            $this->loggerHandler = $this->loggerHandler;
        }

        public function log($str)
        {
            $this->loggerHnadler->log($str);
        }
    }

    class FileLoggerHandler{
        $public $filePath;
        public __construct($filePath)
        {
            $this->filePath = $this->filePath;
        }

        public function log($str)
        {
            file_put_contents($this->filePath,$str);
        }
    }

    class EmailLoggerHandler{
        $public $adminMail;
        public __construct($adminMail)
        {
            $this->adminMail = $this->adminMail;
        }

        public function log($str)
        {
            sendEmail($this->adminMail,$str);
        }
    }

    //调用

    $fileLoggerHandler = new FileLoggerHandler(WEB_ROOT.'/logger/login.log');

    $logger = new Logger(fileLoggerHandler);
    $logger->log('login error'.getError());

    $emailLoggerHandler = new EmailLoggerHandler(DEV_ABB_EMAIL);
    $logger = new Logger(emailLoggerHandler);
    $logger->log('login error'.getError());


```

*  通过set方式进行注入
这种方式跟构造函数类似，只不过是在实例化类之后对其属性进行复制设置。

```
    $logger = new Logger();
    $logger->setLoggerHandler(fileLoggerHandler);
    $logger->log();
    $logger->setLoggerHandler(emailLoggerHandler);
    $logger->log();
```

上面的两种注入方式都需要提前准备依赖的类对象。加速文件日志类依赖文件读写类，依赖
目录操作，权限验证等相关类文件，则需要按依赖顺序构造好相应的类对象，并且对各个对
象进行依赖的注入。所以需要重复很多对象创建，set的操作。因此就有了依赖注入容器
container,用于实现类的依赖对象的管理等操作。github上有一个最最简单的Container
[Twittee](https://github.com/fabpot-graveyard/twittee),简单而有效。

```
    class Container{
        public $cont = array();
        function __set($k, $c) { $this->s[$k]=$c; }
        function __get($k) { return $this->s[$k](); }
    }

    class Logger{
        public $container;

        public __construct()
        {
            $this->container = new Container();
        }

        public function addContainerData($key,$class)
        {
            $this->container->set($key,$class);
        }

        public function log($containerKey,$str)
        {
            $this->container->get($containerKey)->log();
        }
    }


    //使用

    $logger = new Logger();
    $logger->addContainnerData('fileLog','FileLoggerHandler');
    $logger->log('error info');

```

这个最简单的注入容器解决了依赖对象创建繁杂的问题。但是没有解决注入对象依赖问题。所以
一些框架的依赖注入容器中添加了获取对象依赖的操作:

```

    class Container{
        public $cont = array();
        function __set($k, $c) { $this->s[$k]=$c; }
        function __get($k) { return $this->s[$k](); }
        function getDepends($key)
        {
            $class = $this->s[$k];
            $dependencies = array();
            $ref = new ReflectionClass($class);//获取对象的实例
            $constructor = $ref->getConstructor();//获取对象的构造方法
            if($constructor !== null)
            {//如果构造方法有参数
                foreach($constructor->getParameters() as $param)
                {//获取构造方法的参数
                    if($param->isDefaultValueAvailable())
                    {//如果是默认 直接取默认值
                        $dependencies[] = $param->getDefaultValue();
                    }
                    else
                    {//将构造函数中的参数实例化
                        $temp = $param->getClass();
                        $temp = ($temp === null ? null : $temp->getName());
                        $temp = Instance::getInstance($temp);//这里使用Instance 类标示需要实例化 并且存储类的名字
                        $dependencies[] = $temp;
                    }
                }
            }
            $this->_reflections[$class] = $ref;
            $this->_dependencies[$class] = $dependencies;
        }
    }

    //使用

    $logger = new Logger();
    $logger->addContainnerData('fileLog','FileLoggerHandler');//这里会自动创建FileLoggerHandler依赖类对象
    $logger->log('error info');

```


