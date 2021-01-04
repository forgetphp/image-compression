<?php
use Image\Exception\RuntimeException;
use Image\Image;
use Image\ImageFactory;
use Image\Webp\Webp;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: chenhong
 * Date: 2020/12/25
 * Time: 10:38 AM
 * @author chenhong <747825455@qq.com>
 */
final class ImageFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function createDefault(  )
    {
        $instance = ImageFactory::create();
        $this->assertInstanceOf( Webp::class,$instance);
    }

    /**
     * @test
     */
    public function createUnknow()
    {
        $this->expectException(RuntimeException::class);
        $config = [
            'type' => 'test'
        ];
        ImageFactory::create($config);
    }

    /**
     * @test
     */
    public function create(  ) : Image
    {
        $instance = ImageFactory::create();
        $this->assertInstanceOf(Image::class,$instance);
        return $instance;
    }

    /**
     * @test
     * @depends create
     */
    public function setTypeErrorQuality(Image $image )
    {
        $this->expectException(TypeError::class);

        $image->setQuality('string give');
    }

    /**
     * @test
     * @depends create
     *
     * @param Image $image
     *
     * @return Image
     */
    public function setQuality(Image $image ) : Image
    {
        $instance = $image->setQuality(75);

        $this->assertInstanceOf(Image::class,$instance);

        return $instance;
    }

    /**
     * @test
     * @depends setQuality
     *
     * @param Image $image
     */
    public function inputError(Image $image )
    {
        $this->expectException(RuntimeException::class);
        $instance = $image->input('test');
    }

    /**
     * @test
     * @depends setQuality
     *
     * @param Image $image
     *
     * @return Image
     */
    public function input(Image $image ) : Image
    {
        $instance = $image->input(dirname(__DIR__) . '/example/origin.jpeg','tests_origin');
        $this->assertInstanceOf(Image::class,$instance);
        return $instance;
    }

    /**
     * @test
     * @depends input
     *
     * @param Image $image
     *
     * @return Image
     */
    public function output( Image $image )
    {
        $array = $image->output();
        $this->assertIsArray($array);
    }
}