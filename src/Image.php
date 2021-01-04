<?php declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: chenhong
 * Date: 2020/12/10
 * Time: 2:05 PM
 * @author chenhong <747825455@qq.com>
 */

namespace Image;

use GuzzleHttp\Client;
use Image\Contract\ImageCompressionInterface;
use Image\Exception\RuntimeException;

abstract class Image implements ImageCompressionInterface
{
    /**
     * @var string
     */
    protected $bin = '';

    /**
     * @var string
     */
    protected $outputDir = '';

    /**
     * @var array
     */
    protected $listener;

    /**
     * @var bool
     */
    protected $isEnableAsync = false;

    /**
     * input images
     * @var array
     */
    protected $inputImages = [ ];

    /**
     * quality factor (0:small..100:big), default=75
     * @var float
     */
    protected $quality = 75;

    /**
     * @var string
     */
    protected $resizeCommand;

    /**
     * @var string
     */
    protected $cropCommand;

    /**
     * @var string
     */
    protected $distFilePath;

    /**
     * @var string
     */
    protected $currentFile;

    /**
     * @var string
     */
    protected $originPath;

    /**
     * Image constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $binPath   = $config['bin'] ?? '';
        $outputDir = $config['output_dir'] ?? '';
        $listener  = $config['listener'] ?? [ ];

        $this->init($binPath, $outputDir, $listener);
    }

    /**
     *
     * @param string $binPath
     * @param string $outputDir
     * @param array  $listener
     */
    protected function init(string $binPath, string $outputDir, array $listener)
    {
        $this->bin       = $binPath;
        $this->outputDir = $outputDir ?: '.';
        if (!is_dir($this->outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        $this->listener = $listener;
    }

    /**
     * @param string $binPath
     *
     * @return Image
     */
    public function setBinPath(string $binPath) : Image
    {
        $this->bin = $binPath;

        return $this;
    }

    /**
     * @param string $outputDir
     *
     * @return Image
     */
    public function setOutputDir(string $outputDir) : Image
    {
        $this->outputDir = $outputDir;

        return $this;
    }

    /**
     * @return float
     */
    protected function getQuality(): float
    {
        return $this->quality;
    }

    /**
     * @param float $quality
     *
     * @return Image
     */
    public function setQuality(float $quality)
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * @return string
     */
    protected function getDistFilePath(): string
    {
        return $this->distFilePath;
    }

    /**
     * @param string $distFilePath
     */
    protected function setDistFilePath(string $distFilePath)
    {
        $this->distFilePath = $distFilePath;
    }


    /**
     * input file
     *
     * @param string $filePath
     *
     * @param string $output
     *
     * @return Image
     */
    public function input(string $filePath, string $output = '')
    {
        $this->inputImages[] = $this->validation($filePath, $output);

        return $this;
    }

    /**
     * input images array
     *
     * @param array $images
     *
     * @return Image
     */
    public function inputs(array $images)
    {
        foreach ($images as $image) {
            $filePath = '';
            $output = '';

            if (is_array($image) && count($image) === 2) {
                [ $filePath , $output ] = $image;
            } elseif (is_string($image)) {
                $filePath = $image;
            } elseif (is_array($image) && count($image) === 1) {
                [ $filePath ] = $image;
            }

            $this->inputImages[]    = $this->validation($filePath, $output);
        }

        return $this;
    }

    /**
     *
     * @param string $filePath
     *
     * @param string $output
     *
     * @return string
     * @throws RuntimeException
     */
    protected function validation(string $filePath, string $output)
    {
        if (is_file($filePath) && file_exists($filePath)) {
            return [
                'is_network_image' => false ,
                'path'             => $filePath ,
                'origin_path'      => $filePath ,
                'output'           => $output ?: pathinfo($filePath, PATHINFO_FILENAME) ,
            ];
        } elseif (filter_var($filePath, FILTER_VALIDATE_URL) !== false) {
            return $this->downloadImage($filePath, $output);
        } else {
            throw new RuntimeException(sprintf('%s file not exists.', $filePath));
        }
    }

    /**
     * Converts bytes into human readable file size.
     *
     * @param string $bytes
     *
     * @return string human readable file size (2,87 Мб)
     * @author Mogilev Arseny
     */
    protected function fileSizeConvert($bytes)
    {
        $bytes   = floatval($bytes);
        $arBytes = [
            [
                "UNIT"  => "TB" ,
                "VALUE" => pow(1024, 4) ,
            ] ,
            [
                "UNIT"  => "GB" ,
                "VALUE" => pow(1024, 3) ,
            ] ,
            [
                "UNIT"  => "MB" ,
                "VALUE" => pow(1024, 2) ,
            ] ,
            [
                "UNIT"  => "KB" ,
                "VALUE" => 1024 ,
            ] ,
            [
                "UNIT"  => "B" ,
                "VALUE" => 1 ,
            ] ,
        ];

        $result = '0B';
        foreach ($arBytes as $arItem) {
            if ($bytes >= $arItem["VALUE"]) {
                $result = $bytes / $arItem["VALUE"];
                $result = strval(round($result, 2)) . " " . $arItem["UNIT"];
                break;
            }
        }

        return $result;
    }

    public function enableAsync()
    {
        $this->isEnableAsync = true;

        return $this;
    }

    private function downloadImage($filePath, $output)
    {
        $client          = new Client();
        $response        = $client->get($filePath);
        $mimes           = [
            'image/gif' ,
            'image/jpeg' ,
            'image/png' ,
            'image/jpg' ,
        ];
        [ $contentType ] = $response->getHeader('Content-Type');
        if (in_array($contentType, $mimes)) {
            $contents             = $response->getBody()->getContents();
            $downloadDir = $this->outputDir . DIRECTORY_SEPARATOR . 'download';
            if (!is_dir($downloadDir)) {
                mkdir($downloadDir, 755, true);
            }
            $tempFilename         = $downloadDir . strrchr($filePath, '/');
            file_put_contents($tempFilename, $contents);

            return [
                'is_network_image' => true ,
                'path'             => $tempFilename ,
                'origin_path'      => $filePath ,
                'output'           => pathinfo($tempFilename, PATHINFO_FILENAME) ,
            ];
        } else {
            throw new RuntimeException(sprintf('%s not image.', $filePath));
        }
    }

    protected function removeImage($inputFile)
    {
        return unlink($inputFile);
    }
}
