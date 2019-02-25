---
layout: post
title: PHP 字符串填充str_pad函数有什么不知道的
category: php
comments: true
description: PHP 字符串填充str_pad函数有什么不知道的
keywords: PHP,源码,字符串填充,str_pad,字符串
---

str_pad — 使用另一个字符串填充字符串为指定长度

```php

str_pad ( string $input , int $pad_length [, string $pad_string = " " [, int $pad_type = STR_PAD_RIGHT ]] ) : string

```

该函数返回 input 被从左端、右端或者同时两端被填充到制定长度后的结果。

如果可选的 pad_string 参数没有被指定，input 将被空格字符填充，否则它将被 pad_string 填充到指定长度。

可选的 pad_type 参数的可能值为 STR_PAD_RIGHT，STR_PAD_LEFT 或 STR_PAD_BOTH。如果没有指定 pad_type，则假定它是 STR_PAD_RIGHT。

以上是文档上的说明。

	
	

那么对于以下这些情况，内部怎么处理，会得到什么样的结果呢？

	


1、input长度比pad_length长度大

2、pad_length给负数的时候,给0的时候呢

3、pad_string给空字符串的时候呢

4、可以填充的最大长度是什么，有没有限制

5、两边填充，给定pad_length，左边填充多少，右边填充多少

<!-- more -->

这些答案都在源码当中。


```c

if (pad_length < 0  || (size_t)pad_length <= ZSTR_LEN(input)) {
	RETURN_STRINGL(ZSTR_VAL(input), ZSTR_LEN(input));
}

if (pad_str_len == 0) {
	php_error_docref(NULL, E_WARNING, "Padding string cannot be empty");
	return;
}

```

可以看到，如果pad_length<0 或小于原字符串的时候（包括pad_length=0），都返回原字符串。

当填充字符串为空字符串的时候，会触发警告信息，返回NULL


好了，前3个问题都找到答案了。来看后面几个问题


```c

num_pad_chars = pad_length - ZSTR_LEN(input);
if (num_pad_chars >= INT_MAX) {
	php_error_docref(NULL, E_WARNING, "Padding length is too long");
	return;
}

```
填充长度（pad_length - str_len(input) ）最大取值是INT_MAX，所以pad_length可以传一个不大于 INT_MAX+ste_len(input)的值。


```c

switch (pad_type_val) {
	case STR_PAD_RIGHT:
		left_pad = 0;
		right_pad = num_pad_chars;
		break;

	case STR_PAD_LEFT:
		left_pad = num_pad_chars;
		right_pad = 0;
		break;

	case STR_PAD_BOTH:
		left_pad = num_pad_chars / 2;
		right_pad = num_pad_chars - left_pad;
		break;
}
```

当两边填充的时候，先填充左边，再填充右边。当num_pad_chars为奇数的时候，左边会比右边少一个(整除运算)




所以，对于str_pad，处理知道默认以空字符填充，默认填充右边之外，还有以下内容:


input长度比pad_length长度大,或者pad_length<0的时候返回原字符串

pad_length最大长度=INT_MAX+str_len(input)

pad_string 不能传入空字符串，否则触发警告，返回NULL

两边填充的时候，先填充左边，再填充右边。实际填充长度为奇数的时候，左边填充长度会比右边少一个字符。






