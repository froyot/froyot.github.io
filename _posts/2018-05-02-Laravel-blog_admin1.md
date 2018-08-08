---
layout: post
title: Laravel5.6 博客搭建系列二--搭建后台管理系统
category: PHP
comments: true
description: PHP Laravel5.6 博客，后台管理
keywords: Laravel,PHP,博客
---


####  创建用户认证系统


本篇文章跟大家分享搭建后台管理认证系统以及创建后台视图模板


Laravel 中实现登录认证非常简单。实际上，几乎所有东西 Laravel 都已经为你配置好了。配置文件位于 config/auth.php，其中包含了用于调整认证服务行为的、文档友好的选项配置。

执行```php artisan make:auth``` 和 ```php artisan migrate``` 创建控制器以及需要的数据表。脚本会在目录app/Http/Auth 下创建一下几个文件:

*   创建必须的控制器

    -   LoginController 登录退出操作，继承App\Http\Controllers\Controller，所有的业务逻辑在```trait AuthenticatesUsers```中，可以通过设置属性```$redirectTo```改变登录之后的跳转地址，设置```$redirectAfterLogout```改变退出之后的跳转地址;

    -   RegisterController 提供用户注册相关操作，所有业务逻辑在```trait RegistersUsers```中

    -   ForgotPasswordController 忘记密码，发送验证邮件相关操作

    -   ResetPasswordController 重置密码相关操作

*   添加路由:

routes/web.php文件中添加用户认证相关路由

```
Auth::routes();

```

*   创建视图模板文件

    -layouts 文件夹，创建app.blade.php 作为整个应用的视图模板文件

    -auth 文件夹，分别创建登录，注册，找回密码等视图文件


#### 创建后台管理首页

执行以下命令:

```
php artisan make:controller Admin\\DefaultController

```
脚本会在app\Http\Controlles下创建admin目录，并创建DefaultController文件，修改DefaultController文件，添加后台显示文章列表操作，添加以下代码:

```
    public function index()
    {
        return view('admin.default.index');
    }

```

*   创建视图文件

在resources下创建admin/post目录，并在该目录下创建admin/post/index.blade.php文件，文件内容如下:

```
@extends('layouts.app')

@section('content')
<div class="container">
    welcome to Post Admin
</div>
@endsection


```

*   修改路由route/web.php,限制后台必须登录

```

Route::get('admin', function () {
    return redirect('/admin/default');
});
Route::namespace('Admin')->middleware(['auth'])->group(function () {
    Route::resource('admin/default', 'DefaultController');
});

```


#### 创建后台模板

很多情况下前后台使用的模板不同，因此需要给后台自定义视图模板。复制一份resources/layouts/app.blade.php 到resources/admin/layouts/main.blade.php

在
```html
<a class="navbar-brand" href="{{ url('/') }}">
    {{ config('app.name', 'Laravel') }}
</a>

```

后面加入以下内容，给后台添加导航栏


```html

<ul class="nav">
  <li class="nav-item">
    <a class="nav-link active" href="{{route('default.index')}}">{{ __('Dashbord') }}</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="#">{{ __('Posts') }}</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="#">{{ __('Tags') }}</a>
  </li>
  <li class="nav-item">
    <a class="nav-link " href="#">{{ __('Files') }}</a>
  </li>
</ul>

```

在```</head>```前添加@yield('styles')，在```</body>```前添加@yield('scripts'),创建样式以及脚本模块，后续在视图文件中添加样式和js脚本

#### 效果

访问 ```http://127.0.0.1:8000/admin/default``` 可以看到以下内容


![image](http://blog.static.aiaiaini.com/blog2.png)


本教程代码[下载](http://blog.static.aiaiaini.com/blog2.zip)



