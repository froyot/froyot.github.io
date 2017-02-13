---
layout: post
title: Yii2 源码学习 Behavior
category: PHP
comments: true
---

Yii2的```行为```，用来在不修改组件主体代码的情况下，增强组件的功能。
行为可以将自己的方法以及属性注入到组件中。在组件中可以像使用自己的
方法和属性一样使用，通过$this直接调用。行为通过组件能响应被触发的事
件， 从而自定义或调整组件正常执行的代码。

Behavior类:

```
class Behavior extends Object
{

    public $owner;

    public function events()
    {
        return [];
    }

    public function attach($owner)
    {
        $this->owner = $owner;
        foreach ($this->events() as $event => $handler) {
            $owner->on($event, is_string($handler) ? [$this, $handler] : $handler);
        }
    }

    public function detach()
    {
        if ($this->owner) {
            foreach ($this->events() as $event => $handler) {
                $this->owner->off($event, is_string($handler) ? [$this, $handler] : $handler);
            }
            $this->owner = null;
        }
    }
}
```

行为主要提供两个方法，一个是行为绑定，一个是行为解绑。绑定的过程中，
通过events属性，对定义的组件事件以及事件响应函数进行绑定。实现在行
为中响应组件事件的功能。

比如要实现一个将数据添加排序字段的行为SortrableModelBehavior

```
class SortrableModelBehavior extends Behavior{
	 public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'findMaxOrderNum',
        ];
    }

    public function findMaxOrderNum($event)
    {
        if(!$this->owner->order_num) {
            $maxOrderNum = (int)(new \yii\db\Query())
                ->select('MAX(`order_num`)')
                ->from($this->owner->tableName())
                ->scalar();
            $this->owner->order_num = ++$maxOrderNum;
        }
    }
}

```

这样在ActiveRecord组件执行对象插入的时候就会获取当前最大排序值并加一赋值给新数据。
而在ActiveRecord组件中不需要做任何更改。

