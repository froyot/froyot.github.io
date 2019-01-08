---
layout: post
title: Yii2和thinkphp5中一个微小的差异造成bug的根源
category: php
comments: true
description: query类在Yii2和thinkphp5的实现差异。
keywords: Yii2,thinkphp5,query类
---


考虑一个场景，一个函数需对相同表进行多次查询，多次查询中有部分查询条件相同。对于这种情况，Yii2和thinkphp5的实现方式要格外小心。在Yii2中，可以直接使用``clone`` 复用共同的查询条件，但是thinkphp5的话，必须把相同条件再重复写一次。

例如，需要查询总有效文章数，以及今日发布有效文章数。

Yii2 版本

```php

$query = Post::find();
$query->where(['status'=>1,'is_delete'=>0]);
$todayquery = clone $query;
$todayquery->andFilterWhere(['between','create_at',$start_date, $end_date])
$totalcount = $query->count();
$todaycount = $todayquery->count();

```

<!-- more -->

thinkphp5版本

```php

$post = new Post();
$post->where(['status'=>1,'is_delete'=>0]);
$totalcount = $query->count();

$post = new Post();
$post->where(['status'=>1,'is_delete'=>0]);
$post->where('create_at','between time',[$start_date, $end_date]);
$todaycount = $todayquery->count();

```

如果在thinkphp5中使用clone会发生什么？

*	1 clone model


```php

$post = new Post();
$post->where(['status'=>1,'is_delete'=>0]);
$t = clone $post;
$t->where('create_at','between time',[$start_date, $end_date]);
$totalcount = $post->count();
$todaycount = $t->count();

```

执行过程没有报错，但是实际上是否真的正确呢？看一下执行的语句:

```sql

SELECT COUNT(*) AS tp_count FROM `test` WHERE  `status` = 1  AND `is_delete` = 0  AND `create_at` BETWEEN 1539619200 AND 1539705600 LIMIT 1
SELECT COUNT(*) AS tp_count FROM `test` LIMIT 1

```
查询最终的执行时通过model类中的getQuery()方法获得的query对象执行的。所有的查询条件最终都绑定在query对象当中。

```php
    public function getQuery($buildNewQuery = false)
    {
        if ($buildNewQuery) {
            return $this->buildQuery();
        } elseif (!isset(self::$links[$this->class])) {
            // 创建模型查询对象
            self::$links[$this->class] = $this->buildQuery();
        }

        return self::$links[$this->class];
    }

```

可以看出，clone model 之后，内部query其实还是同一个。虽然是在clone出来不同的两个model添加查询条件，但是最终都是添加在相同的query当做。
所以第一条语句就会有所有的查询条件。第二条语句没有任何条件的原因是因为query执行完之后，会把查询条件情空。



*	clone query

既然clone model不行，那直接clone内部query呢？

```php
$query = (new Post())->getQuery();
$query->where(['status'=>1,'is_delete'=>0]);
$t = clone $query;
$t->where('create_at','between time',[$start_date, $end_date]);
$totalcount = $query->count();
$todaycount = $t->count();

```

执行过程,抛出SQLSTATE[HY000]: General error: 2031错误信息，看看内部解析成什么样的语句了:

```sql

SELECT count(*) FROM `test` WHERE `status` = 1 AND `is_delete` = 0
SELECT count(*) FROM `test` WHERE `status` = :where_AND_status AND `is_delete` = :where_AND_is_delete AND `create_at` BETWEEN :where_AND_create_at_between_1 AND :where_AND_create_at_between_2

```
初步认为是参数没有绑定上去。应该也是query内部引用了一个对象，对象在clone之后与原有对象是一个地址引用。通过一步一步断点输出，确认在```$this->builder->select($options);```之后获得了bind数据。因此只需要解绑clone前后对象的builder属性即可完成query对象的复制。查看query对象的属性，只有builder,connection是对象，但是connection我们希望在整个请求中是一个单实例，所以没必要区分。

最终修改,新建query子类，添加__clone方法,指定clone后对新对象执行```php $this->setBuilder();```保证 clone之后的builder是一个新实例。

```php
<?php
class Query extends \think\db\Query{

	public function __clone(){
		$this->setBuilder();
	}
}
```

到此，对于一开始的使用场景，thinkphp5也可以使用clone完成

```php

$query = (new Post())->getQuery();
$query->where(['status'=>1,'is_delete'=>0]);
$t = clone $query;
$t->where('create_at','between time',[$start_date, $end_date]);
$totalcount = $query->count();
$todaycount = $t->count();

```

在这其中有几点需要注意:
*	对象clone之后，其属性执行的是浅拷贝！！
*	\_\_clone()方法的操作只对clone出来新对象有效!
*	如果没做任何修改，thinkphp5中不要直接clone model,除非自己知道在干什么，否则容易参数bug,因为它不抛错误。