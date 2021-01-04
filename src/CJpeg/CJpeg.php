<?php declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: chenhong
 * Date: 2020/12/21
 * Time: 5:02 PM
 * @author chenhong <747825455@qq.com>
 */

namespace Image\CJpeg;

use Hyperf\Event\EventDispatcher;
use Hyperf\Event\ListenerProvider;
use Image\Contract\FinishInterface;
use Image\Contract\ReportEncodingProgressInterface;
use Image\Event\Finish;
use Image\Event\ReportEncodingProgress;
use Image\Exception\RuntimeException;
use Image\Image;
use Image\Webp\Std;

class CJpeg extends Image
{

    /**
     * output file path array.
     **
     * @return array
     */
    public function output() : array
    {
        $result = [ ];

        foreach ($this->inputImages as $inputImage) {
            $this->currentFile = $inputImage['path'];
            $this->originPath  = $inputImage['origin_path'];
            $filename          = $inputImage['output'];

            $command = $this->generateCommand($this->currentFile, $filename);

            if ($this->isEnableAsync) {
                $this->executeAsync($command);
            } else {
                $this->execute($command);
            }

            if ($inputImage['is_network_image']) {
                $this->removeImage($this->currentFile);
            }

            $result [] = $this->getDistFilePath();
        }


        return $result;
    }

    protected function generateCommand($inputFile, $filename)
    {
        $extension = pathinfo($this->originPath, PATHINFO_EXTENSION);

        $this->setDistFilePath($this->outputDir . DIRECTORY_SEPARATOR . $filename . sprintf('.%s', $extension));

        $command = '%s -quality %d -optimize -progressive -baseline -smooth 100 -outfile %s %s';

        if ($this->resizeCommand) {
            $command .= $this->resizeCommand;
        }

        if ($this->cropCommand) {
            $command = sprintf($this->cropCommand . '-outfile %s -copy none -optimize -progressive %s', $this->getDistFilePath(), $inputFile);
            return dirname($this->bin) . DIRECTORY_SEPARATOR .'jpegtran' . $command;
        }

        return sprintf(
            $command,
            $this->bin,
            $this->getQuality(),
            $this->getDistFilePath(),
            $inputFile
        );
    }

    /**
     *
     * @param string $command
     */
    protected function executeAsync(string $command)
    {
        \Swoole\Runtime::setHookFlags(SWOOLE_HOOK_ALL);
        \Swoole\Coroutine\run(function () use ($command) {
            $this->execute($command);
        });
    }

    protected function execute($command)
    {
        $provider = new ListenerProvider();

        $instance = $this->listener[ ReportEncodingProgressInterface::class ] ?? [ ];
        if ($instance) {
            $provider->on(ReportEncodingProgress::class, [ new $instance() , 'process' ]);
        }

        $instance = $this->listener[ FinishInterface::class ] ?? [ ];

        if ($instance) {
            $provider->on(Finish::class, [ new $instance() , 'process' ]);
        }

        $eventDispatcher = new EventDispatcher($provider);

        $process = proc_open($command, Std::getDescriptorSpec(), $pipes);

        if (is_resource($process)) {
            $handle     = $pipes[ Std::STDERR ];
            $event      = new ReportEncodingProgress(ReportEncodingProgress::START, $this->originPath);
//            while ( !feof( $handle ) ) {
//                $buffer = fread( $handle , 1024 );
//            }
            fclose($handle);
            // 切记：在调用 proc_close 之前关闭所有的管道以避免死锁。
            proc_close($process);
            $eventDispatcher->dispatch($event->setProgress(ReportEncodingProgress::FINISH));

            $this->dispatcher($eventDispatcher);
        }
    }

    /**
     *
     * @param EventDispatcher        $eventDispatcher
     *
     * @internal param $
     */
    protected function dispatcher(EventDispatcher $eventDispatcher)
    {
        $bytes            = filesize($this->getDistFilePath());
        $compressionRatio = (1 - (round($bytes / filesize($this->currentFile), 2))) * 100;

        $eventDispatcher->dispatch(
            new Finish(
                $compressionRatio,
                $this->fileSizeConvert(filesize($this->currentFile)),
                $this->fileSizeConvert($bytes),
                $this->getDistFilePath(),
                $this->originPath
            )
        );
    }

    /**
     * crop picture with the given rectangle
     *
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     *
     * @return CJpeg
     */
    public function crop(int $x, int $y, int $width, int $height) : CJpeg
    {
        $this->cropCommand = sprintf(' -crop %dx%d+%d+%d  ', $width, $height, $x, $y);

        return $this;
    }

    /**
     * resize picture (after any cropping)
     *
     * @param int $width
     * @param int $height
     *
     * @return CJpeg
     * @throws RuntimeException
     */
    public function resize(int $width, int $height) : CJpeg
    {
        throw new RuntimeException('MozJPEG does not support image resizing.');
    }
}
