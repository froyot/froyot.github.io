---
layout: post
title: php 设计模式 一
category: 基础
comments: true
description: "php 设计模式,单例模式,工厂模式,观察者模式"
---


程序运行期间只有一个实例对象。单例模式类似于全局变量，在整个应用运行期间，共同操
作通一个对象。

```
class App{
    public static $appInstance;

    private $name;


    public static function getInstance()
    {
        if(App::$appInstance == null)
        {
            App::$appInstance = new App();
        }
        return App::$appInstance;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
```

使用
```
$app = App::getInstance();
$app->setName('news');
echo $app->name;
```

##2.工厂模式

通过给工厂类指定不同的创建需求创建不同的类实例;

```
class People{
    function __construct($sex)
    {
        switch($sex){
            case 'male':
                return new Male();
            case 'female':
                return new Female();
        }
    }
}

class Male{
    public $sex = 'male';
}

class Female{
    public $sex = 'female';
}
```
使用:

```
$male = new People('male');
$female = new People('female');
```

##3.观察者模式

观察者模式是一个对象被多个对象观察(订阅),当这个被观察者对象改变时需要通知订阅者

```
class Subject{
    public $message;
    public $observes = [];
    /**
     * 被观察对象
     */
    public function addObserve($observe)
    {
        if(!in_array($observe,$this->observes))
        {
            $this->observes[] = $observe;
        }
    }

    public function deleteObserve($observe)
    {
        $index = array_search($observe,$this->observes);
        if($index !== false)
        {
            unset($this->observes[$index]);
        }
    }

    private function notifyObserve()
    {
        foreach($this->observes as $observe)
        {
            $observe->notify($this);
        }
    }

    public function setMessage($message)
    {
        $this->message = $message;
        $this->notify();
    }
}

class ObserveNews()
{
    public function notify($subject)
    {
        //TO-DO something
        echo 'news new message '.$subject->message;
    }
}

class ObserveWeibo()
{
    public function notify($subject)
    {
        //TO-DO something
        echo 'weibo new message '.$subject->message;
    }
}
```

使用

```
$subject = new Subject();
$subject->addObserve(new ObserveNews());
$subject->addObserve(new ObserveWeibo());
$subject->setMessage('a');//这里将会输出两条数据news new message a,weibo new message a;
```

