---
layout: post
title: Laravel5.6 博客搭建系列四--文章标签后台管理
category: PHP
comments: true
description: Laravel5.6 博客搭建系列四,文章标签后台增删改查
keywords: Laravel,PHP,博客,CURD
---

#### 创建标签模型和迁移
首先需要创建 Tag 模型类：```php artisan make:model --migration Tag```该命令会在 app 目录下创建模型文件 Tag.php，由于我们在 make:model 命令中使用了 --migration 选项，所以同时会创建  Tag 模型对应的数据表迁移。在标签（Tag）和文章（Post）之间存在多对多的关联关系，因此还要按照下面的命令创建存放文章和标签对应关系的数据表迁移：```php artisan make:migration --create=post_tag_pivot create_post_tag_pivot```


在 database/migrations 目录下编辑新创建的标签迁移文件内容如下：
```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('tag')->unique();
            $table->string('title');
            $table->string('subtitle');
            $table->string('page_image');
            $table->string('meta_description');
            $table->string('layout')->default('blog.layouts.index');
            $table->boolean('reverse_direction');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tags');
    }
}

```
对上面迁移文件中新增的字段作简要说明：
*   page_image：标签图片
*   meta_description：标签介绍
*   layout：博客终归要使用布局
*   reverse_directions：在文章列表按时间升序排列博客文章（默认是降序）

编辑文章与标签对应关系表迁移文件内容如下：

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostTagPivot extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_tag_pivot', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id')->unsigned()->index();
            $table->integer('tag_id')->unsigned()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('post_tag_pivot');
    }
}

```

运行迁移```php artisan migrate```


#### 创建控制器添加路由

运行```php artisan make:controller Admin\\TagController```创建控制器文件。

在routes/web.php文件中,将管理员路由改成以下内容:

```php
Route::namespace('Admin')->middleware(['auth'])->group(function () {
    Route::resource('admin/default', 'DefaultController');
    Route::resource('admin/post', 'PostController');
    Route::resource('admin/tag', 'TagController');
});
```

#### 实现标签列表
在TagController 中添加index方法,具体引入的类可以参照之前的Post。

```php
    public function index()
    {
        $tags = Tag::orderBy('created_at', 'desc')
                ->paginate(config('blog.posts_per_page'));
        return view('admin.tag.index')->withTags($tags);
    }

```
在resources/views/admin/tag下创建index.blade.php视图文件

```html
@extends('admin.layouts.main')
@section('content')
<div class="container">
    <div class="row page-title-row">
        <div class="col-md-6">
            
        </div>
        <div class="col-md-6 text-right">
            <a href="{{route('tag.create')}}" class="btn btn-success btn-md">
                <i class="fa fa-plus-circle"></i> New Tag
            </a>
        </div>
    </div>
    <div class="row">
            <div class="col-sm-12">
            @include('admin.partials.error')
            @include('admin.partials.success')
            <table id="posts-table" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Tag</th>
                            <th>Title</th>
                            <th class="hidden-sm">Subtitle</th>
                            <th class="hidden-md">Page Image</th>
                            <th class="hidden-md">Meta Description</th>
                            <th class="hidden-md">Layout</th>
                            <th class="hidden-sm">Direction</th>
                            <th data-sortable="false">Actions</th>
                        </tr>
                     </thead>
                    <tbody>
                    @foreach ($tags as $tag)
                        <tr>
                            <td>{{ $tag->tag }}</td>
                            <td>{{ $tag->title }}</td>
                            <td class="hidden-sm">{{ $tag->subtitle }}</td>
                            <td class="hidden-md">{{ $tag->page_image }}</td>
                            <td class="hidden-md">{{ $tag->meta_description }}</td>
                            <td class="hidden-md">{{ $tag->layout }}</td>
                            <td class="hidden-sm">
                                @if ($tag->reverse_direction)
                                    Reverse
                                @else
                                    Normal
                                @endif
                            </td>
                            <td>
                                <a href="/admin/tag/{{ $tag->id }}/edit" class="btn btn-xs btn-info">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                                <!-- 删除操作，因为需要delete提交，必须使用表单或者ajax模拟表单提交-->
                                <form style="display:inline" action="/admin/tag/{{ $tag->id }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        {{ __('Delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
            @endforeach
            </tbody>
            </table>
        </div>
        {{ $tags->links() }}
    </div>
</div>
@endsection
```

到这里，控制器方法，模板创建成功。访问```http://127.0.0.1:8000/admin/tag```可以看到界面显示出来了，但是没有数据。因此需要填充一些数据。

运行```php artisan make:seeder TagsTableSeeder```创建生seeder,命令会在database\seeds目录下创建TagsTableSeeder.php文件,文件内容如下:

```php
<?php

use Illuminate\Database\Seeder;

class TagsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        factory(App\Tag::class, 50)->create();
    }
}
```

在database\factories下创建TagFactory.php文件，文件内容如下:

```php
<?php
use Faker\Generator as Faker;
$factory->define(App\Tag::class, function (Faker $faker) {
    $t = $faker->dateTimeBetween('-1 month', '+3 days');
    return [
        'tag' => $faker->sentence(mt_rand(1, 3)),
        'title' => $faker->sentence(mt_rand(3, 10)),
        'subtitle' => $faker->sentence(mt_rand(3, 10)),
        'page_image'=>'',
        'meta_description'=>'',
        'layout'=>'',
        'reverse_direction'=>1,
        'created_at' => $t,
        'created_at' => $t,
    ];
});
```
执行 **composer dump-autoload**，保证新增的两个文件能够正常引入。执行命令```php artisan db:seed --class=TagsTableSeeder``` 生成tag的假数据。

**执行过程有可能会报错**，因为tag的唯一性冲突，可以直接跳过，我们只是生成假数据方便展示而已。

重新访问```http://127.0.0.1:8000/admin/tag```可以看到列表中已经填充了很多数据。

![image](http://blog.static.aiaiaini.com/larval-tag-list-xdsdf892j34ak23kz8234ka82ksad028ksd91243.png)


#### 创建标签
执行命令```php artisan make:job TagFormFields``` 创建一个表单数据字段创建Job，生成文件在app\Jobs\TagFormFields.php目录下。修改内容如下:
```
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Tag;
use Illuminate\Support\Carbon;
class TagFormFields 
{
    use Dispatchable, Queueable;
    protected $id;
    protected $fieldList = [
        'title' => '',
        'subtitle' => '',
        'tag' => '',
        'page_image' => '',
        'meta_description' => '',
        'created_date' => '',
        'created_time' => '',
        'layout' => 'blog.layouts.tag',
        'reverse_direction' => 1,
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
            $fields['created_date'] = $when->format('M-j-Y');
            $fields['created_time'] = $when->format('g:i A');
        }


        foreach ($fields as $fieldName => $fieldValue) {
            $fields[$fieldName] = old($fieldName, $fieldValue);
        }
        return $fields;
    }

    protected function fieldsFromModel($id, array $fields)
    {
        $tag = Tag::findOrFail($id);

        $fieldNames = array_keys($fields);

        $fields = ['id' => $id];
        foreach ($fieldNames as $field) {
            $fields[$field] = $tag->{$field};
        }
        if($tag->created_at)
        {
            $when = new Carbon($tag->created_at);
            $fields['created_date'] = $when->format('M-j-Y');
            $fields['created_time'] = $when->format('g:i A');
        }

        return $fields;
    }
}
```

执行命令```php artisan make:request TagCreateRequest```，在app\Http\Requests目录下创建TagCreateRequest.php，修改内容如下:

```php

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class TagCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
        $id = $this->route('tag');
        return [
            'title' => 'required',
            'subtitle' => 'required',
            'tag' => 'unique:tags,tag,'.$id,//避免编辑的时候相同的id也提示tag重复
            'created_date' => 'required',
            'created_time' => 'required',
            'layout' => 'required',
            'reverse_direction'=>'required',
        ];
    }

    public function tagFillData()
    {
        $created_at = new Carbon(
            $this->created_date.' '.$this->created_time
        );
        return [
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'tag' => $this->tag,
            'meta_description' => $this->meta_description?$this->meta_description:'',
            'reverse_direction' => (bool)$this->reverse_direction,
            'created_at' => $created_at,
            'layout' => $this->layout,
            'page_image'=>'',
        ];
    }
}
```
对数据表单进行验证，格式化成数据库需要的数据。

在TagController中添加create,store方法。内容如下，注意添加TagCreateRequest，TagFormFields的引用

```
    /**
     * Show the new tag form
     */
    public function create(){
        $data = $this->dispatch(new TagFormFields());
        return view('admin.tag.create', $data);
    }

    /**
     * Store a newly created tag
     *
     * @param PostCreateRequest $request
     */
    public function store(TagCreateRequest $request)
    {
        $tag = Tag::create($request->tagFillData());
        return redirect()->route('tag.index')
                        ->withSuccess('New Tag Successfully Created.');
    }

```

在resources\views\admin\tag下添加create.blade.php视图文件

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
                    <form class="form-horizontal" role="form" method="POST" action="{{ route('tag.store') }}">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        @include('admin.tag._form')
                        <div class="col-md-8">
                            <div class="form-group">
                                <div class="col-md-10 col-md-offset-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fa fa-disk-o"></i>
                                        Save New Tag
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
create视图文件引入了表单视图。在相同文件夹下创建_form.blade.php视图文件:

```
<div class="row">
    <div class="col-md-8">
        <div class="form-group">
            <label for="title" class="col-md-2 control-label">
                Tag
            </label>
            <div class="col-md-10">
                <input type="text" class="form-control" name="tag" autofocus id="tag" value="{{ $tag }}">
            </div>
        </div>
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
            <label for="meta_description" class="col-md-3 control-label">
                Meta
            </label>
            <div class="col-md-10">
                <textarea class="form-control" name="meta_description" id="meta_description" rows="6">{{ $meta_description }}</textarea>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="created_date" class="col-md-3 control-label">
                Pub Date
            </label>
            <div class="col-md-8">
                <input class="form-control" name="created_date" id="created_date" type="text" value="{{ $created_date }}">
            </div>
        </div>
        <div class="form-group">
            <label for="created_time" class="col-md-3 control-label">
                Pub Time
            </label>
            <div class="col-md-8">
                <input class="form-control" name="created_time" id="created_time" type="text" value="{{ $created_time }}">
            </div>
        </div>
        <div class="form-group">
            <div class="col-md-8 col-md-offset-3">
                <div class="checkbox">
                    <label>
                        <input {{ $reverse_direction?'checked':'' }} type="checkbox" name="reverse_direction">
                        reverse_direction?
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


    </div>
</div>


```

到这里标签创建的部分就结束了，访问```http://127.0.0.1:8000/admin/tag/create```可以看到以下内容
![image](http://blog.static.aiaiaini.com/larval-tag-create-xdsdf892j34ak23kz8234ka82ksad028ksd91243.png)

完善页面信息，点击保存，可以看到标签创建成功，并重定向到了标签列表页面。

#### 标签编辑

执行命令```php artisan make:request TagUpdateRequest```，在app\Http\Requests目录下创建TagUpdateRequest.php，修改内容如下:
```
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TagUpdateRequest extends TagCreateRequest
{

}
```
控制器添加edit,update方法

```
    public function edit($id)
    {
        $data = $this->dispatch(new TagFormFields($id));
        return view('admin.tag.edit', $data);
    }
    /**
     * Update the Post
     *
     * @param PostUpdateRequest $request
     * @param int $id
     */
    public function update(TagUpdateRequest $request, $id)
    {
        $tag = Tag::findOrFail($id);
        $tag->fill($request->tagFillData());
        $post->save();
        if ($request->action === 'continue') {
            return redirect()->back()->withSuccess('Tag saved.');
        }
        return redirect()->route('tag.index')->withSuccess('Tag saved.');
    }

```

在resources\views\admin\tag下添加edit.blade.php

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
                    <form class="form-horizontal" role="form" method="POST" action="{{ route('tag.update', $id) }}">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="PUT">
                        @include('admin.tag._form')
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

#### 删除标签

在TagController下添加以下内容:
```
    public function destroy($id)
    {
        $post = Tag::findOrFail($id);
        $post->delete();

        return redirect()->route('tag.index')->withSuccess('Tag deleted.');
    }

```

在index.blade.php中的edit后面添加以下内容:

```php
<form style="display:inline" action="/admin/tag/{{ $tag->id }}" method="post">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-danger">
        {{ __('Delete') }}
    </button>
</form>

```
因为使用的是resouce路由，laravel的resouce路由只接受delete提交或者模拟delete提交，因此需要一个表单，添加```@method('DELETE')```的方式模拟delete请求。

以上就是Laravel5.6 博客 中文章标题的增删改查操作！


本教程代码 [http://blog.static.aiaiaini.com/laravel5.6-blog-tag-curd.zip](http://blog.static.aiaiaini.com/laravel5.6-blog-tag-curd.zip)

更多内容，可以关注微信公众号: 写PHP的老王 

