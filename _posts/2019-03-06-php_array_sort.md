---
layout: post
title: PHP 关于数组排序的函数
category: php
comments: true
description: PHP 关于数组排序的函数
keywords: PHP,源码,数组,排序,引用传递
---

php的数组排序函数有很多。有按键排序，有按值排序。有升序,有降序。有的排序后改变原数组索引，有的不改变。


关于PHP的排序函数，[官方文档](http://php.net/manual/zh/array.sorting.php)给出了下面的一个总结表：

![php array sort functions](http://blog.static.aiaiaini.com/blog/201903/array_sort_funcsd4c8195f60b11d13882dc40b09b9b8ef.jpg)

以上函数排序结果都是通过**引用传递**到原数组中去，而不是返回一个新的有序的数组。

<!-- more -->

### 一维数组排序

其实PHP内部对于数组排序的实现都比较相似，都是一个模子刻出来的。

先看看asort,arsort排序源码:

![php array asort](http://blog.static.aiaiaini.com/blog/201903/asort34502dcee3b97a79e9e89073babc42f7.jpg)

![php array arsort](http://blog.static.aiaiaini.com/blog/201903/arsort34502dcee3b97a79e9e89073babc42f7.jpg)

再来看看sort,rsort排序函数的源码

![php array sort](http://blog.static.aiaiaini.com/blog/201903/sort34502dcee3b97a79e9e89073babc42f7.jpg)

![php array rsort](http://blog.static.aiaiaini.com/blog/201903/rsort34502dcee3b97a79e9e89073babc42f7.jpg)

从上面四个函数的代码对比可以看出，数组排序最终都是通过``zend_hash_sort``实现的。查看源码，可以发现，除了``array_multisort``是使用``zend_sort``实现的外，其他的函数都是通过``zend_hash_sort``实现。排序方式通过传入的排序函数决定，并通过参数控制是否覆盖原来的索引。

按照这个理解，估计有的人会猜想对于用户自定义函数排序，内部是直接把函数传递到``zend_has_sort``中去。但是在PHP中其实还加了一层，限定了函数只能作用在键或者值之上。对于函数``usort`` 和``uksort``分别是使用自定义函数按值，和按键排序。

![usort and uksort](http://blog.static.aiaiaini.com/blog/201903/usort_uksort6ec5facbc84f8148bc42c4c158710650.jpg)

用户自定义函数其实是在``php_array_user_key_compare``，和``php_array_user_compare``中调用的。


其实归结起来，排序函数就有下面几种

1、sort,按值排序，改变键名,相关有rsort,usort

2、asort，按值排序，不改变键名,相关有arsort,uasort

3、ksort,按键名排序，不改变键名,相关有krsort,uksort

4、nasort,nacasesort自然顺序排序,不改变键名


### 多数组排序

```array_multisort```是一个比较奇葩的函数，它的调用形式有很多
比如:

```php

array_multisort(
	$volume, SORT_DESC, 
	$edition, SORT_ASC, $data
);

```
或者:

```php

array_multisort(
	$ar[0], SORT_ASC, SORT_STRING,
    $ar[1], SORT_NUMERIC, SORT_DESC
);
```

还有这样:

```php
array_multisort($ar1, $ar2);

```

它内部怎么确定传的参数代表什么意思呢?

![php array_multisort](http://blog.static.aiaiaini.com/blog/201903/multil_sort6ec5facbc84f8148bc42c4c158710650.jpg)

可以看到，代码里对数据类型进行判断。如果是数组，都当做排序数组。所以``array_multisort``可以排序不定个数个数组。顺序，以及排序方式都是通过获取数组之后的整形参数得到。如果没有，那就都默认。



