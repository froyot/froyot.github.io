---
layout: post
title: PHP 通过搜狗搜索 抓取微信公众号数据
category: Developer
comments: true
description: "PHP,抓取微信,微信公众号."
---


微信公众号的文章抓取一直以来由于微信本身的封闭性抓取比较困难。好在现在搜狗搜索
可以搜索微信数据。因此用PHP进行实现数据的抓取。

PHP数据抓取的思路无非是curl请求然后正则匹配，然后获取数据。顶多再添加一个cookie，
处理301，302跳转。如果以上还是无法获取，那就说明，真是搞不定了。

造出这个轮子，有一下收获：

*   首先现阶段，很多网页是通过前端模板进行渲染的，因此，如果想从页面中获取js渲染
之后的数据，那PHP是搞不定了，这就需要用node.js 的cheerio或其他js库进行处理。

*   curl中允许跳转，需要设置参数CURLOPT\_FOLLOWLOCATION ，但是!!!!

> Warning: curl_setopt() [function.curl-setopt]: CURLOPT_FOLLOWLOCATION cannot
> be activated when safe_mode is enabled or an open_basedir is set in
> /home/xxx/public_html/xxx.php on line 56

,所以不是每次都可以让你设置CURLOPT\_FOLLOWLOCATION。如果不能设置这个值，就需要对
返回头进行判断，判断是否是301，或302，并获取跳转地址。可以设置CURLOPT_HEADER参数
为true,让请求头作为数据流返回，从返回中采用正则方式匹配跳转后的地址。对于返回
code的判断，则可以通过curl_getinfo($ch, CURLINFO_HTTP_CODE)获取。

*   如果在header中添加一个Cookie头，同时，请求的时候添加了cookie文件，那么发出去
的请求中包含两个cookie的内容。

*   sleep函数在一些主机上，处于安全原因是不允许使用的！！！！





　　　　
