---
layout: post
title: API Cloud PHP SDK个人版
category: 技术
comments: true
description: "API Cloud PHP SDK,使用apicloud的数据库作为自己项目的存储环境."
---


## API Cloud PHP SDK
因为自己服务器资源有限，所以想要使用apicloud的数据库作为自己项目的存储环境。
由于之前有些混乱事宜，一直搁置。今日稍有点空闲时间，所以重新开始。apicloud的网站上
只有node,python,java,c#的代码，竟然没有php的代码。所以，还得码农亲力亲为。

不得不说，apicloud的restapi做的确实不是很好。删除用户只能登陆用户自己删除，这个
逻辑让我百思不得其姐。什么样的场景下，用户会自己删除自己。

项目中主要内容就是接口调用，没什么值得说的。比较有意思的是将Yii2中的QueryBuilder
方式用于apicloud的查询参数的创建当中。Yii2的QueryBuilder在各个设置函数中都讲本身
对象返回，以实现php对象的连贯操作。同时在QueryBuilder的build函数中，采用递归的方
式构建查询条件。

[api cloud php sdk 下载地址](https://github.com/froyot/apicloud_php)
