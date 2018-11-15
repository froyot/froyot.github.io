---
layout: post
title: 如何快速高效的将数组转换成树形结构
category: php
comments: true
description: 如何快速高效的将数组转换成树形结构
keywords: 数组,树状,无限极分类
---

任何无限极分类都会涉及到创建一个树状层级数组。从顶级分类递归查找子分类，最终构建一个树状数组。如果分类数据是一个数组配置文件，且子类父类id没有明确的大小关系。那么我们如何高效的从一个二维数组中构建我们所需要的树状结构呢。

假设数据源如下:
```php

return [
	['id'=>1,'name'=>'文章','parent_id'=>0],
	['id'=>2,'name'=>'页面','parent_id'=>0],
	['id'=>3,'name'=>'娱乐','parent_id'=>1],
	['id'=>4,'name'=>'国内','parent_id'=>1],
	['id'=>5,'name'=>'海外','parent_id'=>1],
	['id'=>6,'name'=>'北京','parent_id'=>4],
	['id'=>7,'name'=>'上海','parent_id'=>4]
];

```

#### 方案1 :

```php
function makeTree($source,$parentid=0){
	$trees = [];
	foreach ($source as $key => $item) {
		if( $item['parent_id'] ==$parentid )
		{
			$item['_child'] = makeTree($source,$item['id']);
			$trees[] = $item;
		}
	}
	return $trees;
}

```

##### 分析:

每次递归都要遍历所有的数据源。时间复杂度N^2


#### 方案2 :

```php
function arrayToTree($source){
	$childMap = [];
	foreach ($source as $key => $item) {

		$k = 'map_'.$item['parent_id'];
		if( !isset( $childMap[$k] ) )
		{
			$childMap[$k] = [];
		}
		$childMap[$k][] = $item;
	}
	return $this->makeTree($childMap);
}
function makeTree($childMap,$parentid=0){
	$k = 'map_'.$parentid;
	$items = isset( $childMap[$k] )?$childMap[$k]:[];
	if(!$items)
	{
		return [];
	}
	$trees = [];
	foreach ($items as  $value) {
		$trees[] = $items['_child'] = $this->makeTree($childMap,$item['id']);
	}
	return $trees;
}

```

##### 分析:

每次递归循环内部只遍历指定父分类下的数据。加上前期数据准备，整个时间复杂度Nx2


#### 测试

生成测试数据

```php
function generateSource($num){
	for($id=1;$id<$num;$id++)
	{
		$name = chr(65+($id%26)).$id;
		$source[] = [
			'id'=>$id,'name'=>$name,'parent_id'=>($id%2==0)?max(0,$id-rand(3,9)):max(0,$id-rand(1,7))
		];
	}
	return $source;
}

```

对两种方式使用相同的5000个数据，分别测试100次，两种方式100次执行总时间如下(单位s):

```

float(96.147500038147) 
float(0.82804679870605)

```
可以看出相差的不是一点点。方案2还是使用的是递归调用。递归调用虽然会让程序简介，阅读方便，但是数据多的时候容易出现超出最大调用栈的情况,同时内存也会持续上升。

还有什么其他的方案呢？

