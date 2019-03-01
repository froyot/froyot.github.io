---
layout: post
title: 面试不知道Redis和memcache
怎么行
category: 工具
comments: true
description: 面试不知道Redis和memcache
怎么行
keywords: Redis,memcache
,面试
---

过完年，又到了每年的跳槽季了。作为一个PHP程序员，你的知识储备够了吗？一般做开发，基本上都会用到redis或者memcache
吧。那为什么用redis又为什么用memcache
呢?


先上一张图吧

![redis memcache diffrent](http://blog.static.aiaiaini.com/blog/201902/5a95184c0001597707420419.png)

<!-- more -->

### 出发点

memcache
是内存型的缓存服务。memcache
主要为了提供缓存服务，所有数据都存储在内存之中。因此不涉及到数据的持久化。

redis是基于内存的非关系型数据库。redis是一个数据库，将所有数据载入在内存(内存足够的情况)，数据更新后通过异步的方式将数据持久化到磁盘当做。

### 数据类型

memcache
 是key-value缓存服务，支持字符串类型，二进制类型(新版本)。如果存储其他类型数据，需要先对数据进行序列化再存储。

redis支持string,list,set,hash,sort set(zset)五种类型

### IO
memcache
 是多线程非阻塞IO网络操作。分为网络监听主线程，工作子进程。使用libevent作为事件模型

redis 是单进程单线程IO网络操作。使用内部封装epoll,select。因为redis是单线程，内部较大的运算会阻塞io

### 内存分配


#### memcache
  Slab Allocator

memcache
 默认采用Slab Allocator的机制分配、管理内存。memcache
申请内存的单元是page,每个page大小固定，默认1M。page下面，使用一系列slub 管理一定大小区间的存储单元(比如slub1管理0byte-120byte的存储单元)，数据存储单元chunk,用于存放真实数据。chunk的大小是分配一个page的时候划分的一系列不同大小的存储空间。当需要存储数据的时候，找到管理制定大小的slub，然后选择可以存储数据单元的最小的chunk进行存储。

![chunk 保存数据](http://blog.static.aiaiaini.com/blog/201902/2e7d606bc2eee5f66e4476f1b0d4670f86adf5a5e9c9.png)

比如一个数据大小100byte，slub1存储大小88-184byte,有88,112,144,184四个大小的chunk。选取112 byte的chunk进行存储。剩余的12byte无法在存储其他数据，无法再使用。

Slab Allocator有利于减少内存碎片和频繁分配销毁内存所带来的开销，固定的chunk大小，会带来一定的内存浪费。


#### redis 内存分配



Redis采用的是包装的mallc/free，redis数据块的大小根据数据类型进行分配。在分配一块内存之 后，会将这块内存的大小存入内存块的头部。在内存释放的过程中，通过获取数据库的指针以及数据块的大小获得数据的指针然后释放内存。

Redis使用现场申请内存的方式来存储数据，并且很少使用free-list等方式来优化内存分配，会在一定程度上存在内存碎片。


### 数据一致性保证

redis使用的是事务(假事务,单线程模型，保证了数据按顺序提交)保证数据的一致性。

memcache需要使用cas保证数据一致性。memcache
 1.2.5以及更高版本，提供了gets和cas命令。CAS（Check and Set）是一个确保并发一致性的机制，属于“乐观锁”范畴；原理很简单：拿版本号，操作，对比版本号，如果一致就操作，不一致就放弃任何操作。

### 集群方式

大型应用往往需要多台的redis或memcache。

redis 内部实现了master-slave(主从)机制，可以使用该协议实现集群，扩容。

memcache 需要客户端实现集群。（先hash出服务器id,在获取缓存）


所有区别可以使用下面表格说明

![redis memcached diffrend](http://blog.static.aiaiaini.com/blog/201902/5a95185800014f7d06210378.png)


说了这么多，开发过程中如何选择？

*	如果只是字符串类型的缓存数据，那么使用memcache

*	需要持久化的数据，需要存储多种数据类型，使用redis


参考文档[脚踏两只船的困惑 - Memcached与Redis](https://www.imooc.com/article/23549)










