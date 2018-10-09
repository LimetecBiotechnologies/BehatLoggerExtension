<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 09.10.18
 * Time: 13:27
 */

namespace seretos\BehatLoggerExtension\Service;


class ScreenshotPrinter
{
    public function takeScreenshot($path, $prefix, $data){
        $filename = sprintf('%s_%s_%s.%s', $prefix, date('c'), uniqid('', true), 'png');
        file_put_contents($path . $filename, $data);
        return $filename;
    }
}