---
layout: post
title: PHP GC学习
category: PHP
comments: true
description: " PHP GC学习 "
---


在5.2及更早版本的PHP中，没有专门的垃圾回收器GC（Garbage Collection），引擎在判断
一个变量空间是否能够被释放的时候是依据这个变量的zval的refcount的值，如果refcount
为0，那么变量的空间可以被释放，否则就不释放。思考一个问题:

```
$a = array('one');
$a[] = &$a;
unset($a);
```

在执行unset之前，PHP进程中变量的指向：
![](http://php.net/manual/zh/images/12f37b1c6963c1c5c18f30495416a197-loop-array.png)

执行上述代码之后,PHP进程中变量的指向：

![](http://php.net/manual/zh/images/12f37b1c6963c1c5c18f30495416a197-leak-array.png)

那么问题来了，那些在内存里的，没有被任何变量指向，但是指向自身引用的变量容器怎么
被回收？如果采用引用计数器的方式进行GC，那么如果出现上述这种自身引用的情况就容易
造成内存泄漏。PHP5.3之后引入一种新的垃圾回收机制，用于处理类似"垃圾"数据。

*   如果一个zval的refcount增加，那么此zval还在使用，不属于垃圾
*   如果一个zval的refcount减少到0， 那么zval可以被释放掉，不属于垃圾
*   如果一个zval的refcount减少之后大于0，那么此zval还不能被释放，此zval可能成为一个垃圾，进一步判断

为了避免每次refcount减少都进行GC判断，算法会先把所有可能是垃圾的zval节点放入一个节点(root)缓冲区(root buffer),当缓冲区被节点塞满的时候，GC才开始开始对缓冲区中的zval节点进行垃圾判断。并且将这些zval节点标记成紫色

当缓冲区满了之后，算法以深度优先对每一个节点所包含的zval进行减1操作，为了确保不会对同一个zval的refcount重复执行减1操作，一旦zval的refcount减1之后会将zval标记成灰色。需要强调的是，这个步骤中，起初节点zval本身不做减1操作，但是如果节点zval中包含的zval又指向了节点zval（环形引用），那么这个时候需要对节点zval进行减1操作。

算法再次以深度优先判断每一个节点包含的zval的值，如果zval的refcount等于0，那么将其标记成白色(代表垃圾)，如果zval的refcount大于0，那么将对此zval以及其包含的zval进行refcount加1操作，这个是对非垃圾的还原操作，同时将这些zval的颜色变成黑色（zval的默认颜色属性）。
D：遍历zval节点，将C中标记成白色的节点zval释放掉。



