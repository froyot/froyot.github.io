---
layout: post
title: 不知不觉踩到PHP内存泄漏的雷
category: php
comments: true
description: 不知不觉踩到PHP内存泄漏的雷
keywords: php,内存泄漏,循环引用
---



最近工作上需要排查php频繁达到内存限制进程被杀掉的原因。项目中使用php写一个死循环，把mysql的数据同步到mq或者mongodb当中。内存问题主要出现在mq消息的发布上。项目中有使用到php-amqplib。

跟踪代码发现，循环内部，获取mq单例对象有问题导致每次循环都是new的一个mq对象。刚开始以为是这个原因导致内存不断增长。三下五除二就改完了，结果一试，没什么效果，还是不断飙升啊。

既然不是新对象引起的，那估计就是就对象的问题。因为新建对象都没有对已有的mq对象进行处理，例如端口连接，释放资源等。因此在新建对象之前，执行php-amqplib 中connection的close操作，关闭连接以及释放资源。关闭之后再操作，确实有些改变，飚的慢点，但是还是会飚。然后又在循环结束的时候unset对象，结果依然没什么变化。

只能接着看代码。php-amqplib中connection的属性中有一个channels属性，用于保存channel对象数组。然而这个channel对象本身又有一个connection属性，这样这两个对象之间就构成一个循环引用，当我们删除connection以及channel的时候，内部引用计数器不会到0，所以内存不会被释放。

用一下简化版说明一下其中的问题:

[!image](http://blog.static.aiaiaini.com/blog-2019-06-27-01.JPG)

```php

class Channel{
    protected $connection;
    function __construct($connection){
        $this->connection = $connection;
    }

    public function release(){
        $this->connection = null;
    }

}

class Connection{
    protected $channel;
    protected $data = null;
    function __construct(){
        $this->channel = new Channel($this);
        $this->data= '这里填充数据';
    }
    function __destruct(){
        $this->channel->release();
        $this->data = null;

    }
}



echo "start time ".date('Y-m-d H:i:s')."\n";
for($i=0;$i<20000;$i++){
    $ch = new Connection();
    $ch = null;
    if($i%100==0){
        echo date('Y-m-d H:i:s')." instance memory [".$i."]".(memory_get_usage()/1024)."k\n";
    }
}

```

按正常的逻辑，对象赋值null,那对象所占用的内存应该要被释放。上面的代码输出内容如下:

```sh

2019-06-17 13:23:28 instance memory [0]246.5546875k
2019-06-17 13:23:28 instance memory [300]8541.109375k
2019-06-17 13:23:28 instance memory [600]16867.6484375k
2019-06-17 13:23:28 instance memory [900]25162.1953125k
2019-06-17 13:23:28 instance memory [1200]33520.734375k
2019-06-17 13:23:28 instance memory [1500]41815.28125k
2019-06-17 13:23:28 instance memory [1800]50109.828125k
2019-06-17 13:23:28 instance memory [2100]58532.3671875k
2019-06-17 13:23:28 instance memory [2400]66826.9140625k
2019-06-17 13:23:28 instance memory [2700]75121.46875k
2019-06-17 13:23:28 instance memory [3000]83416.015625k
2019-06-17 13:23:28 instance memory [3300]91710.5546875k
2019-06-17 13:23:28 instance memory [3600]100005.09375k
2019-06-17 13:23:28 instance memory [3900]108299.6640625k
2019-06-17 13:23:28 instance memory [4200]116850.1953125k
2019-06-17 13:23:28 instance memory [4500]125144.7421875k
PHP Fatal error:  Allowed memory size of 134217728 bytes exhausted

```

可以看出，跑了4500次之后内存就已经操作128M了。如果Channel中没有connection的属性，则会有不一样的结果。我们把Channel的构造方法注释掉，再重新跑

```sh
start time 2019-06-17 13:34:34
2019-06-17 13:34:34 instance memory [0]218.8046875k
2019-06-17 13:34:34 instance memory [300]218.8125k
2019-06-17 13:34:34 instance memory [600]218.8125k
2019-06-17 13:34:34 instance memory [900]218.8125k
2019-06-17 13:34:34 instance memory [1200]218.8125k
2019-06-17 13:34:34 instance memory [1500]218.8125k
2019-06-17 13:34:34 instance memory [1800]218.8125k
2019-06-17 13:34:34 instance memory [2100]218.8125k
2019-06-17 13:34:34 instance memory [2400]218.8125k
2019-06-17 13:34:34 instance memory [2700]218.8125k
2019-06-17 13:34:34 instance memory [3000]218.8125k
2019-06-17 13:34:34 instance memory [3300]218.8125k
2019-06-17 13:34:34 instance memory [3600]218.8125k
2019-06-17 13:34:34 instance memory [3900]218.8125k
2019-06-17 13:34:35 instance memory [4200]218.8125k
2019-06-17 13:34:35 instance memory [4500]218.8125k


```

只是一个简单的修改，循环就没有内存的问题了。

问题的根本就是对象之间循环引用。有个很有趣的现象，如果对象之间构成循环引用，在xdebug中就可以看到一个无限的树状对象。Connection->Channel->Connection->Channel....

对于普通的web应用而已，一般不会有什么问题，每次请求结束之后fpm会释放掉。但是对于cli应用，这就是致命的。基本上跑个一天就挂了。

但是，现实就是这样。对象之间相互引用很容易出现。这个model需要那个model,几个model之间也很容易构成一个回环。同时，很多东西需要引用第三方类，没办保证第三方类没有相互引用。那有没有不改类之间引用可以解决的呢？

在这次排查，我使用的是```gc_collect_cycles()```强制执行gc操作，释放内存。还是第一段程序代码，循环内容改为一下内容:

```php

echo "start time ".date('Y-m-d H:i:s')."\n";
for($i=0;$i<20000;$i++){
    $ch = new Connection();
    $ch = null;
    gc_collect_cycles();
    if($i%100==0){
        echo date('Y-m-d H:i:s')." instance memory [".$i."]".(memory_get_usage()/1024)."k\n";
    }
}
```

输出内容如下:

```sh

start time 2019-06-17 13:43:41
2019-06-17 13:43:41 instance memory [0]219.125k
2019-06-17 13:43:41 instance memory [300]219.1328125k
2019-06-17 13:43:41 instance memory [600]219.1328125k
2019-06-17 13:43:41 instance memory [900]219.1328125k
2019-06-17 13:43:41 instance memory [1200]219.1328125k
2019-06-17 13:43:41 instance memory [1500]219.1328125k
2019-06-17 13:43:41 instance memory [1800]219.1328125k
2019-06-17 13:43:41 instance memory [2100]219.1328125k
2019-06-17 13:43:41 instance memory [2400]219.1328125k
2019-06-17 13:43:41 instance memory [2700]219.1328125k
2019-06-17 13:43:41 instance memory [3000]219.1328125k
2019-06-17 13:43:41 instance memory [3300]219.1328125k
2019-06-17 13:43:41 instance memory [3600]219.1328125k
2019-06-17 13:43:41 instance memory [3900]219.1328125k
2019-06-17 13:43:41 instance memory [4200]219.1328125k
2019-06-17 13:43:41 instance memory [4500]219.1328125k

```

内存飙升的问题解决了。



网上很多描述都是php5.3之后的gc会自动回收类似这类的垃圾，但是前提是zend节点满了。但实际上,说的只是数组类型。下面的代码在循环结束之后，局部变量data的资源会得到释放。

```php

echo "start time ".date('Y-m-d H:i:s')."\n";
for($i=0;$i<20000;$i++){
    $data = ['a'=>'11111111111111111111111111'];
    $data['b'] = &$data;
    if($i%300==0){
        echo date('Y-m-d H:i:s')." instance memory [".$i."]".(memory_get_usage()/1024)."k\n";
    }
}
```


总的而言，PHP在一些长时间的循环运行当中，一定要小心对象之间相互引用造成内存上升的问题。如果遇到内存上升问题，可以先看看代码当中有没有什么类之间存在循环引用。平时写代码的时候也需要尽量避免对象之间构成循环引用，避免在不经意之间给自己或团队挖个坑。