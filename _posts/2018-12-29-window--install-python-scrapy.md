---
layout: post
title: windows python scrapy 安装
category: php
comments: true
description: windows python scrapy 安装
keywords: python,scrapy,twisted,lxml,whl
---

scrapy 依赖三个包，wheel,lxml,twisted。其中wheel 可以直接使用pip安装。其他两个需要vc编译工具。谁也不许因为装个10M不到的包去下好几个G的编译工具，因此需要去找编译之后的whl包，进行离线安装。

lxml.whl包比较容易找，https://pythonwheels.com/ 这个网站上能够找到。

twisted.whl 比较费劲，网上给出的地址只有这个 https://www.lfd.uci.edu/~gohlke/pythonlibs/#twisted ，但是这个下载地址已经是404了。经过多次查询，找到一个win64-py37的版本。

依赖包下载完之后，分别执行以下内容即可完成安装

```
pip install wheel
pip install lxml.whl
pip install twisted.whl
pip install scrapy
```