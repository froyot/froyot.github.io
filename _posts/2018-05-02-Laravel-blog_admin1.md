---
layout: post
title: Laravel5.6 博客搭建系列一
category: PHP
comments: true
description: PHP Laravel5.6 博客
keywords: Laravel,PHP,博客
---

### 搭建后台管理系统

####	创建用户认证系统

Laravel 中实现登录认证非常简单。实际上，几乎所有东西 Laravel 都已经为你配置好了。配置文件位于 config/auth.php，其中包含了用于调整认证服务行为的、文档友好的选项配置。

执行```php artisan make:auth``` 和 ```php artisan migrate``` 创建控制器以及需要的数据表。脚本会在目录app/Http/Auth 下创建一下几个文件:

*	LoginController 登录退出操作，继承App\Http\Controllers\Controller，所有的业务逻辑在```trait AuthenticatesUsers```中，可以通过设置属性```$redirectTo```改变登录之后的跳转地址，设置```$redirectAfterLogout```改变退出之后的跳转地址;

*	RegisterController 提供用户注册相关操作，所有业务逻辑在```trait RegistersUsers```中

*	ForgotPasswordController 忘记密码，发送验证邮件相关操作

*	ResetPasswordController 重置密码相关操作

同时会在routes/web.php文件中添加用户认证相关路由

```
Auth::routes();

```

####	创建博客管理

*	创建控制器
执行以下命令:
```
php artisan make:controller Admin\\PostController

```
脚本会在app\Http\Controlles下创建admin目录，并创建PostController文件，修改PostController文件，添加后台显示文章列表操作，添加以下代码:

```
	public function index()
	{
	    return view('admin.post.index');
	}

```

*	创建视图文件

在resources下创建admin/post目录，并在该目录下创建admin/post/index.blade.php文件，文件内容如下:

```
@extends('layouts.app')

@section('content')
    <div class="container">
    <div class="row page-title-row">
        <div class="col-md-6">
            <h3>Posts <small>» Listing</small></h3>
        </div>
        <div class="col-md-6 text-right">
            <a href="/admin/post/create" class="btn btn-success btn-md">
                <i class="fa fa-plus-circle"></i> New Post
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">


            <table id="posts-table" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Published</th>
                    <th>Title</th>
                    <th>Subtitle</th>
                    <th data-sortable="false">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($posts as $post)
                <tr>
                    <td data-order="{{ $post->published_at->timestamp }}">
                        {{ $post->published_at->format('j-M-y g:ia') }}
                    </td>
                    <td>{{ $post->title }}</td>
                    <td>{{ $post->subtitle }}</td>
                    <td>
                        <a href="/admin/post/{{ $post->id }}/edit" class="btn btn-xs btn-info">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="/blog/{{ $post->slug }}" class="btn btn-xs btn-warning">
                            <i class="fa fa-eye"></i> View
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
            </table>
        </div>
    </div>

</div>
@stop

@section('scripts')
<script>
    $(function() {
        $("#posts-table").DataTable({
            order: [[0, "desc"]]
        });
    });
</script>
@stop

```

*	修改路由route/web.php

```
// Admin area
Route::get('admin', function () {
    return redirect('/admin/post');
});
Route::namespace('Admin')->middleware(['auth'])->group(function () {
    Route::resource('admin/post', 'PostController');
});

```

*	访问地址:http://127.0.0.1:8000/admin/post

看到以下内容:

![image](http://p4ou67wbp.bkt.clouddn.com/blog_larval56_admin1.png)


