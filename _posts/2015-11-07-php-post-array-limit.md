---
layout: post
title: PHP POST 数组限制
category: 技术
comments: true
description: "PHP, POST, Array limit, max_input_var"
---


## PHP POST 数组限制
今天调一个接口，测试批量上传数据。上传数据用的是POST方式，分成一个多维数组上传。
但是，问题来了，最多可以批量上传多少条数据？PHP默认POST数据的限制是2M,但是并不代
表你就可以真的传2M以内的任意长度的数组。

PHP对输入变量是有限制的。默认php.ini里的max\_input\_vars的限制是1000.如果POST一个二维数组，每个二维子数组里有五个元素，那么，只能POST200个子数组。那
么多余的整么办？直接抛掉。。。。。真是简单粗暴。那么抛掉的策略是怎么样的呢？比如我POST上述数组210个，另外还POST一个变量type,那么服务器的$_POST参数里有啥？

一下数据是通过PHP CURL方式提交的，其他语言提交之后数据顺序``不一定不变``

```php
$data['ztype'] = "ztype";
$data['aber'] = "aber";
$data['User'] = [];
for($i=0;$i<290;$i++)
{
    $data['User'][] = [
        'create_time'=>1,
        'health_action_cat_id' => '5',
        'detail' => '{"distance":44}',
        'uid' => '5089',
        'provider_id'=>$i,
    ];
}
```

如果提交的数组是这样的,那么，服务器的$_POST是这样的:

```
{
    "ztype":"ztype",
    "aber":"aber",
    "User": [0-198]
}
```

那如果顺序变一下呢,

```php
$data['User'] = [];
for($i=0;$i<290;$i++)
{
    $data['User'][] = [
        'create_time'=>1,
        'health_action_cat_id' => '5',
        'detail' => '{"distance":44}',
        'uid' => '5089',
        'provider_id'=>$i,
    ];
}
$data['ztype'] = "ztype";
$data['aber'] = "aber";
```

服务器的$_POST是这样的:

```
{
    "User": [0-199]
}
```

额，这个...难怪容易出现诡异的情况。像其他语言(比如java)，提交之后数据顺序是不确定的，所以，所以就会出现数据混乱，在获得1000个变量之后，PHP将抛弃后面的输入。如果下一个数组大小加起来已经超过1000，PHP也会果断抛弃....




