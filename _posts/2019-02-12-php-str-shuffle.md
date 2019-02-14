---
layout: post
title: PHP内部如何实现打乱字符串顺序函数str_shuffle
category: php
comments: true
description: PHP内部如何实现打乱字符串顺序函数str_shuffle
keywords: PHP,源码,str_shuffle,打乱字符串顺序
---

2019年春节已过，今天是上班第一天，还得翻一翻之前没有看完的PHP源码。

今天聊的是字符串顺序打乱函数str_shuffle。这个函数本身使用频率并不高。但是，其内部实现还是非常有趣的。


<!-- more -->
## 自己实现

如果在没有看PHP源码内部实现之前，如果使用php实现内部字符串打乱顺序的操作,我能想到的是下面几种方式。

### 循环随机数

使用随机数，可以有随机取字符串的字符拼接，或者顺序取出，放到随机数自定的位置。这两种方式都涉及到随机数重复的情况，需要去重。

```php
$str = 'abcdefg';
$len = strlen($str);
$newstr = '';
$randnums = [];

while(true){
	if(count($randnums) == $len)
	{
		break;
	}
	$rand = rand(0,$len-1);
	if( in_array($rand,$randnums) )
	{
		continue;
	}
	$randnums[] = $rand;
	$newstr .= $str[$rand];
}
var_dump($newstr);

```

这种方式的重点在于生成不重复的随机数。

### 切分成数组然后打乱顺序

```php
$str = 'abcdefg';
$len = strlen($str);
$newstr = '';
$arr = str_split($str);
shuffle($arr);
$newstr .= implode($arr,'');
var_dump($newstr);
```

用数组打乱顺序的方式实现其实是有些“作弊”嫌疑。

## PHP内部实现

来看看PHP内部如何实现。


```c
static void php_string_shuffle(char *str, zend_long len) 
{
	zend_long n_elems, rnd_idx, n_left;
	char temp;
	n_elems = len;
	if (n_elems <= 1) {
		return;
	}
	n_left = n_elems;
	while (--n_left) {
		rnd_idx = php_rand();
		RAND_RANGE(rnd_idx, 0, n_left, PHP_RAND_MAX);
		if (rnd_idx != n_left) {
			temp = str[n_left];
			str[n_left] = str[rnd_idx];
			str[rnd_idx] = temp;
		}
	}
}
```

其实PHP内部也是使用随机数实现，但是他的巧妙之处在于使用随机数抽取字符串与一个特定的字符串(最后一个)进行替换。这样就不用去考虑随机数重复的问题。不会因为重复到账一些字符串被覆盖。

文章开始的随机数抽取，不能保证经过n次后结束，因为需要跳过随机数重复的情况。但是php内部的实现，都是n次循环后结束。在性能上肯定比需要去重的随机数方法要好。

两个方法的出发点都一样，但是稍微的不一样就可以带来很大的提升。



