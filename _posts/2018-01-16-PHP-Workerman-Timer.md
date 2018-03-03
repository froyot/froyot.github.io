---
layout: post
title: PHP Workerman 中使用Timer的实现
category: PHP
comments: true
description: PHP Workerman 中使用Timer的实现
keywords: PHP,Workerman,pcntl_signal,Timer
---

Workerman 是由PHP原生开发的常驻内存应用框架。通过Workerman可以实现PHP常驻内存，给PHP更加广泛的应用场景。类似的还有使用C语言开发的PHP扩展 Swoole,也是实现类似的方案。下面来看看Workerman中如何实习定时器Timer。

```
public static function init($event = null)
{
    if ($event) {
        self::$_event = $event;
    } else {
        pcntl_signal(SIGALRM, array('\Workerman\Lib\Timer', 'signalHandle'), false);
    }
}

/**
 * ALARM signal handler.
 *
 * @return void
 */
public static function signalHandle()
{
    if (!self::$_event) {
        pcntl_alarm(1);
        self::tick();
    }
}

```

以上就是核心代码，init函数通过参数注入，确定事件处理机制。默认使用pcntl信号处理。signalHandle用于接受信号之后的处理函数。如果设置了事件处理对象，则调用事件处理对象进行处理，否则调用tick方法，对pcntl信号进行处理。从上面代码可以看出，pcntl信号机制，定时每隔一秒触发一次，所以Workerman 的定时器最小精度1s。但是如果使用其他事件处理方式，确实可以达到0.001的精度。







