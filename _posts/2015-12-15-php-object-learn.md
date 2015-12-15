---
layout: post
title: PHP面向对象深入--构造函数中调用成员函数，到底调用的是哪个类的函数
category: 技术
comments: true
description: "PHP中，构造函数中调用成员函数，到底调用的是哪个类的函数?"
---


## PHP中，构造函数中调用成员函数，到底调用的是哪个类的函数?
PHP中，静态方法中不能使用$this，即用双冒号::调用的函数。这里，构造函数要除外，那么，
构造函数中的$this到底指向谁，$this->display函数又会执行那个类里面的函数？

如果去看Yii2的源码，发现Yii2的源码都是在构造函数中调用一个init函数对类进行初始化
的操作。但是如果看Application.php的源码，发现在构造函数\__construct里面并没有显示
的调用init函数，也没有调用其父类的构造函数，而是调用Compoment的构造函数，继续追踪，
发现Application 继承 Module, Module继承ServiceLocator, ServiceLocator继承Component，
Component继承Object, 而在Object的构造函数中调用了init函数。


```php
    public function __construct($config = []) {
        if (!empty($config)) {
            Yii::configure($this, $config);
        }
        $this->init();
    }
```

那么，这里的**$this**指向Application还是Object本身呢?为此，用下面的程序进行试验
从结果可以看出，$this指向的是调用者本身。
如果父类的display方法是public或者protected(即可以被子类继承),执行的是调用类，否则
就是当前所在类的成员函数。但是这种情况下，display函数里面打印**$this**依然是调用类。


```php

<?php
class MyObj {

    function __construct() {
        $this->display();
        var_dump($this);//输出MyApp
    }

    public function display(){//修饰符是private的时候执行该函数，否则执行子类函数
        var_dump($this);//输出MyApp，无论方法修饰符是private 还是public,protected
        echo 'obj';
    }

}
class Modle extends MyObj {

}

class Compoment extends Modle {

}
class MyApp extends Compoment {
    function __construct() {
        Modle::__construct();
    }

    public function display(){//
        echo 'obj test public';
    }
}

$objOther = new MyApp();
?>
```

理解(此处仅仅是个人理解) :

*   如果父类的diplay函数是可以被继承的，且子类中重写了该函数，则调用的是子类
的display函数。其他情况，不可继承或子类并没与重写该函数，执行父类函数。

*   对于$this的指向，$this总是指向最终构造的对象。因为这是构造函数。



