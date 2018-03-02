---
layout: post
title: PHP foreach 引用传递 循环之后的事情
category: PHP
comments: true
keywords: PHP,引用传递,foreach,PHP 循环
---
foreach 是PHP语法中最最常用的。foreach可以直接对循环结构进行便利，也可以以引用的方式进行遍历，在遍历的过程修改原来循环结构
今天就来谈谈foreach 以引用的方式，循环之后的一些事情。

*	case 1

```
$a = [0,1,3,5];
foreach ($a as $key => &$item) {
	echo $item.' ';
}
print_r($a);
$item = 10;
print_r($a);

```

我们看看上面的代码输出内容：```echo: 0 1 3 5 ```

两个print_r的输出内容
```
(
    [0] => 0
    [1] => 1
    [2] => 3
    [3] => 5
)
Array
(
    [0] => 0
    [1] => 1
    [2] => 3
    [3] => 10
)
```

从上面可以看出，foreach引用循环之后，$item并没有释放，还是指向数组最后一个元素的引用，所以后续代码中如果使用了一个同名的遍历，将会同时影响到原来的数组。



