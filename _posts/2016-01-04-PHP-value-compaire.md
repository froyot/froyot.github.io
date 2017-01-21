---
layout: post
title: PHP 松散比较的几个值
category: PHP
comments: true
description: "PHP 松散比较的几个值"
---


PHP中几个比较容易混乱的值:false,'0',0,'',null,array().这几个值在if判断中都是false,那么他们之间相互进行'=='比较呢？


一下是这几个值的比较结果：


|       | 0    | '0'   | false | ''    | null     | array() |
|:----:|:--:|:----:|:--:|:--:|:--:|:--:|
0       | true | true  | true  | true  | true | false
'0'     | true | true  | true  | false | false    | false
false   | true | true  | true  | true  | true     | true
''      | true | false | true  | true  | true    | false
null    | true | false | true  | true | true     | true
array() | false | false | true  | false | true    | true



php按一下顺序进行比较运算:

*   null或string与string比较，先转换成string再比较

*   bool和null与其他任何类型比较，转换成bool

*   string和number相互比较,先转换成数字类型

*   bool与任何其他类型比较，先转换成bool值

*   array和任意其他类型比较，或其他类型与数组比较，**都是数组大**。所以0 == array 是false


