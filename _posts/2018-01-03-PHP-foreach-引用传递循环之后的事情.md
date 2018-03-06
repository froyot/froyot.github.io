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

*	case 2

```
$a = [0,1,3,5];
foreach ($a as $key => &$item) {
	echo $item.' ';
	if($key>1){
		break;
	}
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
    [2] => 10
    [3] => 5
)
```

从以上的输出结果可以看出，$item总是指向循环跳出之前的最后一个元素


*	case 3

```
$a = [0,1,3,5];
foreach ($a as $key => &$item) {
	echo $item.' ';
}
print_r($a);
foreach($a as $key => $item)
{
	echo $item;
}
print_r($a);

```

我们看看上面的代码输出内容：```echo1: 0 1 3 5 ```,```echo2:0 1 3 3```

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
    [3] => 3
)
```

这个结果就有点绕，网上有很多说这是PHP foreach 的bug,但是仔细分析，其实并不是bug.我们把第二个循环拆解开:
1. $t = $a[0]; $item = $t; //此时 $a[3] = $a[0], $a的内容[0,1,3,0]
2. $t = $a[1]; $item = $t; //此时 $a[3] = $a[1], $a的内容[0,1,3,1]
3. $t = $a[2]; $item = $t; //此时 $a[3] = $a[2], $a的内容[0,1,3,3]
4. $t = $a[3]; $item = $t; //此时 $a[3] = $a[3], $a的内容[0,1,3,3]

通过上面三种情况，可以了解到foreach引用传递之后，我们需要unset($item),接触引用，否则一旦循环之后的代码有是有到循环中的变量名，很容易造成bug。由于大型系统并非一个人完成，所以在多人协作的时候，一定要把自己的数据处理干净，避免bug


#### 其他引用相关


```

$arr = [0,1];
$arr = [&arr];
var_dump($arr === $arr[0]);

```

以上输出结果是true。因为$arr 和 $arr[0]指向同一个数据地址。

```

$arr = [0,1];
function test($item,$key,&$arr){
    unset($arr[$key]);
}
array_walk($arr, 'test',$arr);
var_dump($arr);

```

以上输出内容还是[0,1]。


