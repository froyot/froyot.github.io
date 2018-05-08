---
layout: post
title: Laravel5.6 博客搭建系列一
category: PHP
comments: true
description: PHP Laravel5.6 博客
keywords: Laravel,PHP,博客
---

Laravel框架目前已经发展到5.6版本了，但是目前官方的入门教程还是基于5.1的博客教程。为了更多的人能快速上手新版本，本教程使用Laravel5.6
一部一部跟大家分享如何搭建一个博客系统。下面来看一下如何用十分钟使用Laravel5.6搭建简单博客

### 安装环境
Laravel 框架对PHP版本和扩展有一定要求

*   PHP >= 7.1.3
*   PHP OpenSSL 扩展
*   PHP PDO 扩展
*   PHP Mbstring 扩展
*   PHP Tokenizer 扩展
*   PHP XML 扩展
*   PHP Ctype 扩展
*   PHP JSON 扩展

下载安装PHP7,composer,mysql
执行 ```composer global require "laravel/installer"```, 安装laravel之后配置环境变量,执行```laravel new blog```


### 配置

*   编辑.env 修改数据库用户名密码，数据库名称，.env本身是隐藏文件，注意开启显示隐藏文件

### 创建博客文章模型数据迁移文件
执行```php artisan make:model --migration Post```，会在database/migrations目录下建立"日期create_posts_table.php"文件，编辑该文件

```
Schema::create('posts', function (Blueprint $table) {
    $table->increments('id');
    $table->string('slug')->unique();
    $table->string('title');
    $table->text('content');
    $table->timestamps();
    $table->timestamp('published_at')->nullable()->index();
});

```

这其实是在设置文字内容表的数据字段
*   id 自增文章id
*   slug  seo url 唯一
*   title 标题
*   content 内容
*   timestamps()会自动创建一个默认是create_at的字段，用于记录数据的创建时间
*   published_at 发布时间 不能为空 并且建立索引


### 创建博客文章模型

命令同时会在app目录下建立一个Post.php文件，编辑文件

```
    protected $dates = ['published_at'];

    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;

        if (! $this->exists) {
            $this->attributes['slug'] = str_slug($value);
        }
    }

```

### 执行数据迁移，在数据库创建数据表

执行```php artisan migrate```，该命令会在数据库中根据配置创建数据表

### 填充数据
Laravel提供数据填充，添加factory 文件 database/factories/PostFactory.php，内容如下:

``` 
<?php
use Faker\Generator as Faker;
$factory->define(App\Post::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(mt_rand(3, 10)),
        'content' => join("\n\n", $faker->paragraphs(mt_rand(3, 6))),
        'published_at' => $faker->dateTimeBetween('-1 month', '+3 days'),
    ];
});
```

执行```php artisan make:seed PostsTableSeeder``` 创建生博客文章数据成器，命令会在database/seeds下建立PostsTableSeeder.php文件。然后执行***composer dump-autoload 导入（否则class not found)***

```
function run(){
    factory(App\Post::class, 50)->create();
}

```

编辑database/seeds/DatabaseSeeder.php中编辑

```
    public function run()
    {
        $this->call(PostsTableSeeder::class);
    }

```

执行 php artisan db:seed ,命令会根据数据生成器生成50条post数据


### 添加博客配置
在config目录添加blog.php文件，文件内容如下:

```
<?php
return [
        'title' => 'My Blog',
        'posts_per_page' => 5
];

```

### 添加路由
修改routes/web.php文件

```
Route::get('/', function () {
    return redirect('/blog');
});

Route::get('blog', 'BlogController@index');
Route::get('blog/{slug}', 'BlogController@showPost');

```

###  创建控制器

执行```php artisan make:controller BlogController```,在app/Http/Controllers下建立BlogController.php,修改内容如下

```
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Post;
use Illuminate\Support\Carbon;

class BlogController extends Controller
{
    public function index()
    {
        $posts = Post::where('published_at', '<=', Carbon::now())
                ->orderBy('published_at', 'desc')
                ->paginate(config('blog.posts_per_page'));

        return view('blog.index', compact('posts'));
    }

    public function showPost($slug)
    {
        $post = Post::whereSlug($slug)->firstOrFail();
        return view('blog.post')->withPost($post);
    }
}


```

### 创建视图模板

在resources/view下建立***blog文件夹***，分别添加index.blade.php,post.blade.php文件分别用户博客列表和详情的展示

index.blade.php

```
<html>
    <head>
        <title>{{ config('blog.title') }}</title>
        <link href="https://cdn.bootcss.com/bootstrap/4.1.0/css/bootstrap.css" rel="stylesheet">
    </head>
    <body>
        <div class="container">
            <h1>{{ config('blog.title') }}</h1>
            <h5>Page {{ $posts->currentPage() }} of {{ $posts->lastPage() }}</h5>
            <hr>
            <ul>
            @foreach ($posts as $post)
                <li>
                    <a href="/blog/{{ $post->slug }}">{{ $post->title }}</a>
                    <em>({{ $post->published_at }})</em>
                    <p>
                        {{ str_limit($post->content) }}
                    </p>
                </li>
            @endforeach
            </ul>
            <hr>
            {!! $posts->render() !!}
        </div>
    </body>
</html>

```

post.blade.php

```
<html>
    <head>
        <title>{{ $post->title }}</title>
        <link href="https://cdn.bootcss.com/bootstrap/4.1.0/css/bootstrap.css" rel="stylesheet">
    </head>
    <body>
        <div class="container">
            <h1>{{ $post->title }}</h1>
            <h5>{{ $post->published_at }}</h5>
            <hr>
                {!! nl2br(e($post->content)) !!}
            <hr>
            <button class="btn btn-primary" onclick="history.go(-1)">
                « Back
            </button>
        </div>
    </body>
</html>

```

### 效果
执行```php artisan serve``` 启动服务，在浏览器中输入http://127.0.0.1:8080 浏览器会跳转到http://127.0.0.1:8080/blog 并展示下面内容

![image](http://p4ou67wbp.bkt.clouddn.com/blog1.png)


### 如果报错:

*   1071 Specified key was too long; max key length is 767 bytes,修改App\Providers\AppServiceProvider.php，引入```use Illuminate\Support\Facades\Schema;``` ,在boot中添加:Schema::defaultStringLength(191);


本教程代码 [下载](http://p4ou67wbp.bkt.clouddn.com/blog1.zip)

