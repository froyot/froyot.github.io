---
layout: post
title: Yii2 源码学习 Components 
category: PHP
comments: true
---


Yii 组件组成了Yii2绝大部分的功能。Controller属于组件。
Action属于组件，Model属于组件。Request属于组件,Response属于组件...只有几个
Object的子类，以及Exception类不是组件。组件实现了三个主要的功能：
*	Property 属性获取设置(继承于Object类)
*	Event 事件
*	Behavior 行为
  

因为继承于Object,实现了configure接口，组件可以通过配置方式创建，同时设置好
创建对象的属性。比如

```
component=>[
	'user'=>[
		'class'=>'yii\web\User'
		'enableAutoLogin' => true,
	],
	'response'=>[
		'class'=>'yii\web\Response',
		'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->data !== null) {
                    $response->data = [
                        'success' => $response->isSuccessful,
                        'data' => $response->data,
                    ];
                    $response->statusCode = 200;
                }
            }
	],
	...

]

```
  

在上面可以看出来有一个比较特别的属性,'on beforeSend'，但是Response中并没有on beforeSend 属性
因此会执行魔术方法__set。

```
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            // set property
            $this->$setter($value);

            return;
        } elseif (strncmp($name, 'on ', 3) === 0) {
            // on event: attach event handler
            $this->on(trim(substr($name, 3)), $value);

            return;
        } elseif (strncmp($name, 'as ', 3) === 0) {
            // as behavior: attach behavior
            $name = trim(substr($name, 3));
            $this->attachBehavior($name, $value instanceof Behavior ? $value : Yii::createObject($value));

            return;
        } else {
            // behavior property
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canSetProperty($name)) {
                    $behavior->$name = $value;

                    return;
                }
            }
        }
        if (method_exists($this, 'get' . $name)) {
            throw new InvalidCallException('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new UnknownPropertyException('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

```

在__set方法中，对输入属性进行判断，如果以on 开头的，则作为事件绑定。如果以as 开头的属性，则作为行为绑定。
如果属性不存在，同时没有对应的setter方法，则检查所绑定的行为中有没有对应的属性，进行设置。
  

在组件中，可以像调用自己的属性一样去调用行为的属性，以及方法。这主要通过魔术方法__get,__call实现。


```

    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            // read property, e.g. getName()
            return $this->$getter();
        } else {
            // behavior property
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canGetProperty($name)) {
                    return $behavior->$name;
                }
            }
        }
        if (method_exists($this, 'set' . $name)) {
            throw new InvalidCallException('Getting write-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    public function __call($name, $params)
    {
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $object) {
            if ($object->hasMethod($name)) {
                return call_user_func_array([$object, $name], $params);
            }
        }
        throw new UnknownMethodException('Calling unknown method: ' . get_class($this) . "::$name()");
    }

```

从函数中可以看出，只有当组件中属性或函数不存,才会去获取行为的属性和方法。



Component中的事件实现跟Event中的实现类似。

```

	public function on($name, $handler, $data = null, $append = true)
    {
        $this->ensureBehaviors();
        if ($append || empty($this->_events[$name])) {
            $this->_events[$name][] = [$handler, $data];
        } else {
            array_unshift($this->_events[$name], [$handler, $data]);
        }
    }

    public function off($name, $handler = null)
    {
        $this->ensureBehaviors();
        if (empty($this->_events[$name])) {
            return false;
        }
        if ($handler === null) {
            unset($this->_events[$name]);
            return true;
        } else {
            $removed = false;
            foreach ($this->_events[$name] as $i => $event) {
                if ($event[0] === $handler) {
                    unset($this->_events[$name][$i]);
                    $removed = true;
                }
            }
            if ($removed) {
                $this->_events[$name] = array_values($this->_events[$name]);
            }
            return $removed;
        }
    }

    
    public function trigger($name, $event = null)
    {
        $this->ensureBehaviors();
        if (!empty($this->_events[$name])) {
            if ($event === null) {
                $event = new Event;
            }
            if ($event->sender === null) {
                $event->sender = $this;
            }
            $event->handled = false;
            $event->name = $name;
            foreach ($this->_events[$name] as $handler) {
                $event->data = $handler[1];
                call_user_func($handler[0], $event);
                // stop further handling if the event is handled
                if ($event->handled) {
                    return;
                }
            }
        }
        // invoke class-level attached handlers
        Event::trigger($this, $name, $event);
    }

```

区别有:

*	事件触发的时候，如果event是null,则event赋值的是当前对象实例。Event中的是当前类全名。

*	事件触发的时候，Component会触发当前类的类级别的事件。

*	在事件绑定解绑，以及触发事件的时候都会检查注册的行为是否绑定了，如果没有绑定，则执行绑定操作。
确保在后续的操作中可以获取行为的属性，方法以及触发属性中的事件处理函数。


  
Behavior的绑定解绑实现：


Component将Behavior放在_behaviors中。绑定的时候，如果behavior 不是Behavior的子类，则根据behavior创建
一个对象。

```

    public function attachBehavior($name, $behavior)
    {
        $this->ensureBehaviors();
        return $this->attachBehaviorInternal($name, $behavior);
    }

    public function attachBehaviors($behaviors)
    {
        $this->ensureBehaviors();
        foreach ($behaviors as $name => $behavior) {
            $this->attachBehaviorInternal($name, $behavior);
        }
    }

    public function detachBehavior($name)
    {
        $this->ensureBehaviors();
        if (isset($this->_behaviors[$name])) {
            $behavior = $this->_behaviors[$name];
            unset($this->_behaviors[$name]);
            $behavior->detach();
            return $behavior;
        } else {
            return null;
        }
    }

    public function detachBehaviors()
    {
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $name => $behavior) {
            $this->detachBehavior($name);
        }
    }

    private function attachBehaviorInternal($name, $behavior)
    {
        if (!($behavior instanceof Behavior)) {
            $behavior = Yii::createObject($behavior);
        }
        if (is_int($name)) {
            $behavior->attach($this);
            $this->_behaviors[] = $behavior;
        } else {
            if (isset($this->_behaviors[$name])) {
                $this->_behaviors[$name]->detach();
            }
            $behavior->attach($this);
            $this->_behaviors[$name] = $behavior;
        }
        return $behavior;
    }

```





