---
layout: post
title: substr_replace如何替换多个字符串不同位置不同长度的子串
category: php
comments: true
description: substr_replace 实现多个字符串子串替换
keywords: PHP,源码,substr_replace,多个字符指定位置体会
---

都知道substr_replace可以替换指定位置的子串。比如``substr_repace("Hello Test",'xxxx',1,4)``替换成Hxxxx Test

那么如何实现替换多个字符串不同位置不同长度的子串。

```
$data = [
'Hello Test',
'QQ mytest',
'Sina email'
]
```

比如上面一个数组，现在需要把数组第i个元素的第i个字符串后面的4个字符串替换陈xxxx

```php
$data = [
	'Hxxxx Test',
	'QQxxxxest',
	'Sinxxxxail'
]
```
其实，substr_replace也可以实现多个字符串子串的替换。

<!-- more -->

substr_replace[函数定义](http://www.php.net/manual/zh/function.substr-replace.php)

```php
substr_replace ( mixed $string , mixed $replacement , mixed $start [, mixed $length ] ) : mixed

```

substr_replace源码在ext/standard/string.c中。先看一下整体的结构

```c
...
if (zend_parse_parameters(ZEND_NUM_ARGS(), "zzz|z/", &str, &repl, &from, &len) == FAILURE) {
	return;
}
...
if (Z_TYPE_P(str) != IS_ARRAY) {
	if (Z_TYPE_P(from) != IS_ARRAY) {
		if (Z_TYPE_P(repl) == IS_ARRAY) {
			...
		} else {
			repl_str = Z_STR_P(repl);
		}
	} else {
		php_error_docref(NULL, E_WARNING, "Functionality of 'start' and 'length' as arrays is not implemented");
		RETURN_STR_COPY(Z_STR_P(str));
	}
} else { 
...
}
```

substr_repace首先根据替换需要替换的内容的类型区分。字符类型和数组类型的替换采用不同的处理方式。同时字符类型也对起始位置参数``from``做了限制，这中情况下，不接受数组类型作为起始位置。

对于字符数据的替换

```c
if (Z_TYPE_P(repl) == IS_ARRAY) {
	repl_idx = 0;
	while (repl_idx < Z_ARRVAL_P(repl)->nNumUsed) {
		tmp_repl = &Z_ARRVAL_P(repl)->arData[repl_idx].val;
		if (Z_TYPE_P(tmp_repl) != IS_UNDEF) {
			break;
		}
		repl_idx++;
	}
	if (repl_idx < Z_ARRVAL_P(repl)->nNumUsed) {
		repl_str = zval_get_string(tmp_repl);
		repl_release = 1;
	} else {
		repl_str = STR_EMPTY_ALLOC();
	}
} else {
	repl_str = Z_STR_P(repl);
}

result = zend_string_safe_alloc(1, Z_STRLEN_P(str) - l + ZSTR_LEN(repl_str), 0, 0);

memcpy(ZSTR_VAL(result), Z_STRVAL_P(str), f);
if (ZSTR_LEN(repl_str)) {
	memcpy((ZSTR_VAL(result) + f), ZSTR_VAL(repl_str), ZSTR_LEN(repl_str));
}
memcpy((ZSTR_VAL(result) + f + ZSTR_LEN(repl_str)), Z_STRVAL_P(str) + f + l, Z_STRLEN_P(str) - f - l);
ZSTR_VAL(result)[ZSTR_LEN(result)] = '\0';
if (repl_release) {
	zend_string_release(repl_str);
}
RETURN_NEW_STR(result);

```

如果替换的目标是一个数组，则取数组第一个元素作为实际替换的内容。

l是传入的第四个参数处理之后的长度值（l取值0-原字符串长度）。然后执行三个copy操作，分别把from之前的原始字符串，替换后的字符串，from+l之后的字符串拷贝到结果字符串中取。所以说，这里的l指定的是原字符串有多少个字符被替换。

如果要替换的内容是一个字符串数组的话，内部处理结构如下:

```c

ZEND_HASH_FOREACH_KEY_VAL(Z_ARRVAL_P(str), num_index, str_index, tmp_str) {
	zend_string *orig_str = zval_get_string(tmp_str);
	if (Z_TYPE_P(from) == IS_ARRAY) {
		...
		if (from_idx < Z_ARRVAL_P(from)->nNumUsed) {
			...
			from_idx++;
			...
		}
		...
	} else {
		...
	}
	if (argc > 3 && Z_TYPE_P(len) == IS_ARRAY) {
		...
		if (len_idx < Z_ARRVAL_P(len)->nNumUsed) {
			...
			len_idx++;
			...
		}
		...
	} else if (argc > 3) {
		l = Z_LVAL_P(len);
	} else {
		l = ZSTR_LEN(orig_str);
	}
	...
	if (Z_TYPE_P(repl) == IS_ARRAY) {
		...
		if (repl_idx < Z_ARRVAL_P(repl)->nNumUsed) {
			zend_string *repl_str = zval_get_string(tmp_repl);
			result_len += ZSTR_LEN(repl_str);
			repl_idx++;
			...
		}
	} else {
		...
	}
	zend_string_release(orig_str);
} ZEND_HASH_FOREACH_END();

```
执行一个for循环，拆分成对每个数组元素的处理。在数组处理中，需要处理起始位置参数，长度参数是数组的情况。所以循环中对form，len，repl参数类型进行检查。如果是数组类型，则在每次替换之后下标进行加一操作。保证每次循环，获取到的是对应于该数组元素需要替换的内容，起始位置，和替换长度。





有以下几点需要了解:

1. length长度是指替换长度，用repacement替换 string[start]...string[start+length]，下面几个实例能够很好的说明其中的含义。
 
 length长度小于替换字符串长度的时候，比如```substr_replace('Hello Test','xxxx',2)``` 输出内容```Hxxxxlo Test```。length长度大于替换字符串长度,比如```substr_replace('Hello Test','xxxx',6)``` 输出内容```Hxxxxest```，length大于原字符串长度的时候,比如```substr_replace('Hello Test','xxxx',12)``` 输出内容```Hxxxx```

* string为字符串的时候，replacement可以是数组，实际替换是去数组第一个元素

```substr_replace('Hello Test',['xxxx'],4)```实际上和```substr_replace('Hello Test','xxxx',4)```效果一样

* 当需要替换的内容是数组的时候，replacement,from,length可以是数组,也可以部分是数组。php对于几个数组参数，如果不对应会进行相应的处理




```php
$s1 = substr_replace(["Hello Test"], ["xxxx"],[1,2],[3,4]);
$s1=>[
	[0]=>'Hxxxxo Test'
]
```
起始位置和长度比要替换的内容多，自动忽略。



```php
$s2 = substr_replace(["Hello Test","qqqq"], ["xxxx"],[1],[3]);
$s1=>[
	[0]=>'Hxxxxo Test',
	[1]=>''
]
```

原数组多，替换后数组少，则相当于替换成空字符串，即等价于一下内容:

```php
$s2 = substr_replace(["Hello Test","qqqq"], ["xxxx",""],[1],[3]);
$s1=>[
	[0]=>'Hxxxxo Test',
	[1]=>''
]
```




```php
$s2 = substr_replace(["Hello Test","qqqq"], ["xxxx","ff"],[1],[3]);
$s1=>[
	[0]=>'Hxxxxo Test',
	[1]=>'ff'
]
```
替换起始位置，长度数组不够，则认为起始位置是0，长度是整个字符串。即等价于:

```php
$s2 = substr_replace(["Hello Test","qqqq"], ["xxxx","ff"],[1,0],[3,strlen("qqqq")]);
$s1=>[
	[0]=>'Hxxxxo Test',
	[1]=>'ff'
]
```



如果部分参数不是数组，则对需要替换的数组都是有效的。

```php
$s2 = substr_replace(["Hello Test","qqqqq"], "xx",[1,0],3);
$s1=>[
	[0]=>'Hxxo Test',
	[1]=>'xxqq'
]
```

等价于
```php
$s2 = substr_replace(["Hello Test","qqqqq"], ["xx","xx"],[1,0],[3,3]);
$s1=>[
	[0]=>'Hxxo Test',
	[1]=>'xxqq'
]
```





