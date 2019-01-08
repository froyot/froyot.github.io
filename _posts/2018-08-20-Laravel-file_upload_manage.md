---
layout: post
title: Laravel5.6 文件上传以及文件管理后台
category: PHP
comments: true
description: Laravel5.6 文件上传以及文件管理后台
keywords: Laravel,PHP,文件上传,博客,CURD
---


今天聊聊在Laravel5.6 如何实现文件上传功能，以及上传文件的管理功能。主要有文件列表，上传新文件，创建文件夹，删除文件夹以及删除文件。

首先添加一个控制器,在命令行中输入```php  php artisan make:controller Admin/FileController```，创建一个空的FileController控制器，控制器中有下列四个方法:

*	index 显示文件和目录列表
*	upload 上传新文件
*	createFolder 创建新文件夹
*	delete 删除文件或目录

我们在app/Services目录下创建一个UploadsManager服务类，用了处理上传文件以及文件目录等相关操作。实现方法

*	folderInfo 获取指定目录下所有文件和目录列表，以及当前目录路径
*	createDir 新建目录
*	store 保存文件
*	deleteByPath 删除文件或目录

所有文件以及目录的逻辑操作放置在UploadsManager服务类内部，在控制器中对输入参数进行验证后直接调用服务类对应方法。

Laravel文件上传Storage使用的是local disk，上传文件保存在/storage/app下，并以此为根目录。如果要实现上传文件对外访问，则需要在public目录下创建一个软链接至/storage/app。我本地使用的是uploads作为上传文件跟目录url。因此需要在public目录下创建一个名为uploads的软链接。在windows中，可以使用```mklink /J source_src_dir dist_src_dir```


##### 文件目录列表实现：

*	控制器
```php
	public function index(Request $request)
	{
        $folder = $request->get('folder','/');
        $data = $this->upManager->folderInfo($folder);
        return view('admin.file.index', $data);
	}

```
*	视图文件
在resources/admin/file下新建index.blade.php，模板中使用table渲染文件目录列表

{% raw %}

```html
    <table id="posts-table" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>name</th>
                    <th>type</th>
                    <th data-sortable="false">Actions</th>
                </tr>
             </thead>
            <tbody>
            @foreach ($items as $item)
                <tr>
                    @if ($item['type'] == 'dir')
                        <td><a href="{{route('file.index',[ 'folder'=>$item['dist'] ])}}"><span class="dirname">{{ $item['name'] }}</span></a></td>
                    @else
                        <td><span class="filename">{{ $item['name'] }}</span></td>
                    @endif
                    <td>{{ $item['type'] }}</td>
                    <td>
                        <a href="{{route('file.delete',['path'=>$item['dist']])}}" class="btn btn-xs btn-info">
                            <i class="fa fa-edit"></i> Delete
                        </a>
                    </td>
                </tr>
    @endforeach
    </tbody>
    </table>
```
{% endraw %}

*	添加路由
在routes/web.php中的```php Route::namespace('Admin')->middleware(['auth'])->group```添加以下内容

```
    Route::get('admin/file/index', 'FileController@index')->name('file.index');
    Route::post('admin/file/upload', 'FileController@upload')->name('file.upload');
    Route::post('admin/file/createFolder', 'FileController@createFolder')->name('file.createFolder');
    Route::get('admin/file/store', 'FileController@store')->name('file.store');
    Route::get('admin/file/delete','FileController@delete')->name('file.delete');
```

访问```http://127.0.0.1:8000/admin/file/index```,可以看到文件列表已经显示正常。

![image](http://blog.static.aiaiaini.com/blog/file_index_ab7b32e9bc47e13fd41e7656d1dc4a9f.png)


##### 新建文件夹

*	控制器
参数验证，只做简单的required验证，因此使用控制器验证，不再另见Request类验证。创建目录输入两个参数，一个是当前目录，一个是新建目录名。在当期目录下新建一个指定name的新目录。
```
	public function createFolder(Request $request){
		$this->validate($request, [
	        'folder' => 'required',
	        'name'=>'required'
	    ]);
	    $res = $this->upManager->createDir($request->get('folder'),$request->get('name'));
	    if($res)
	    {
	    	return back()->with('success','删除成功');
	    }
	    return back()->with('error','删除失败');
	}
```

*	视图文件
视图文件采用moda方式弹窗添加，在index.blade.php中添加一个moda。内容见文后代码。
moda内容中只有一个表单，点击确定提交到createFolder，在列表中点击New Folder可以看到以下内容

![image](http://blog.static.aiaiaini.com/blog/file_newdirector_ab7b32e9bc47e13fd41e7656d1dc4a9f.png)


##### 上传文件

*	控制器
创建目录输入三个参数，一个是上传文件，一个是保存目录名（不含后缀)，一个文件名（可选）
```
	//上传文件
	public function upload(FileUploadRequest $request){
		$name = $request->get('name');
		$folder = $request->get('folder');
		$file = $request->file('file');
		$path = $this->upManager->store($file,$folder,$name);
		if($path)
		{
			return back()->with('success','文件上传成功');
		}
		return back()->with('error','文件上传失败');
	}
```

*	视图文件
文件上传采用moda方式弹窗添加，在index.blade.php中添加一个moda。内容见文后代码。
moda内容中只有一个表单，点击确定提交到upload，在列表中点击upload可以看到以下内容

![image](http://blog.static.aiaiaini.com/blog/file_upload_ab7b32e9bc47e13fd41e7656d1dc4a9f.png)


##### 文件的删除
文件删除，通过链接中的参数path实现，判断传入路径是目录还是文件，根据不同执行不同的删除方式。


##### 可能遇到的错误
*	Illuminate\Http\Exceptions\PostTooLargeException 上传文件太大


代码下载地址[http://blog.static.aiaiaini.com/blog.uploadfile.zip](http://blog.static.aiaiaini.com/blog.uploadfile.zip)
