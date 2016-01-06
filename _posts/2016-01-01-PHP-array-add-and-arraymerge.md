---
layout: post
title: PHP 数组相加与array_merge的区别
category: PHP
comments: true
description: "PHP 数组相加与array_merge的区别"
---

## PHP 数组相加与array_merge的区别
PHP合并数组可以使用"+"号或者array_merge函数。那么这两个不同的方式有什么区别呢？

*   数字键名的情况

```php
$a = ["php","java"];
$b = ["c++","python","vb"];

var_dump($a+$b);//输出["php","java","vb"]
var_dump(array_merge($a,$b));["php","java","c++","python","vb"]

```
数字键的时候，两个数组相加，如果键名相同，留先出现的，后面的不要。array_merge是吧两个数组完全合并在一起

*   字符串键名的情况

```php
$a = ["name"=>"Lili","sex"=>0];
$b = ["name"=>"LiHao","sex"=>1,"age"=>22];
var_dump($a+$b);//输出["name"=>"Lili","sex"=>0,"age"=>22]
var_dump(array_merge($a,$b));//输出["name"=>"LiHao","sex"=>1,"age"=>22];
```
字符串键名数组相加也是取首先出现的数组做最后的结果。array_merge是后面的数据覆盖前面的数据。
