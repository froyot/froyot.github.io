---
layout: post
title: LetCode 查找数组中两个数之和等于特定数值
category: PHP
comments: true
description: LetCode 查找数组中两个数之和等于特定数值
keywords: LetCode,数组查找
---

假设允许赠送礼物配置
```
{"apple":5,"Banana":3,"Fig":7,"Grape":1,"Haw":4,"Mango":6,"Nectarin":8,"Pear":2,"Pitaya":6,"empty":0}
```
业务需求要求当用户充值>50 且<80 赠送价值4元礼物，当用户充值>80 且小于<100 赠送6元礼物,当用户充值>100 赠送价值10礼物，单次赠送不赠送相同礼物。对于这样的问题，如果在工作中如何解决?我们先来看一看Letcode中的一道题
给定一个整数数组和一个目标值，找出数组中和为目标值的两个数。你可以假设每个输入只对应一种答案，且同样的元素不能被重复利用。


```
示例:

给定 nums = [2, 7, 11, 15], target = 9

因为 nums[0] + nums[1] = 2 + 7 = 9
所以返回 [0, 1]

```

### 方法1--循环查找

```python
def towsum(nums,target)
	for i in range(len(nums):
		for j in range(i,len(nums)):
			if nums[j] + nums[i] == target:
				return [i,j]
	return []

```
时间复杂度：需要执行两层循环，时间复杂度O(n^2)
空间复杂度：O(1)

### 排序后从两端查找
```python
def towsum(nums,target)
	sortnums = sorted(nums)
	i = 0
	j = len(sortnums)
	while i<j:
		t = sortnums[i] + sortnums[j]
		if t > target:
			j = j -1
		elif t< target:
			i = i+1
		else:
			return findindex(nums,sortnums[i],sortnums[j])

def findindex(nums,num1,num2):
	num1index = -1
	num2index = -1

	for i in range(len(nums):
		if num1index>=0 and num2index>=0:
			break
		if num1 == nums[i] and num1index<0:
			num1index = i
			continue
		if num2 == nums[i] and num2index<0:
			num2index = i
			continue
	if num1index>=0 and num2index>=0:
		return [num1index,num2index]
	return [-1,-1]

```

时间复杂度：算法时间复杂度依赖排序算法时间复杂度O(nlogn)，排序之后选出符合条件的元素，再获取原数组的位置至少需要一次循环，因此总的算法时间复杂度O(n)+O(nlogn)
空间复杂度：O(n)


### 方法3--hash表

```python

def towsum(nums,target)
	dic = {}
	for i in range(len(nums):
		t = target-nums[i]
		if t in dic:
			return [i,dic[t]]
		else:
			dic[nums[i]] = i
	return []

```
时间复杂度：需要一次循环，时间复杂度O(n)
空间复杂度：O(n)


### 充值赠送的解

回到一开始的问题，这其实也是获取数组中符合两个数之和等于指定数值的问题，只是可以返回多个解。

```python

def sendgift(gifts,price)
	dic = {}
	allowgifts = []
	for (k,v) in  gifts.items(): 
		t = price-v
		if t in dic:
			allowgifts.append( [k,dic[t]] )
		else:
			dic[v] = k
	return allowgifts

```

