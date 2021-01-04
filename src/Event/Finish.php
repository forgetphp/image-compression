<?php declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: chenhong
 * Date: 2020/12/11
 * Time: 1:38 PM
 * @author chenhong <747825455@qq.com>
 */

namespace Image\Event;

class Finish
{
    /**
     * @var float
     */
    public $compressionRatio;

    /**
     *
     * @var string
     */
    public $originImageSize;

    /**
     * @var string
     */
    public $distImageSize;

    /**
     * @var string
     */
    public $distPath;

    /**
     * @var string
     */
    public $currentFile;

    /**
     * Finish constructor.
     *
     * @param float  $compressionRatio
     * @param string $originImageSize
     * @param string $distImageSize
     * @param string $distPath
     * @param string $currentFile
     */
    public function __construct($compressionRatio, $originImageSize, $distImageSize, $distPath, $currentFile)
    {
        $this->compressionRatio = $compressionRatio;
        $this->originImageSize  = $originImageSize;
        $this->distImageSize    = $distImageSize;
        $this->distPath         = $distPath;
        $this->currentFile      = $currentFile;
    }
}
