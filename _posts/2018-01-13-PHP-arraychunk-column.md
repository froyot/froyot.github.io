---
layout: post
title: PHP 数组函数array_chunk和array_column
category: PHP
comments: true
description: PHP 数组函数array_chunk和array_column知识点回顾
keywords: PHP,array_chunk,array_column
---

1、array_chunk($array,$size,$is_keep_key);将数组分割为size大小的数组块，如果$is_keep_key则保留原始的key,否则所有key从0开始,并返回一个二维数组。如果size大小不能整除，最后一个就是余数个大小的数组；如果size 大于等于原始数组，则将数组分割成一个数组，并组合成二维数组；

```
$age=array("P"=>"35","B"=>"37","J"=>"43","K"=>"53");
print_r(array_chunk($age,3,true));

```

输出内容:

```
Array
(
    [0] => Array
        (
            [P] => 35
            [B] => 37
            [J] => 43
        )

    [1] => Array
        (
            [K] => 53
        )

)

```

2、array_column,获取二维数组的某一列，或根据指定的列构建一个对应的键值对数组

```
$user=array(['id'=>1,'name'=>'k'],['id'=>3,'name'=>'d'],['id'=>2,'name'=>'Y']);
print_r(array_column($user,'id'));//返回[0=>1,1=>3,2=>2];

```
以上多用户数据库查询，假设查询出指定条件的用户数据，想要获取这一批用户数据的扩展用户信息，使用in查询，需要获取所有用户id

```
$user=array(['id'=>1,'name'=>'k'],['id'=>3,'name'=>'d'],['id'=>2,'name'=>'Y']);
print_r(array_column($user,'name','id'));//返回[1=>'k','3'=>'d','2'=>'Y']

```

上述查询，用于将多个不同维度的用户数据组合成一个数组，根据用户id作为键值，方便定位指定用户数据

#### 注意多为数组数据字段不一致的时候

```

$user=array(['id'=>1,'name'=>'k'],['id'=>3,'name'=>'d'],['id'=>2,'hh'=>'qq'],['name'=>'ss','qq'=>'h']);
print_r(array_column($user,'name','id'));//由于id=2的数据没有name字段，输出[1=>'k',3=>'d']

$user=array(['id'=>1,'name'=>'k'],['id'=>3,'name'=>'d'],['id'=>2,'hh'=>'qq'],['name'=>'ss','qq'=>'h']);
print_r(array_column($user,'name','id'));//由于第四个数组没有id,相当于数组没有指定key,PHP没有指定key数组赋值类似于$array[] = $data,所以输出[1=>'k','3'=>'d',4=>'ss'];

```

