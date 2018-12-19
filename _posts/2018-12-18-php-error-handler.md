---
layout: post
title: 如何给PHP添加多个错误处理函数
category: php
comments: true
description: 如何给PHP添加多个错误处理函数
keywords: php,set_error_handler,trigger_error
---

一些常规的PHP框架都会对PHP的错误、异常进行异常处理封装，方便框架日志记录，开发的时候方便处理。我们先看看几个框架错误处理:

### Laravel

Laravel在app初始化的时候注册了错误处理函数，异常处理函数，异常退出处理函数，最终将错误转化成异常抛出，统一通过异常处理函数进行处理。

```php
    public function bootstrap(Application $app)
    {
        $this->app = $app;
        error_reporting(-1);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
        if (! $app->environment('testing')) {
            ini_set('display_errors', 'Off');
        }
    }
    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }
    public function handleShutdown()
    {
        if (! is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalExceptionFromError($error, 0));
        }
    }
```

### Yii2

Yii2 在application构造函数中初始化ErrorHandler组件，通过调用register方法注册错误处理，将PHP的错误转换成异常，通过异常处理方式显示处理。

```php
    public function register()
    {
        ini_set('display_errors', false);
        set_exception_handler([$this, 'handleException']);
        if (defined('HHVM_VERSION')) {
            set_error_handler([$this, 'handleHhvmError']);
        } else {
            set_error_handler([$this, 'handleError']);
        }
        if ($this->memoryReserveSize > 0) {
            $this->_memoryReserve = str_repeat('x', $this->memoryReserveSize);
        }
        register_shutdown_function([$this, 'handleFatalError']);
    }
    public function handleError($code, $message, $file, $line)
    {
        if (error_reporting() & $code) {
            if (!class_exists('yii\\base\\ErrorException', false)) {
                require_once __DIR__ . '/ErrorException.php';
            }
            $exception = new ErrorException($message, $code, $code, $file, $line);
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
            foreach ($trace as $frame) {
                if ($frame['function'] === '__toString') {
                    $this->handleException($exception);
                    if (defined('HHVM_VERSION')) {
                        flush();
                    }
                    exit(1);
                }
            }
            throw $exception;
        }
        return false;
    }
    public function handleFatalError()
    {
        unset($this->_memoryReserve);
        if (!class_exists('yii\\base\\ErrorException', false)) {
            require_once __DIR__ . '/ErrorException.php';
        }
        $error = error_get_last();
        if (ErrorException::isFatalError($error)) {
            if (!empty($this->_hhvmException)) {
                $exception = $this->_hhvmException;
            } else {
                $exception = new ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);
            }
            $this->exception = $exception;
            $this->logException($exception);
            if ($this->discardExistingOutput) {
                $this->clearOutput();
            }
            $this->renderException($exception);
            Yii::getLogger()->flush(true);
            if (defined('HHVM_VERSION')) {
                flush();
            }
            exit(1);
        }
    }
```

### Thinkphp5.1

thinkphp5.1在thinkphp\Base.php中使用Error::register()注册了错误处理函数。在错误处理函数中将错误转换成异常记录日志输出错误提示

```php
    public static function register()
    {
        error_reporting(E_ALL);
        set_error_handler([__CLASS__, 'appError']);
        set_exception_handler([__CLASS__, 'appException']);
        register_shutdown_function([__CLASS__, 'appShutdown']);
    }
    public static function appError($errno, $errstr, $errfile = '', $errline = 0)
    {
        $exception = new ErrorException($errno, $errstr, $errfile, $errline);
        if (error_reporting() & $errno) {
            throw $exception;
        }
        self::getExceptionHandler()->report($exception);
    }
    public static function appShutdown()
    {
        if (!is_null($error = error_get_last()) && self::isFatal($error['type'])) {
            $exception = new ErrorException($error['type'], $error['message'], $error['file'], $error['line']);
            self::appException($exception);
        }
        Container::get('log')->save();
    }
```

上述三种PHP框架对错误的处理都差不多，都使用的是``set_error_handler``,``register_shutdown_function``两个函数。

[set_error_handler](http://php.net/manual/zh/function.set-error-handler.php),设置用户自定义的错误处理函数

> ``mixed set_error_handler ( callable $error_handler [, int $error_types = E_ALL | E_STRICT ] )``
>本函数可以用你自己定义的方式来处理运行中的错误， 例如，在应用程序中严重错误发生时，或者在特定条件下触发了一个错误(使用 trigger_error())，你需要对数据/文件做清理回收。
>以下级别的错误不能由用户定义的函数来处理： E_ERROR、 E_PARSE、 E_CORE_ERROR、 E_CORE_WARNING、 E_COMPILE_ERROR、 E_COMPILE_WARNING，和在 调用 set_error_handler() 函数所在文件中产生的大多数 E_STRICT。
>如果错误发生在脚本执行之前（比如文件上传时），将不会 调用自定义的错误处理程序因为它尚未在那时注册。

[register_shutdown_function](http://php.net/manual/zh/function.register-shutdown-function.php),设置用户自定义的错误处理函数

> ``void register_shutdown_function ( callable $callback [, mixed $parameter [, mixed $... ]] )``
> 注册一个 callback ，它会在脚本执行完成或者 exit() 后被调用。
> 可以多次调用 register_shutdown_function() ，这些被注册的回调会按照他们注册时的顺序被依次调用。 如果你在注册的方法内部调用 exit()， 那么所有处理会被中止，并且其他注册的中止回调也不会再被调用。


思考这么一种场景，使用PHP框架开发，但是在某个模块，需要监听特定的E_USER_ERROR,E_USER_WARNING,E_USER_NOTICE等错误。或者说项目刚上线，需要将一些notice错误通过邮件报告给开发人员，而不需要对框架底层做修改。这就需要能够添加多个错误处理函数，遇到第一个有效处理函数，则执行，否则继续到下一个错误处理函数中处理。 对于set_error_handler是可以的。



```php
set_error_handler(function($errno, $errstr ,$errfile, $errline){
   echo "defaulthandler:".$errstr."</br>";
});
class Logger{
    protected $defaultErrorHandler = null;
    function registerError(){
        $this->defaultErrorHandler = set_error_handler([$this,'errorhandler']);
    }
    function errorhandler($errno, $errstr ,$errfile, $errline){
        if(strpos($errstr,'Undefined variable:')===0)
        {
            echo "errorhandler:".$errno.$errstr."</br>";
        }
        else
        {
            call_user_func_array($this->defaultErrorHandler, [$errno, $errstr ,$errfile, $errline]); 
        }
    }
}
$log = new Logger();
$log->registerError();


function test(){
   $P = $QQ;
   trigger_error("Cannot divide by zero", E_USER_ERROR);
}
test();
```
以上代码输出内容为：
```
errorhandler:8Undefined variable: QQ
defaulthandler:Cannot divide by zero
```

因为set_error_handler返回参数是本次设置之前最后的错误处理函数。当我们设置回调函数的同时也能保持上一个回调函数，因此在我们的回调函数中如果遇到不符合要求的错误，还是可以调用上一个错误处理函数。
