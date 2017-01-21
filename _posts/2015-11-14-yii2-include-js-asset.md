---
layout: post
title: Yii2 中使用registerJsFile引入asset发布的js文件
category: PHP
comments: true
description: "Yii2 use registerJsFile include file in asset director"
---



Yii2 提供AssetManage对一些不对外的目录的静态文件发布到可访问的asset目录中。如果
所有的静态文件都通过AssetBundle的方式进行引入，那倒没什么值得说的了。不过像js文
件，由于依赖关系，有时候不得不在文件开头就引入某个js文件，同时其他js文件依然在文
件底部加载。这个时候我们不得不在模板或者视图文件中使用registerJsFile对js文件进行
引入。

但是，在视图或者模板文件中，如果我们想直接引入web目录下的js文件直接采用别名@web
就可以，但是如果想要访问asset目录呢？
假设模板文件中，注册了AssetBundle $bundel, 采用下面的方式引入改AssetBundle 中发
布的asset 静态文件

```php

<?php
    $manager = $this->getAssetManager();
    $url = $manager->getAssetUrl($bundel,'js/vue.min.js');
    $this->registerJsFile($url,['position'=>yii\web\View::POS_HEAD]);
?>

```


