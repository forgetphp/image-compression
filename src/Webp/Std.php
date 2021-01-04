<?php declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: chenhong
 * Date: 2020/12/10
 * Time: 4:14 PM
 * @author chenhong <747825455@qq.com>
 */

namespace Image\Webp;

class Std
{
    const STDIN  = 0;
    const STDOUT = 1;
    const STDERR = 2;

    public static function getDescriptorSpec()
    {
        return [
            Std::STDIN  => [ "pipe" , "r" ] ,  // 标准输入，子进程从此管道中读取数据
            Std::STDOUT => [ "pipe" , "w" ] ,  // 标准输出，子进程向此管道中写入数据
            Std::STDERR => [ "pipe" , "w" ] // 标准错误，写入到一个文件
        ];
    }
}
