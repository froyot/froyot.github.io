---
layout: post
title: gitbook 安装
category: tools
comments: true
description: "gitbook 安装"
---

## gitbook 安装折腾
gitbook 是github中比较流行用来记录分享的工具，也是写工作文档非常好的工具。
因此打算折腾一番。

[GitBook Github地址](https://github.com/GitbookIO/gitbook)给出的安装使用步骤:

```
npm install gitbook-cli -g

gitbook inti

gitbook build

gitbook serve

```


如果没有墙，上面的步骤都没错！！！

*   如果不采用国内镜像，install的时候回出现网络超时。
*   如果使用alias安装的镜像，比如添加一个cnpm命令采用国内镜像，是可以安装，但是当
执行gitbook init命令的时候会自动执行install laste版本的gitbook。因此alias方式的国内
镜像会是的gitbook init失败

因此，最好是添加全局镜像
npm config set registry https://registry.npm.taobao.org


