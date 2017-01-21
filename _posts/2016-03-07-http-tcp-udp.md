---
layout: post
title: http,tcp,udp协议
category: 基础
comments: true
description: "http,tcp,udp协议"
---


*   tcp协议是面向连接的传输层网络协议
*   tcp数据传输之前需要与接收方建立连接，进行三次握手之后才可以传输数据
*   tcp传输是可靠的,因为数据传输之前，发送接收方需要建立连接，进行数据传输同步，应用于大量数据传输的场景，传输速度慢
*   tcp连接是有状态的，长连接，除非网络中断或主动断开，连接才会中断
*   tcp,客户端向服务器发送syn(syn=j)进入SYN\_SEND状态,服务器回复ACK(ack=j+1),同时发送SYN(syn=k)进入SYN\_RECV状态,客户端发送ACK(ack=k+1),进入ESTABLISTION状态

## udp协议
*   udp也是属于传输层网络协议
*   udp协议是非面向连接的，传输数据之前不需要与接收方建立连接
*   udp传输不可靠，应用于少量数据传输的场景，传输速度快

## http协议
*   http协议是建立在tcp协议基础之上的应用层网络协议
*   http连接是无状态的，短连接，Http是一个基于请求/响应模式的、无状态的协议，每次请求完之后，连接就关闭
*   http连接，请求-响应式消息发送，服务器数据需要客户端请求才能发送到客户端。

## HTTP的Request/Response：

先看Request 消息的结构,Request 消息分为3部分

*   第一部分叫Request
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

*   第二部分叫Response

第一部分叫request line, 第二部分叫request header，第三部分是body

request line：协议版本、状态码、message

*   第二部分叫Response header
>   Location响应报头域用于重定向接受者到一个新的位置。Location响应报头域常用在更换域名的时候。
>   Server响应报头域包含了服务器用来处理请求的软件信息。与User-Agent请求报头域是相对应的。
>   Content-Encoding用于记录文档的压缩方法
>   Content-Language实体报头域用于指明实体正文的长度，以字节方式存储的十进制数字来表示。
>   Content-Type实体报头域用语指明发送给接收者的实体正文的媒体类型
>   Last-Modified实体报头域用于指示资源的最后修改日期和时间。
>   Expires实体报头域给出响应过期的日期和时间。

*   http code

```
"200" : OK

"201" : Created 已创建

"202" : Accepted 接收

"203" : Non-Authoritative Information 非认证信息

"204" : No Content 无内容

"205" : Reset Content 重置内容

"206" : Partial Content 部分内容

重定向

"300" : Multiple Choices 多路选择

"301" : Moved Permanently  永久转移

"302" : Found 暂时转移

"303" : See Other 参见其它

"304" : Not Modified 未修改

"305" : Use Proxy 使用代理

"307" : Temporary Redirect

客户方错误

"400" : Bad Request 错误请求

"401" : Unauthorized 未认证

"402" : Payment Required 需要付费

"403" : Forbidden 禁止

"404" : Not Found 未找到

"405" : Method Not Allowed 方法不允许

"406" : Not Acceptable 不接受

"407" : Proxy Authentication Required 需要代理认证

"408" : Request Time-out 请求超时

"409" : Conflict 冲突

"410" : Gone 失败

"411" : Length Required 需要长度

"412" : Precondition Failed 条件失败

"413" : Request Entity Too Large 请求实体太大

"414" : Request-URI Too Large 请求URI太长

"415" : Unsupported Media Type 不支持媒体类型

"416" : Requested range not satisfiable

"417" : Expectation Failed

服务器错误

"500" : Internal Server Error 服务器内部错误

"501" : Not Implemented 未实现

"502" : Bad Gateway 网关失败

"503" : Service Unavailable

"504" : Gateway Time-out 网关超时

"505" : HTTP Version not supported  HTTP版本不支持
```


