<?php declare(strict_types = 1);
namespace Image\Event;

/**
 * Created by PhpStorm.
 * User: chenhong
 * Date: 2020/12/11
 * Time: 1:35 PM
 * @author chenhong <747825455@qq.com>
 */
class ReportEncodingProgress
{
    const START  = 0;
    const FINISH = 100;
    /**
     * encoding progress
     * @var int
     */
    public $progress;

    /**
     * @var string
     */
    public $currentFile;

    /**
     * ReportEncodingProgress constructor.
     *
     * @param int    $progress
     * @param string $currentFile
     */
    public function __construct(int $progress, string $currentFile)
    {
        $this->progress    = $progress;
        $this->currentFile = $currentFile;
    }

    /**
     * @param int $progress
     *
     * @return ReportEncodingProgress
     */
    public function setProgress(int $progress)
    {
        $this->progress = $progress > 100 ? 100 : $progress;

        return $this;
    }
}
