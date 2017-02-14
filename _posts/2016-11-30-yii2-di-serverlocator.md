---
layout: post
title: Yii2 源码学习 对象依赖注入(一)
category: PHP
comments: true
---

在YII2中，实现对象依赖注入的功能主要通过```\yii\base\di``` 下的相关文件实现。
对象依赖注入的机制有两种，
-	控制反转(DI) Container
-	服务定位器ServiceLocator

先看一下Yii中创建对象的函数，分析Container的使用

```


	public static function createObject($type, array $params = [])
    {
        if (is_string($type)) {
            return static::$container->get($type, $params);
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            return static::$container->get($class, $params, $type);
        } elseif (is_callable($type, true)) {
            return static::$container->invoke($type, $params);
        } elseif (is_array($type)) {
            throw new InvalidConfigException('Object configuration must be an array containing a "class" element.');
        } else {
            throw new InvalidConfigException('Unsupported configuration type: ' . gettype($type));
        }
    }


```

### 创建对象分三种情况

*	根据类名创建```createObject('app\models\Mymodel',['property'=>1]);```
*	根据配置数组创建```createObject($config)```
*	根据回调函数创建

```
createObject(function(){
	return $object;
	},$params);

```

#### 先看前面两种对象创建过程:


```
    public function get($class, $params = [], $config = [])
    {
        if (isset($this->_singletons[$class])) {
            // singleton
            return $this->_singletons[$class];
        } elseif (!isset($this->_definitions[$class])) {
            return $this->build($class, $params, $config);
        }

        $definition = $this->_definitions[$class];

        if (is_callable($definition, true)) {
            $params = $this->resolveDependencies($this->mergeParams($class, $params));
            $object = call_user_func($definition, $this, $params, $config);
        } elseif (is_array($definition)) {
            $concrete = $definition['class'];
            unset($definition['class']);

            $config = array_merge($definition, $config);
            $params = $this->mergeParams($class, $params);

            if ($concrete === $class) {
                $object = $this->build($class, $params, $config);
            } else {
                $object = $this->get($concrete, $params, $config);
            }

        } elseif (is_object($definition)) {
            return $this->_singletons[$class] = $definition;
        } else {
            throw new InvalidConfigException('Unexpected object definition type: ' . gettype($definition));
        }

        if (array_key_exists($class, $this->_singletons)) {
            // singleton
            $this->_singletons[$class] = $object;
        }

        return $object;
    }

```
*	判断_singletons是否存在要获取的对象，如果有，则是获取单例对象，直接返回否则继续判断;
*	如果对象不在_definitions里，则是第一次获取，创建一个新对象，并在_definitions缓存
*	获取缓存里的数据
*	如果缓存中的数据是一个回调函数，则通过回调函数获取最终的类实例,否则继续
*	如果缓存中的数据是一个对象配置数组，则根据配置数组创建对象实例
*	如果是一个对象实例，则把对象实例放在_singletons中，作为单例返回




##### 创建对象的时候，对对象所依赖对象进行创建注入。



```

    protected function build($class, $params, $config)
    {
        /* @var $reflection ReflectionClass */
        list ($reflection, $dependencies) = $this->getDependencies($class);

        foreach ($params as $index => $param) {
            $dependencies[$index] = $param;
        }

        $dependencies = $this->resolveDependencies($dependencies, $reflection);
        if (!$reflection->isInstantiable()) {
            throw new NotInstantiableException($reflection->name);
        }
        if (empty($config)) {
            return $reflection->newInstanceArgs($dependencies);
        }

        if (!empty($dependencies) && $reflection->implementsInterface('yii\base\Configurable')) {
            // set $config as the last parameter (existing one will be overwritten)
            $dependencies[count($dependencies) - 1] = $config;
            return $reflection->newInstanceArgs($dependencies);
        } else {
            $object = $reflection->newInstanceArgs($dependencies);
            foreach ($config as $name => $value) {
                $object->$name = $value;
            }
            return $object;
        }
    }

    protected function getDependencies($class)
    {
        if (isset($this->_reflections[$class])) {
            return [$this->_reflections[$class], $this->_dependencies[$class]];
        }

        $dependencies = [];
        $reflection = new ReflectionClass($class);

        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    $c = $param->getClass();
                    $dependencies[] = Instance::of($c === null ? null : $c->getName());
                }
            }
        }

        $this->_reflections[$class] = $reflection;
        $this->_dependencies[$class] = $dependencies;

        return [$reflection, $dependencies];
    }

    protected function resolveDependencies($dependencies, $reflection = null)
    {
        foreach ($dependencies as $index => $dependency) {
            if ($dependency instanceof Instance) {
                if ($dependency->id !== null) {
                    $dependencies[$index] = $this->get($dependency->id);
                } elseif ($reflection !== null) {
                    $name = $reflection->getConstructor()->getParameters()[$index]->getName();
                    $class = $reflection->getName();
                    throw new InvalidConfigException("Missing required parameter \"$name\" when instantiating \"$class\".");
                }
            }
        }
        return $dependencies;
    }
```

*	通过类名，获取反射类，通过反射类获取类对象构造函数所需要的参数信息，如果参数是默认的参数，则将默认
参数值放入数组$dependencies，如果对象属于类，则把类名放入$dependencies。

*	根据输入的参数$params,调用```$this->resolveDependencies```,将$dependencies依赖参数转换成实际类对象。获取要获取对象所依赖的对象数组$dependencies


*	通过```$reflection->newInstanceArgs($dependencies)```实例化要获取的对象，如果有配置config，则还需要
通过config设置对象的相应属性。


#### 通过回调函数对象的创建

在createObject函数中，只有当```is_callable($type, true)```条件成立的时候才会执行invoke方法。
```is_callable($type,true)```仅仅是检查语法是否正确，而不会检查函数是否真的存在，因此在invoke
函数里才会再次判断```is_callable($callback)```。只有当条件成立的时候才会执行注入操作，否则执行
简单的调用函数。


```

    public function invoke(callable $callback, $params = [])
    {
        if (is_callable($callback)) {
            return call_user_func_array($callback, $this->resolveCallableDependencies($callback, $params));
        } else {
            return call_user_func_array($callback, $params);
        }
    }

    public function resolveCallableDependencies(callable $callback, $params = [])
    {
        if (is_array($callback)) {
            $reflection = new \ReflectionMethod($callback[0], $callback[1]);
        } else {
            $reflection = new \ReflectionFunction($callback);
        }

        $args = [];

        $associative = ArrayHelper::isAssociative($params);

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            if (($class = $param->getClass()) !== null) {
                $className = $class->getName();
                if ($associative && isset($params[$name]) && $params[$name] instanceof $className) {
                    $args[] = $params[$name];
                    unset($params[$name]);
                } elseif (!$associative && isset($params[0]) && $params[0] instanceof $className) {
                    $args[] = array_shift($params);
                } elseif (isset(Yii::$app) && Yii::$app->has($name) && ($obj = Yii::$app->get($name)) instanceof $className) {
                    $args[] = $obj;
                } else {
                    // If the argument is optional we catch not instantiable exceptions
                    try {
                        $args[] = $this->get($className);
                    } catch (NotInstantiableException $e) {
                        if ($param->isDefaultValueAvailable()) {
                            $args[] = $param->getDefaultValue();
                        } else {
                            throw $e;
                        }
                    }

                }
            } elseif ($associative && isset($params[$name])) {
                $args[] = $params[$name];
                unset($params[$name]);
            } elseif (!$associative && count($params)) {
                $args[] = array_shift($params);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif (!$param->isOptional()) {
                $funcName = $reflection->getName();
                throw new InvalidConfigException("Missing required parameter \"$name\" when calling \"$funcName\".");
            }
        }

        foreach ($params as $value) {
            $args[] = $value;
        }
        return $args;
    }

```

根据回调函数，通过反射方法获取方法的参数列表，根据函数传入的参数，以及反射方法获取的参数列表生成
函数所需要的参数，执行方法回调。

