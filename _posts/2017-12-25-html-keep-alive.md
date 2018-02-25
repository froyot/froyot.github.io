---
layout: post
title: Http中的connection:keep-alive
category: 基础
comments: true
---
http请求中的keep-alive 有什么用，怎么用？

*	http请求

一个典型的http请求:
```
GET / http/1.1
Host: www.baidu.com
Connection:keep-alive
Catch-control:no-cache
Pragma:no-cache
Accept:*/*
Accept-Language:zh-cn
Accept-encoding:gzip, deflat, br
User-Agent:Chrome/5.0
Cookie:BAIDUID=sksjdjjjjjjjj


t=12&q=222
```

http属于短连接形式，无状态的。每次请求，建立tcp链接之后从服务器获取数据之后断开tcp链接。再次请求再重复执行上面的操作。


*	keep-alive有什么用

为了尽可能的减少http请求建立的连接数，http协议实现了keep-alive，用于多个http请求复用tcp连接。以往打开网页，需要连续执行十几个http请求需要建立十几个tcp连接，而有了keep-alive之后，十几个http请求通过复用tcp连接，只需要3-8个tcp连接就能实现，具体个数视服务器处理速度而定，客户端请求不是并发请求的时候效果最佳。

对比一下当服务端开启keep-alive支持之前和之后的连续三个http请求数据抓包对比：

未开启keep-alive 3次访问相同也没 tcp请求数据


![未开启keep-alive](http://p4ou67wbp.bkt.clouddn.com/e84c5effa4ebe2c2ab3c37b4e69bbd56.png)


未开启keep-alive 3次访问相同也没 tcp请求数据


![开启keep-alive](http://p4ou67wbp.bkt.clouddn.com/f1408f5b0fcba680c20653eb086e292c.PNG)

从两张图的对比可以知道，开启keep-alive的时候，连续三个http请求复用了同一个tcp连接。而没有开启keep-alive的3次请求，每次请求之后都会断开之前的连接，不会复用。对服务器而言，回尽可能的复用tcp，所以当一个活跃的tcp请求客户端断开之后，服务器端会进入TIME_WAITE状态，因此开启keep-alive也能够减少服务器TIME_WAITE的数目。


*	keep-alive怎么用

虽然keep-alive可以复用tcp连接，但是同时也会长时间挂起这个连接不会释放，等待下次请求复用，等待时间是KeepAliveTimeout, Apache中配置文件的描述:

>KeepAliveTimeout: Number of seconds to wait for the next request from the
>same client on the same connection.

从上面开启keep-alive的截图中可以看出，tcp连接最终的断开时间是在最后一个请求KeepAliveTimeout后都没有新请求过来，服务端就会断开该连接。但是KeepAliveTimeout得时间长短会影响服务器的并发请求数。假设极端情况下KeepAliveTimeout设置为1000s，服务器最大可以建立的tcp连接65535,系统最大支持访问人数限制在65535/1000,即65。因为每个连接占用1000s，服务器在1000s内不会释放该tcp连接，从而导致无法处理新的请求，这种情况如果遇到syn flood攻击的话，会立即崩溃。

所以keep-alive开启，以及超时时间的设置很关键，时间设置的太短，退化成connection:close的情况。时间太长，则会拖垮整个系统。


