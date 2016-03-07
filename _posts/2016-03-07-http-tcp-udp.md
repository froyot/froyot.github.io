---
layout: post
title: http,tcp,udp协议
category: 基础
comments: true
description: "http,tcp,udp协议"
---

##1. tcp协议
*   tcp协议是面向连接的传输层网络协议
*   tcp数据传输之前需要与接收方建立连接，进行三次握手之后才可以传输数据
*   tcp传输是可靠的,因为数据传输之前，发送接收方需要建立连接，进行数据传输同步，应用于大量数据传输的场景，传输速度慢
*   tcp连接是有状态的，长连接，除非网络中断或主动断开，连接才会中断

## udp协议
*   udp也是属于传输层网络协议
*   udp协议是非面向连接的，传输数据之前不需要与接收方建立连接
*   udp传输不可靠，应用于少量数据传输的场景，传输速度快

## http协议
*   http协议是建立在tcp协议基础之上的应用层网络协议
*   http连接是无状态的，短连接，Http是一个基于请求/响应模式的、无状态的协议，每次请求完之后，连接就关闭

## HTTP的Request/Response：

先看Request 消息的结构,Request 消息分为3部分

*   第一部分叫Request line
```
GET http://www.cnblogs.com/ HTTP/1.1
```
Request line 指明请求方式，请求地址，以及http协议版本


*   第二部分叫Request header

>   Accept: text/html
    指明浏览器端可以接受的媒体类型,Accept: */*  代表浏览器可以处理所有类型

>   Referer:http://translate.google.cn/?hl=zh-cn&tab=wT
    提供了Request的上下文信息的服务器，告诉服务器我是从哪个链接过来的

>   Accept-Language: en-us
    作用： 浏览器申明自己接收的语言。

>   Content-Type: application/json
    作用： 浏览器申明请求数据的格式。

>   Accept-Encoding: gzip
    浏览器申明自己接收的编码方法，通常指定压缩方法，是否支持压缩，支持什么压缩

>   User-Agent
    告诉HTTP服务器， 客户端使用的操作系统和浏览器的名称和版本

>   Connection: keep-alive
    当一个网页打开完成后，客户端和服务器之间用于传输HTTP数据的TCP连接不会关闭,
    如果close,则请求之后立即关闭

>   Content-Length:888
    作用：发送给HTTP服务器数据的长度。

>   Host（发送请求时，该报头域是必需的）
    请求报头域主要用于指定被请求资源的Internet主机和端口号，它通常从HTTP URL中提取出来的

