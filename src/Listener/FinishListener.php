<?php declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: chenhong
 * Date: 2020/12/11
 * Time: 1:42 PM
 * @author chenhong <747825455@qq.com>
 */

namespace Image\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Image\Event\Finish;

class FinishListener implements ListenerInterface
{

    /**
     * @return string[] returns the events that you want to listen
     */
    public function listen(): array
    {
        return [
            Finish::class
        ];
    }

    /**
     * Handle the Event when the event is triggered, all listeners will
     * complete before the event is returned to the EventDispatcher.
     *
     * @param Finish|object $event
     */
    public function process(object $event)
    {
        dump($event->compressionRatio);
        dump($event->distImageSize);
        dump($event->distPath);
        dump($event->originImageSize);
    }
}
