---
layout: post
title: PHP赋值的内部原理
category: php
comments: true
description: PHP赋值的内部原理
keywords: PHP,源码
---

在PHP中，一个变量被赋值，内部到底经历了怎样的逻辑判断呢？

PHP在内核中是通过zval这个结构体来存储变量的，它的定义在Zend/zend.h文件里

```c
struct _zval_struct {
    zvalue_value value; /* 变量的值 */
    zend_uint refcount__gc;
    zend_uchar type;    /* 变量当前的数据类型 */
    zend_uchar is_ref__gc;
};
typedef struct _zval_struct zval;

//在Zend/zend_types.h里定义的：
typedef unsigned int zend_uint;
typedef unsigned char zend_uchar;

```

使用xdebug的xdebug_debug_zval函数可以打印出变量的refcount,is_ref的值。



```php
$a = 'Hello World';
$b = $a;
```
以上内容在内核中怎么执行呢?

```c
    zval *helloval;
    MAKE_STD_ZVAL(helloval);
    ZVAL_STRING(helloval, "Hello World", 1);
    zend_hash_add(EG(active_symbol_table), "a", sizeof("a"),&helloval, sizeof(zval*), NULL);
    ZVAL_ADDREF(helloval); //这句很特殊，我们显式的增加了helloval结构体的refcount
    zend_hash_add(EG(active_symbol_table), "b", sizeof("b"),&helloval, sizeof(zval*), NULL);
```

可以看出来，当变量赋值的时候，其实两个变量指向的是同一个地址空间。那么问题来了，如果指向同一个地址空间，那不是修改a,b也会跟着改变。这就涉及php的**写时复制机制**。
以上代码，如果后面一行为``$b = '123'``判断过程如下:

* 如果这个变量的zval部分的refcount小于2，代表没有别的变量在用，则直接修改这个值
* 否则，复制一份zval 的值，减少原zval的refcount的值,初始化新的zval的refcount,修改新复制的zval

<!-- more -->

### 简单变量

#### 先引用赋值后普通赋值
```php
var_dump(memory_get_usage());
$a = '1234567890';
xdebug_debug_zval('a');
var_dump(memory_get_usage());
$b = &$a;
xdebug_debug_zval('a','b');
var_dump(memory_get_usage());
$c = $a;
xdebug_debug_zval('a','b','c');
var_dump(memory_get_usage());
$a = '1234567890';
var_dump(memory_get_usage());
$b = &$a;
var_dump(memory_get_usage());
$c = $a;
```
输出内容如下:

```
int(121672)
a: (refcount=1, is_ref=0)='1234567890'

int(121776)
a: (refcount=2, is_ref=1)='1234567890'
b: (refcount=2, is_ref=1)='1234567890'

int(121824)
a: (refcount=2, is_ref=1)='1234567890'
b: (refcount=2, is_ref=1)='1234567890'
c: (refcount=1, is_ref=0)='1234567890'

int(121928)
```

$a 赋值，开辟了104byte空间，变量a refcount=1,is_ref=0

$b 赋值，开辟了48byte空间，变量a refcount=2,is_ref=1。48byte是符号表占用，a,b执行同一个地址空间

$c 赋值，开辟了104byte空间。由于a,b是引用，所以在c赋值的时候，会开辟新空间，复制a zval内容，并初始化refcount,is_ref，所以a 的refcount不变，c 的refcount=1


#### 先普通赋值后引用赋值

```php

var_dump(memory_get_usage());

$a = '1234567890';
xdebug_debug_zval('a');
var_dump(memory_get_usage());
$b = $a;
xdebug_debug_zval('a','b');
var_dump(memory_get_usage());

$c = &$a;
xdebug_debug_zval('a','b','c');
var_dump(memory_get_usage());
```

输出内容如下:

```
int(121672)

a: (refcount=1, is_ref=0)='1234567890'
int(121776)

a: (refcount=2, is_ref=0)='1234567890'
b: (refcount=2, is_ref=0)='1234567890'
int(121824)

a: (refcount=2, is_ref=1)='1234567890'
b: (refcount=1, is_ref=0)='1234567890'
c: (refcount=2, is_ref=1)='1234567890'
int(121928)
```

$a 赋值，开辟了104byte空间，变量a refcount=1,is_ref=0

$b 赋值，开辟了48byte空间，变量a refcount=2,is_ref=1。48byte是符号表占用，a,b指向同一个地址空间

$c 赋值，开辟了104byte空间。由于a,c是引用，需要与b隔离开来，因此会赋值原有的zval，初始化zval，将a,c指向新复制的zval,同时原有的zval refcount-1


### 数组


```
$arr = [0=>'one'];
xdebug_debug_zval('arr');
$arr[1] = $arr;

xdebug_debug_zval('arr');

$arr[2] = $arr;
xdebug_debug_zval('arr');
unset($arr[1]);
xdebug_debug_zval('arr');
unset($arr[2]);
xdebug_debug_zval('arr');

```
输出内容如下:

```sh
arr: (refcount=1, is_ref=0)=array (0 => (refcount=1, is_ref=0)='one')

arr: (refcount=1, is_ref=0)=array (0 => (refcount=2, is_ref=0)='one', 1 => (refcount=1, is_ref=0)=array (0 => (refcount=2, is_ref=0)='one'))

arr: (refcount=1, is_ref=0)=array (0 => (refcount=3, is_ref=0)='one', 1 => (refcount=2, is_ref=0)=array (0 => (refcount=3, is_ref=0)='one'), 2 => (refcount=1, is_ref=0)=array (0 => (refcount=3, is_ref=0)='one', 1 => (refcount=2, is_ref=0)=array (...)))

arr: (refcount=1, is_ref=0)=array (0 => (refcount=3, is_ref=0)='one', 2 => (refcount=1, is_ref=0)=array (0 => (refcount=3, is_ref=0)='one', 1 => (refcount=1, is_ref=0)=array (...)))

arr: (refcount=1, is_ref=0)=array (0 => (refcount=1, is_ref=0)='one')

```


```php

$arr = [0=>'one'];
xdebug_debug_zval('arr');
$arr[1] = &$arr;

xdebug_debug_zval('arr');

$arr[2] = $arr;
xdebug_debug_zval('arr');
unset($arr[1]);
xdebug_debug_zval('arr');
unset($arr[2]);
xdebug_debug_zval('arr');


```
输出内容如下:

```sh
arr: (refcount=1, is_ref=0)=array (0 => (refcount=1, is_ref=0)='one')
arr: (refcount=2, is_ref=1)=array (0 => (refcount=1, is_ref=0)='one', 1 => (refcount=2, is_ref=1)=...)
arr: (refcount=3, is_ref=1)=array (0 => (refcount=2, is_ref=0)='one', 1 => (refcount=3, is_ref=1)=..., 2 => (refcount=2, is_ref=0)=array (0 => (refcount=2, is_ref=0)='one', 1 => (refcount=3, is_ref=1)=..., 2 => (refcount=2, is_ref=0)=...))
arr: (refcount=2, is_ref=1)=array (0 => (refcount=2, is_ref=0)='one', 2 => (refcount=2, is_ref=0)=array (0 => (refcount=2, is_ref=0)='one', 1 => (refcount=2, is_ref=1)=..., 2 => (refcount=2, is_ref=0)=...))
arr: (refcount=2, is_ref=1)=array (0 => (refcount=2, is_ref=0)='one')

```
上面段测试代码很相似，差别只在arr[1]是否是引用赋值。

arr[1]非引用赋值的情况，arr[0]的refcount = 赋值次数+1,执行两次unset之后，arr,arr[0]的refcount都跟开始定义的时候一致。
arr[1]引用赋值的情况，arr[0]的refcount = 非引用赋值次数+1，执行两次unset之后，arr,arr[0] 的refcount都无法回到定义的时候的值。

主要原因在于arr[1]引用赋值，构成一个递归操作。 但是如果，至于这个refcount，真的说不明白。当没有arr[2]赋值的时候，执行unset, arr refcount能回到1 。从下面这张图更加清晰看出内部递归引用

![array ref](http://php.net/manual/zh/images/12f37b1c6963c1c5c18f30495416a197-loop-array.png)

当出现上面这种情况，refcount本该=1，但实际上面没有被设置为1，这种情况就会出现内存泄漏。上面代码循环执行100次，内存从一开始121096 上升到169224，内存占用上升了5k 。


### 对象

```php
	$user = new User();
	$m = $user;
	$user->user ='';
	$user->name = 'sdfsdfs';
	xdebug_debug_zval('user','m');

```

以上内容输出

```sh
(refcount=2, is_ref=0)=class User { 
	public $name = (refcount=1, is_ref=0)='sdfsdfs'; 
	public $model = (refcount=1, is_ref=0)=NULL; 
	public $user = (refcount=1, is_ref=0)='' 
}
m: (refcount=2, is_ref=0)=class User { 
	public $name = (refcount=1, is_ref=0)='sdfsdfs'; 
	public $model = (refcount=1, is_ref=0)=NULL; 
	public $user = (refcount=1, is_ref=0)='' 
}

```
xdebug给出的is_ref=0。refcount与普通变量一直。但是类的赋值是引用赋值。


```php
	$user = new User();
	$user->user = $user;
	$user->name = 'sdfsdfs';
	xdebug_debug_zval('user');
	unset($user);

```

上面内容输出:

```sh

user: (refcount=2, is_ref=0)=class User { public $name = (refcount=1, is_ref=0)='sdfsdfs'; public $user = (refcount=2, is_ref=0)=... }
```

这里由于类的赋值是引用赋值，索引也构成了一个递归操作,这样也会跟数组一样出现内存泄漏的情况。对以下代码个自行100次

```php
	$user = new User();
	$user->user = $user;
	$user->name = 'sdfsdfs';
	xdebug_debug_zval('user');
	unset($user);

```

```php
	$user = new User();
	$user->user = new Order();
	$user->name = 'sdfsdfs';
	xdebug_debug_zval('user');
	unset($user);
```

第一段代码前后内存差1408 byte. 第二段代码差208 byte。






