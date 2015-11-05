---
layout: post
title: Vue.js router 切换后执行某个操作
category: 技术
comments: true
---


## Vue.js router 切换后执行某个操作
Vue.js 是一个MVVM前端框架，这几天拿来做自己的小东西，学习当中。遇到一个问题.当使用router的时候，如何在切换之后执行页面初始化的操作。其他想page.js的路由都
有callback之类的回调函数，但是vue-router好像没有。

于是在网上搜索了半天，终于有了一个稍微可行的方案。

vue-router注册的是一个个个的vue-component,在注册的时候可以配置一些属性。
vue-router提供的属性都是在页面被创建之前执行的，而很多时候，比如需要对一些页面
元素绑定某些类似于slider的插件，就必须在页面创建之后才能给通过
document.getElementById获得该元素。既然router文档中提供的配置属性没办法实现，那么vue-component本身呢。
stackoverflow里有提到过component的几个属性：

*   created:function(){},在这里面还是无法获取页面元素

*   attached：function(){},到这里才能获取！！！！

所以，可以把单纯的数据初始化放置在created里面，而把需要操作页面dom的放置在attached


