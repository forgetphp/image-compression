<?php
/**
 * Created by PhpStorm.
 * User: chenhong
 * Date: 2020/12/22
 * Time: 2:50 PM
 * @author chenhong <747825455@qq.com>
 */


use Intervention\Image\ImageManager;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\VarDumper;

require_once realpath( dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' );

VarDumper::setHandler( function ( $var ) {
    $cloner = new VarCloner();
    //$cloner->setMaxItems(2);  // 设置一个嵌套级别（past the first nesting level)被克隆的元素的最大值
//    $cloner->setMinDepth(1);  // 在深度上的剥离限制。
//    $cloner->setMaxItems(5);
    $dumper = 'cli' === PHP_SAPI ? new CliDumper() : new HtmlDumper();
    $data   = $cloner->cloneVar( $var );
    $dumper->dump( $data );

} );

// create an image manager instance with favored driver
$manager = new ImageManager( [ 'driver' => 'imagick' ] );

// to finally create image instances
$image = $manager->make( './origin.jpeg' )->encode( 'webp' , 75 )->save( './imagck_webp.webp' );

