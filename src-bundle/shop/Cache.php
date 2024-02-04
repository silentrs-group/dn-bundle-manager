<?php

namespace shop;

use php\io\FileStream;
use php\lib\fs;
use php\time\Time;

class Cache
{
    public static $path;

    public static function make ($file, $data, $isPermanent = true, $time = 0)
    {
        $file = new FileStream($file, "w");

        if (!$isPermanent) {
            $file->write(Time::now()->getTime() + $time);
        }

        $file->write($data);
        $file->close();
    }

    /**
     * @return mixed|false
     */
    public static function get ($file, $isPermanent = true)
    {
        if (!fs::exists($file)) return false;

        $file = new FileStream($file, "r");

        if (!$isPermanent) {
            if ($file->length() < 13) return 0;

            $time = base_convert($file->read(13), 10, 10) - Time::now()->getTime();

            if ($time  <= 0) {
                return false;
            }

            $data = $file->readFully();
            $file->close();
            return $data;
        }

        $data = $file->readFully();
        $file->close();
        return $data;
    }
}