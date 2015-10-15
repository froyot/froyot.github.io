## Yii2 主题添加记录
Yii2本身支持多模块，多主题开发。因此对开发中遇到的多主题问题，以及自定
义不同模块主题需要的注意事项进行记录

*   主题添加
默认全局主题在配置文件中添加comment配置
```php
 'view' => [
            'theme' => [
                'basePath' => '@app/themes/{themesName}',
                'baseUrl' => '@web',
                'pathMap' => [
                    '@app/views' => '@app/themes/{themesName}',
                ],
            ],
  ]
```
*   多模块定义主题

如果有多个不同的模块，想要在不同的模块中设置不同的模板，在模块的入口文
件中添加

```
        \Yii::$app->view->theme = new \yii\base\Theme([
            'pathMap' => ['@app/views' => '@app/admin/views'],
            'baseUrl' => '@web',
        ]);
```
*  文件机制
YII2中主题layout文件机制，如果定义了重新定义了@app/views，则在该目录下搜
索layout文件夹中的布局文件，如果没有，则在默认文件中寻找。其他页面的view
文件也是如此。

*  注意
如果你跟我一样，将themes文件放置在app根目录内，而网站更目录是@app/web，那
么还需要对主题的静态文件做稍微修改。
YII2中，web可以访问的目录是限制在@web目录下。所以以上情况需要将主题的静态
文件使用Assets发布。

比如主题的路径@app/themes/tfviolet,在该目录下(该目录下其他目录内也可以)建
立一个ThemeAsset文件

```php
namespace app\themes\tfviolet;

use yii\web\AssetBundle;

class ThemeAsset extends AssetBundle
{
    public $sourcePath = '@app/themes/tfviolet/static';
    public $css = [

        'css/materialize.min.css',
        'css/style.css'
    ];

    public $js = [
        'js/materialize.min.js'
    ];
}
```

注意，一定要定义sourcePath，只有这样才能将sourcePath的所有文件发布到asset目
录下。发布的目录是@basthPath/asset
定义sourcePath之后，该Assets的baseUrl, basePath则是无效的，被AssetManage覆盖。
