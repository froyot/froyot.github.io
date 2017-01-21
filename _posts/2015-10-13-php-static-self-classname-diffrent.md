---
layout: post
title: PHP static self classname 区别
category: PHP
comments: true
---

PHP中调用静态变量或者静态调用方法在不同的场景中可以使用
className, static, self三种方式调用. 但是这三种方式除了
使用场景上的些许不同还有什么需要注意的呢？前些日志搬砖不
小心踩到一个坑，所以记录一下。

### static

PHP中, static 除了在定义的时候作为限制符号对变量已经函数
进行限制，还可以表示**当前**调用的对象。如果一个对象并没
有集成其他的对象，那么使用static方式调用，和使用self方式
调用是没有区别的，都是指代该对象。但是如果static在代码中
的位置是在一个父类函数中，子类执行该方法的时候，static指
代的是子类，而self指代的是父类。

### self

self 调用，主要是在类内部进行使用，指代类本身。


### className

以className的方式调用类的静态属性，不仅仅限于类内部，可以在
任何位置(结合属性限制修饰)，这也是className方式与其他方式的
主要区别。同时className永远执行className所对应的类本身。

stackoverflow有个问题回答了static,self的区别,[链接>>](http://stackoverflow.com/questions/4718808/php-can-static-replace-self)

```php
<?php

class Parent_
{
    protected static $x = "parent";
    public static function makeTest()
    {
        echo "self => ".self::$x."<br>";
        echo "static => ".static::$x;
    }
}

class Child_ extends Parent_
{
    protected static $x = "child";
}

echo "<h4>using the Parent_ class</h4>";
Parent_::makeTest();

echo "<br><h4>using the Child_ class</h4>";
Child_::makeTest();
?>
```

输出结果:

```
using the Parent_ class

self => parent
static => parent
using the Child_ class

self => parent
static => child

```

