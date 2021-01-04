### PHP JPEG Encoder Project
该项目是基于 **[libwebp](https://github.com/webmproject/libwebp)** , **[mozjpeg](https://github.com/mozilla/mozjpeg)** 实现的。PHP的 `gd` 库,或者是 `imagick` 压缩比、性能都不如前者。
如果需要转化成异步模式则需要 [Swoole](https://www.swoole.com/) 提供的 `Hook` 功能将命令转为 CLI 模式。

#### 运行环境

*   Linux,OS X
*   PHP 7.2+
*   Swoole 4.4+

#### 安装
```composer
compser require forgetphp/image-compression
```

#### 使用
```php
<?php
use Image\Compression\Type;
use Image\Contract\FinishInterface;
use Image\Contract\ReportEncodingProgressInterface;
use Image\Event\Finish;
use Image\Event\ReportEncodingProgress;
use Image\ImageFactory;
use Image\Listener\FinishListener;
use Image\Listener\ReportEncodingProgressListener;

//监听压缩进度事件(如果需要)
class ReportListener extends ReportEncodingProgressListener
{

    /**
     * @param ReportEncodingProgress| object $event
     */
    public function process( object $event )
    {
        //压缩进度
        //$event->progress; 
        //当前文件名
        //$event->currentFile;
       
    }
}

//监听压缩完成事件(如果需要)
class CompressionFinishListener extends FinishListener
{
    /**
     * @param Finish| object $event
     */
    public function process( object $event )
    {
        $event->currentFile;//当前文件名
        $event->compressionRatio;//压缩比
        $event->distImageSize;//压缩后文件大小
        $event->distPath;//压缩后文件路径
        $event->originImageSize;//原图大小
    }
}

$config = [
    'type'       => Type::CJPEG , //压缩类型
    'output_dir' => './runtime', //文件输出路径
    //'bin' => '',//对应bin的路径。默认不用填写。如果在本地无法正常运行。请编译对应平台的可执行文件后配置该项

    'listener'   => [
        //ReportEncodingProgressInterface::class => ReportListener::class , //压缩进度事件
        //FinishInterface::class                 => CompressionFinishListener::class ,//压缩完成事件
    ] ,
];

$image = ImageFactory::create($config);
```

##### 方法
`setQuality(int)` 设置压缩质量。`0-95` 默认75

```php
$image->setQuality(75);
```

`enableAsync()` 是否开启异步。依赖 [Swoole](https://www.swoole.com/) 提供的 `Hook` 功能

```php
$image->enableAsync();
```

`input(string $image,string $outfile = '')` 输入一张图片。第二个参数为输出图片名称,不需要带后缀,不传则使用原名。也可以输入网络图片地址。

```php
$image->input('demo.jpg');
```

`inputs(array $images)` 输入图片数组。参数说明参考 `input()`

```php
$image->inputs([
   'https://wenda.swoole.com/storage/avatar/avatar-1.png',
   'WechatIMG409.jpeg'
]);

//or

$image->inputs([
   [ 'WechatIMG409.jpeg' , 'outfilename'],
   ['https://wenda.swoole.com/storage/avatar/avatar-1.png','输出文件名。不需要带后缀。可不传同 input()' ],
]);
```

`crop( int $x , int $y , int $width , int $height )` 图片裁切。 `x` 轴位置 ,`y` 轴位置 , `width` 图片宽度 ,`height` 图片高度 
```php
$image->->crop(100,100,100,100);
```

`resize( int $width , int $height )` 图片缩放( mozjpeg 不支持), `width` 图片宽度 ,`height` 图片高度 
```php
$image->->resize(10,10);
```

`output()` 输出图片
```php
$result = $image->->output();
```

#### 例子
```php
 $result = ImageFactory::create($config)
    ->input( 'demo.jpg' )
    ->output( );
```

### 效果对比

此对比只是针对同一图片而言。不同的图片压缩比会有差异。只能是做一个相对的对比。

| 原图 | Webp  | mozjpeg | imagick(jpg) | imagick(webp)
|---|---|---|---|---|
| ![image](https://github.com/forgetphp/image-compression/blob/main/example/origin.jpeg) | ![image](https://github.com/forgetphp/image-compression/blob/main/example/webp.webp) |![image](https://github.com/forgetphp/image-compression/blob/main/example/cjpeg.jpeg)| ![image](https://github.com/forgetphp/image-compression/blob/main/example/imagck.jpeg) | ![image](https://github.com/forgetphp/image-compression/blob/main/example/imagck_webp.webp) | 
| 压缩比 | 85% | 83% | 52% | 72% |

#### 兼容
[webp兼容一览表](https://caniuse.com/?search=webp) 

#### License
MIT