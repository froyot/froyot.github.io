---
layout: post
title: Laravel5.6 博客搭建系列三--博客内容增删改查
category: PHP
comments: true
description: PHP Laravel5.6 博客，博客内容增删改查,Markdown
keywords: Laravel,PHP,博客
---

本篇内容分享创建后台博客内容增删改查操作。


#### 创建markdown转html Service

要想实现markdown 到html的转换，需要安装两个依赖库:

```
composer require michelf/php-markdown
composer require michelf/php-smartypants
```
在app下创建Services目录，存放相应的服务类文件。在app/Services创建Markdowner.php，实习markdown转html,文件内容如下:

```
<?php

namespace App\Services;

use Michelf\MarkdownExtra;
use Michelf\SmartyPants;

class Markdowner
{

    public function toHTML($text)
    {
        $text = $this->preTransformText($text);
        $text = MarkdownExtra::defaultTransform($text);
        $text = SmartyPants::defaultTransform($text);
        $text = $this->postTransformText($text);
        return $text;
    }

    protected function preTransformText($text)
    {
        return $text;
    }

    protected function postTransformText($text)
    {
        return $text;
    }
}

```


#### 修改表结构

*	修改数据表的列，需要安装 Doctrine 依赖包，我们使用 Composer 安装该依赖包：

```
composer require "doctrine/dbal"
```
*	使用 Artisan 命令创建新的迁移文件：

```
php artisan make:migration --table=posts restructure_posts_table

```

*	编辑刚刚创建的迁移文件：

```

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RestructurePostsTable extends Migration
{
 /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('subtitle')->after('title'); 
            $table->renameColumn('content', 'content_raw'); 
            $table->text('content_html')->after('content');
            $table->string('meta_description')->after('content_html'); 
            $table->boolean('is_draft')->after('meta_description'); 
            $table->string('layout')->after('is_draft')->default('blog.layouts.post'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('layout');
            $table->dropColumn('is_draft');
            $table->dropColumn('meta_description');
            $table->dropColumn('content_html');
            $table->renameColumn('content_raw', 'content');
            $table->dropColumn('subtitle');
        });
    }
}

```

表字段说明：

	-	subtitle：文章副标题
	-	content_raw：Markdown格式文本
	-	content_html：使用 Markdown 编辑内容但同时保存 HTML 版本
	-	meta_description：文章备注说明
	-	is_draft：该文章是否是草稿
	-	layout：使用的布局


*	迁移已经创建并编辑好了，运行该迁移：

```
php artisan migrate

```

*	修改model文件app\Post.php

```
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Services\Markdowner;
class Post extends Model
{
    protected $dates = ['published_at'];
    protected $fillable = [
        'title', 'subtitle', 'content_raw', 'meta_description','layout', 'is_draft', 'published_at'
    ];
    /**
     * Set the title attribute and automatically the slug
     *
     * @param string $value
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;

        if (! $this->exists) {
            $this->setUniqueSlug($value, '');
        }
    }

    /**
     * Recursive routine to set a unique slug
     *
     * @param string $title
     * @param mixed $extra
     */
    protected function setUniqueSlug($title, $extra)
    {
        $slug = str_slug($title.'-'.$extra);

        if (static::whereSlug($slug)->exists()) {
            $this->setUniqueSlug($title, $extra + 1);
            return;
        }

        $this->attributes['slug'] = $slug;
    }

    /**
     * Set the HTML content automatically when the raw content is set
     *
     * @param string $value
     */
    public function setContentRawAttribute($value)
    {
        $markdown = new Markdowner();

        $this->attributes['content_raw'] = $value;
        $this->attributes['content_html'] = $markdown->toHTML($value);
    }

    public function getContentAttribute($value)
    {
        return $this->content_raw;
    }
}

```

#### 创建路由

对后台路由进行修改

```
Route::namespace('Admin')->middleware(['auth'])->group(function () {
    Route::resource('admin/default', 'DefaultController');
    Route::resource('admin/post', 'PostController');
});
```

后台视图模板resources/admin/layouts/main.blade.php中设置文章练tab链接

{% raw %}

```
<a class="nav-link" href="{{route('post.index')}}">{{ __('Posts') }}</a>
```
{% endraw %}

#### 修改控制器

*	创建表单请求类

使用表单请求类来验证文件创建及更新请求。首先，使用 Artisan 命令创建表单请求处理类，对应文件会生成在 app/Http/Requests 目录下：

```
php artisan make:request PostCreateRequest 
php artisan make:request PostUpdateRequest
```

编辑新创建的 PostCreateRequest.php 内容如下：

```
<?php

namespace App\Http\Requests;

use Carbon\Carbon;

class PostCreateRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required',
            'subtitle' => 'required',
            'content' => 'required',
            'publish_date' => 'required',
            'publish_time' => 'required',
            'layout' => 'required',
        ];
    }

    /**
     * Return the fields and values to create a new post from
     */
    public function postFillData()
    {
        $published_at = new Carbon(
            $this->publish_date.' '.$this->publish_time
        );
        return [
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'page_image' => $this->page_image,
            'content_raw' => $this->get('content'),
            'meta_description' => $this->meta_description,
            'is_draft' => (bool)$this->is_draft,
            'published_at' => $published_at,
            'layout' => $this->layout,
        ];
    }
}
```

PostUpdateRequest与PostCreateRequest类似，因此只需继承PostCreateRequest

```
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostUpdateRequest extends PostCreateRequest
{

}
```

*	创建 PostFormFields 任务

创建一个公用的、可以从 PostController 中调用的任务类，我们将其称之为PostFormFields。该任务会在我们想要获取文章所有字段填充文章表单时被执行。

首先使用 Artisan 命令创建任务类模板：

```
php artisan make:job PostFormFields
```

创建的任务类位于 app/Jobs 目录下。编辑新生成的 PostFormFields.php 文件内容如下：

```
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Post;
class PostFormFields implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id;
    protected $fieldList = [
        'title' => '',
        'subtitle' => '',
        'page_image' => '',
        'content' => '',
        'meta_description' => '',
        'is_draft' => "0",
        'publish_date' => '',
        'publish_time' => '',
        'layout' => 'blog.layouts.post',
        'tags' => [],
    ];
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id = null)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $fields = $this->fieldList;

        if ($this->id) {
            $fields = $this->fieldsFromModel($this->id, $fields);
        } else {
            $when = Carbon::now()->addHour();
            $fields['publish_date'] = $when->format('M-j-Y');
            $fields['publish_time'] = $when->format('g:i A');
        }

        foreach ($fields as $fieldName => $fieldValue) {
            $fields[$fieldName] = old($fieldName, $fieldValue);
        }

        return $fields;
    }

    protected function fieldsFromModel($id, array $fields)
    {
        $post = Post::findOrFail($id);

        $fieldNames = array_keys($fields);

        $fields = ['id' => $id];
        foreach ($fieldNames as $field) {
            $fields[$field] = $post->{$field};
        }

        return $fields;
    }
}

```

该任务最终返回文章字段和值的键值对数组，我们将使用其返回结果用来填充文章编辑表单。如果 Post 模型未被加载（比如创建文章时），那么就会返回默认值。如果 Post 被成功加载（文章更新），那么就会从数据库获取值。

*	创建文章CURD控制器

```
<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Post;
use Illuminate\Support\Carbon;
use App\Jobs\PostFormFields;
use App\Http\Requests;
use App\Http\Requests\PostCreateRequest;
use App\Http\Requests\PostUpdateRequest;

class PostController extends Controller
{
	public function index()
	{
        $posts = Post::where('published_at', '<=', Carbon::now())
                ->orderBy('published_at', 'desc')
                ->paginate(config('blog.posts_per_page'));
        return view('admin.post.index', compact('posts'));
	}
    /**
     * Show the new post form
     */
    public function create()
    {
        $data = $this->dispatch(new PostFormFields());

        return view('admin.post.create', $data);
    }

    /**
     * Store a newly created Post
     *
     * @param PostCreateRequest $request
     */
    public function store(PostCreateRequest $request)
    {
        $post = Post::create($request->postFillData());
        $post->syncTags($request->get('tags', []));

        return redirect()
                        ->route('admin.post.index')
                        ->withSuccess('New Post Successfully Created.');
    }

    /**
     * Show the post edit form
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        $data = $this->dispatch(new PostFormFields($id));

        return view('admin.post.edit', $data);
    }

    /**
     * Update the Post
     *
     * @param PostUpdateRequest $request
     * @param int $id
     */
    public function update(PostUpdateRequest $request, $id)
    {
        $post = Post::findOrFail($id);
        $post->fill($request->postFillData());
        $post->save();
        $post->syncTags($request->get('tags', []));

        if ($request->action === 'continue') {
            return redirect()
                            ->back()
                            ->withSuccess('Post saved.');
        }

        return redirect()
                        ->route('admin.post.index')
                        ->withSuccess('Post saved.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $post->tags()->detach();
        $post->delete();

        return redirect()
                        ->route('admin.post.index')
                        ->withSuccess('Post deleted.');
    }
}

```

#### 创建视图文件

*   创建辅助提示视图文件
resources/views/admin/partials目录下创建error.blade.php,success.blade.php 分别用于错误提示和成功提示

error.blade.php文件内容如下:

{% raw %}

```html
@if (count($errors) > 0)
    <div class="alert alert-danger">
        <strong>Whoops!</strong>
        There were some problems with your input.<br><br>
        <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
        </ul>
    </div>
@endif
```
{% endraw %}


success.blade.php 文件内容如下:

{% raw %}

```
@if (Session::has('success'))
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <strong>
            <i class="fa fa-check-circle fa-lg fa-fw"></i> Success. 
        </strong>
        {{ Session::get('success') }}
    </div>
@endif
```
{% endraw %}

*   创建博客列表视图文件

在 resources/views/admin/post 目录下创建 index.blade.php：

{% raw %}

```html
@extends('admin.layouts.main')
@section('content')
<div class="container">
    <div class="row page-title-row">
        <div class="col-md-6">
            
        </div>
        <div class="col-md-6 text-right">
            <a href="{{route('post.create')}}" class="btn btn-success btn-md">
                <i class="fa fa-plus-circle"></i> New Post
            </a>
        </div>
    </div>
    @include('admin.partials.error')
    @include('admin.partials.success')
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
        {{ $posts->links() }}
    </div>
</div>
@endsection

```
{% endraw %}


*   创建编辑博客视图

创建表单_form.blade.php：

{% raw %}

```html
<div class="row">
    <div class="col-md-8">
        <div class="form-group">
            <label for="title" class="col-md-2 control-label">
                Title
            </label>
            <div class="col-md-10">
                <input type="text" class="form-control" name="title" autofocus id="title" value="{{ $title }}">
            </div>
        </div>
        <div class="form-group">
            <label for="subtitle" class="col-md-2 control-label">
                Subtitle
            </label>
            <div class="col-md-10">
                <input type="text" class="form-control" name="subtitle" id="subtitle" value="{{ $subtitle }}">
            </div>
        </div>
        <div class="form-group">
            <label for="content" class="col-md-2 control-label">
                Content
            </label>
            <div class="col-md-10">
                 <textarea class="form-control" name="content" rows="14" id="content">{{ $content }}</textarea>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="publish_date" class="col-md-3 control-label">
                Pub Date
            </label>
            <div class="col-md-8">
                <input class="form-control" name="publish_date" id="publish_date" type="text" value="{{ $publish_date }}">
            </div>
        </div>
        <div class="form-group">
            <label for="publish_time" class="col-md-3 control-label">
                Pub Time
            </label>
            <div class="col-md-8">
                <input class="form-control" name="publish_time" id="publish_time" type="text" value="{{ $publish_time }}">
            </div>
        </div>
        <div class="form-group">
            <div class="col-md-8 col-md-offset-3">
                <div class="checkbox">
                    <label>
                        <input {{ $is_draft?'checked':'' }} type="checkbox" name="is_draft">
                        Draft?
                    </label>
                 </div>
            </div>
        </div>
        <div class="form-group">
            <label for="layout" class="col-md-3 control-label">
                Layout
            </label>
            <div class="col-md-8">
                <input type="text" class="form-control" name="layout" id="layout" value="{{ $layout }}">
            </div>
        </div>
        <div class="form-group">
            <label for="meta_description" class="col-md-3 control-label">
                Meta
            </label>
            <div class="col-md-8">
                <textarea class="form-control" name="meta_description" id="meta_description" rows="6">{{ $meta_description }}</textarea>
            </div>
        </div>
    </div>
</div>
```

{% endraw %}

*   创建编辑视图文件edit.blade.php

{% raw %}

```html
@extends('admin.layouts.main')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-sm-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    @include('admin.partials.error')
                    @include('admin.partials.success')
                    <form class="form-horizontal" role="form" method="POST" action="{{ route('post.update', $id) }}">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="PUT">
                        @include('admin.post._form')
                        <div class="col-md-8">
                            <div class="form-group">
                                <div class="col-md-10 col-md-offset-2">
                                    <button type="submit" class="btn btn-primary" name="action" value="continue">
                                        <i class="fa fa-floppy-o"></i>
                                            Save - Continue
                                    </button>
                                    <button type="submit" class="btn btn-success" name="action" value="finished">
                                        <i class="fa fa-floppy-o"></i>
                                            Save - Finished
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

```

{% endraw %}

*   创建新建视图文件create.blade.php

{% raw %}

```html
@extends('admin.layouts.main')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-sm-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    @include('admin.partials.error')
                    @include('admin.partials.success')
                    <form class="form-horizontal" role="form" method="POST" action="{{ route('post.store') }}">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        @include('admin.post._form')
                        <div class="col-md-8">
                            <div class="form-group">
                                <div class="col-md-10 col-md-offset-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fa fa-disk-o"></i>
                                        Save New Post
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

```
{% endraw %}

*   创建删除

resource路由删除操作需要的请求方式是delete,form 没办法发起delete请求，Laravel 在表单中添加_method="delete"模拟。
创建js文件，实现公共的删除操作，在resouces/assets/js/deletemoda.js,在js中，给每个页面填充一个隐藏表单，点击删除的时候根据删除内容发起删除请求。


效果如下:
![image](http://blog.static.aiaiaini.com/blog3.png)

本教程代码[下载](http://blog.static.aiaiaini.com/blog3.zip)


