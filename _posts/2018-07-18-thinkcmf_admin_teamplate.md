---
layout: post
title: 创建自己的 Thinkcmf 后台模板
category: PHP
comments: true
description: 自定义ThinkCmf后台模板
keywords: ThinkCmf,PHP,后台模板 material
---

由于工作原因，项目的后台都是采用Thinkcmf搭建。但是看了那么久的默认样式，还是觉得有点不喜欢。因此想自己套一套主题上去，弄个漂亮点的界面效果。
经过两天的努力，终于在[material-dashboard](https://www.creative-tim.com/product/material-dashboard) html后台模板的基础，结合thinkcmf官方bootstrap3 的模板修改出一套material模板。

### 修改过程比较麻烦的事

*   thinkcmf的所有模板都是以引入的方式，引入header,而没有使用thinkphp的模板继承。这种方式，当需要在全部页面添加一个公共的动东西，比如一个隐藏控件，操作起来比较麻烦，需要修改所有的文件。

*   iframe左侧菜单的输出需要根据模板进行修改，html结构改动比较大。

*   继承的继承模板创建好之后，需要一个个的去修改原有页面的代码，添加模板继承，将不需要改动的地方放入content block 中

### thinkcmf-admin-materialdashboard 介绍


*   thinkcmf-admin-materialdashboard 已前端模板[material-dashboard](https://www.creative-tim.com/product/material-dashboard) 作为基础，结合thinkcmf官方bootstrap3 的模板修改而来。模板并不是单纯修改css样式，改动了页面html结构，所以**请勿直接覆盖项目模板**


*   thinkcmf-admin-materialdashboard 模板采用thinkphp模板继承的方式，改变官方默认模板引入公共头文件的方式，方便样式修改，以及后期改为页面形式而不是iframe的方式

*   改动php主要是admin/index/index中显示菜单的php代码

*   所有页面，如果需要在模板中定义php函数，请在phpscript block中操作，否则定义无效！！！

*   在admin/index/index中添加了一项demo菜单，用于给出表格，图表，表单页面的模板实例，记得删除

### 界面效果显示

![chart teamplate](http://froyoimg.static.aiaiaini.com/cmf_chart.png) 

![table teamplate](http://froyoimg.static.aiaiaini.com/cmf_table.png)

![form teamplate](http://froyoimg.static.aiaiaini.com/cmf_form.png)

![setting teamplate](http://froyoimg.static.aiaiaini.com/cmf_setting.png)

### 使用方式

*   下载文件，解压到public\themes\目录下
*   在配置文件中设置```cmf_admin_default_theme=>admin_materialdashboard```

模板地址[https://gitee.com/froyo/thinkcmf-admin-materialdashboard](https://gitee.com/froyo/thinkcmf-admin-materialdashboard)





