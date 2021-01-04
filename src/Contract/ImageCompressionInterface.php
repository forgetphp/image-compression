<?php declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: chenhong
 * Date: 2020/12/10
 * Time: 2:01 PM
 * @author chenhong <747825455@qq.com>
 */

namespace Image\Contract;

use Image\Image;

interface ImageCompressionInterface
{
    /**
     * input file
     *
     * @param string $filePath
     * @param string $output
     *
     * @return
     */
    public function input(string $filePath, string $output);

    /**
     * output file path array.
     **
     * @return array
     */
    public function output() : array ;

    /**
     * Swoole Hook
     * Swooke version ~4.0
     * @return mixed
     */
    public function enableAsync();
}
