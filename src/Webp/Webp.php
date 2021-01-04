<?php declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: chenhong
 * Date: 2020/12/10
 * Time: 2:32 PM
 * @author chenhong <747825455@qq.com>
 */

namespace Image\Webp;

use Hyperf\Event\EventDispatcher;
use Hyperf\Event\ListenerProvider;
use Image\Contract\FinishInterface;
use Image\Contract\ReportEncodingProgressInterface;
use Image\Event\Finish;
use Image\Event\ReportEncodingProgress;
use Image\Image;

class Webp extends Image
{
    /**
     * @var string
     */
    protected $extension = '.webp';

    /**
     * output file path.
     *
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


    protected function generateCommand($inputImage, $filename)
    {
        $command = '%s -progress ';

        if ($this->resizeCommand) {
            $command .= $this->resizeCommand;
        }

        if ($this->cropCommand) {
            $command .= $this->cropCommand;
        }

        //
        $command .= '-short -mt -q %d %s -o %s';

        $this->setDistFilePath($this->outputDir . DIRECTORY_SEPARATOR . $filename . $this->extension);

        return sprintf(
            $command,
            $this->bin,
            $this->getQuality(),
            $inputImage,
            $this->getDistFilePath()
        );
    }

    /**
     * resize picture (after any cropping)
     *
     * @param int $width
     * @param int $height
     *
     * @return Webp
     */
    public function resize(int $width, int $height) : Webp
    {
        $this->resizeCommand = sprintf('-resize %s %s ', $width, $height);

        return $this;
    }

    /**
     * crop picture with the given rectangle
     *
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     *
     * @return Webp
     */
    public function crop(int $x, int $y, int $width, int $height) : Webp
    {
        $this->cropCommand = sprintf('-crop %d %d %d %d ', $x, $y, $width, $height);

        return $this;
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
            $isFinished = false;

            while (!feof($handle)) {
                $buffer = fread($handle, 64);


                if ($buffer === false) {
                    $eventDispatcher->dispatch($event->setProgress(ReportEncodingProgress::FINISH));
                } else {
                    preg_match('/\d+ \%/', $buffer, $matches);

                    if (!empty($matches)) {
                        [ $step ]              = $matches;
                        [ $number , $percent ] = explode(' ', $step);
                        $eventDispatcher->dispatch($event->setProgress((int)$number));
                    } else {
                        if ($buffer && (strpos($buffer, '[') === false)) {
                            $arr = explode(' ', ltrim($buffer, ' '));

                            if (count($arr) >= 2) {
                                [ $bytes , $string ] = $arr;
                                $bytes            = (int)$bytes;
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


                            $isFinished = true;
                        }

                        //处理有时管道无数据的情况
                        if (!$buffer && !$isFinished) {
                            $this->dispatcher($eventDispatcher);
                        }

                        $eventDispatcher->dispatch($event->setProgress(ReportEncodingProgress::FINISH));
                    }
                }
            }
            fclose($handle);

            //保证最后是一定通知到
            if ($event->progress !== 100) {
                $this->dispatcher($eventDispatcher);
                $eventDispatcher->dispatch($event->setProgress(ReportEncodingProgress::FINISH));
            }

            // 切记：在调用 proc_close 之前关闭所有的管道以避免死锁。
            proc_close($process);
        }
    }



    /**
     *
     * @param EventDispatcher        $eventDispatcher
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
}
