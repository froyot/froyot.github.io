---
layout: post
title: 读Yii2框架的web返回格式化类Response
category: PHP
comments: true
description: 读Yii2框架的web返回格式化类Response。阅读源码补充知识，了解如何编写一个Response类。
keywords: Yii2,http返回码,web响应输出,文件下载
---


一个完整的网络请求，最后都需要一个符合协议的返回。Yii2在处理web请求之后，统一通过web/Response处理返回。错误也会经过错误处理返回一个Response。

#### 一个Response完整的流程有哪些?
*   创建Response对象，设置Resonse响应格式json,html,xml等
*   触发前置事件,暴露操给开发者在输出前对数据进行调整等
*   数据格式化。将所有response的内容更加输出格式转换成响应的字符串，并确定http返回码。
*   设置响应头。输出所有自定会返回头和标注http协议返回头。
*   输出内容。将字符串内容输出，并刷新缓冲区
*   触发后置事件。触发Response后置操作
*   数据清理

<!-- more -->

#### Yii2的Response

*   http状态码明确。在web/Response 文件中定义了状态码数组，几乎涵盖了所有的http状态码，并给出了标注的状态码文字说明。如果想了解http状态码，看Yii2的Response文件就足够了。
*   支持多种方式文件输出。sendFile 下载文件，sendContentAsFile将内容以文件的方式发送给客户端，xSendFile文件下载。
*   发送文件每次最多读取8M数据，防止占用过大内存
*   数据格式，支持多种数据格式html,json,xml并可以指定ResponseFormatter对数据格式进行扩展
*   Cookie处理，Yii2的请求Cooke由Request处理，但是响应Cookie由Response处理。这个相对于Thinkphp5 以及其他框架而言不一样。但是这种分工明确的设计却又很清晰明了。

总的而言，Yii2的Response 代码逻辑结构相当清晰，而且输出内容都非常规范的遵循http协议规范。同时提供前置事件，数据准备前置事件，后置事件给开发者在不同的情况下处理额外的数据。代码在阅读起来非常明了。从头到尾，完整的看一遍，就可以完全理解。以下是Response中的两段代码。

##### 输出内容代码
```php
    protected function sendContent()
    {
        if ($this->stream === null) {
            echo $this->content;

            return;
        }
        set_time_limit(0); // Reset time limit for big files
        $chunkSize = 8 * 1024 * 1024; // 8MB per chunk
        if (is_array($this->stream)) {
            list ($handle, $begin, $end) = $this->stream;
            fseek($handle, $begin);
            while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
                if ($pos + $chunkSize > $end) {
                    $chunkSize = $end - $pos + 1;
                }
                echo fread($handle, $chunkSize);
                flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
            }
            fclose($handle);
        } else {
            while (!feof($this->stream)) {
                echo fread($this->stream, $chunkSize);
                flush();
            }
            fclose($this->stream);
        }
    }
```
没有什么特殊的。如果是简单的字符串，直接echo。主要看它处理stream的情况。

首先设置超时时间。对于读取文件流，没办法确定文件读取需要的时间，因此设置超时时间很必要。

设置最大读取长度。每个请求都需要占用一定的内存去处理数据。为了避免我限制申请内存造成php程序报内存不足，因此对于文件读取程序，必须设置读取限制。读取完及时刷新出去。

##### 下载文件请求头设置
```php
public function setDownloadHeaders($attachmentName, $mimeType = null, $inline = false, $contentLength = null)
    {
        $headers = $this->getHeaders();

        $disposition = $inline ? 'inline' : 'attachment';
        $headers->setDefault('Pragma', 'public')
            ->setDefault('Accept-Ranges', 'bytes')
            ->setDefault('Expires', '0')
            ->setDefault('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->setDefault('Content-Disposition', $this->getDispositionHeaderValue($disposition, $attachmentName));

        if ($mimeType !== null) {
            $headers->setDefault('Content-Type', $mimeType);
        }

        if ($contentLength !== null) {
            $headers->setDefault('Content-Length', $contentLength);
        }

        return $this;
    }
```
想要输出一个下载文件的响应，Yii2的输出请求头中有以下内容:

*   Pragma:public 非必须
*   Expires:0 非必须
*   Cache-Control:must-revalidate, post-check=0, pre-check=0 非必须
*   Content-Disposition:文件名必须
*   Accept-Ranges:bytes 必须
*   Content-Type:文件mime 必须
*   Content-Length:文件长度 必须 

设置完请求头之后就可以把内容输出。浏览器就会弹出一个下载提示框。

