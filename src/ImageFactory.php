<?php declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: chenhong
 * Date: 2020/12/10
 * Time: 2:09 PM
 * @author chenhong <747825455@qq.com>
 */

namespace Image;

use Image\CJpeg\CJpeg;
use Image\Compression\Type;
use Image\Exception\RuntimeException;
use Image\Webp\Webp;

class ImageFactory
{
    protected static $instance = [];

    public static function create(array $config = [])
    {
        $type = $config['type'] ?? Type::WEBP;
        $binPath       = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR;

        switch ($type) {
            case Type::WEBP:
                $config['bin'] = $config['bin'] ?? $binPath . 'cwebp';
                $instance      = new Webp($config);
                break;
            case Type::CJPEG:
                $config['bin'] = $config['bin'] ?? $binPath . 'cjpeg';
                $instance = new CJpeg($config);
                break;
            default:
                throw new RuntimeException(sprintf('[%s] is not support. support type is [cwebp,cjpeg]', $type));
                break;
        }

        if (isset(ImageFactory::$instance[$type]) && (ImageFactory::$instance[$type] instanceof Image)) {
            return ImageFactory::$instance[$type];
        } else {
            ImageFactory::$instance[$type] = $instance;
            return ImageFactory::$instance[$type];
        }
    }
}
