<?php

namespace shop\internal;

use php\io\FileStream;
use php\io\IOException;
use php\lib\fs;
use php\lib\str;
use php\time\Time;

class Cache
{
    public static $path;

    /**
     * @param string $file
     * @param string $data
     * @param bool $isPermanent
     * @param int $time
     * @return void
     * @throws IOException
     */
    public static function make(string $file, string $data, bool $isPermanent = true, int $time = 0)
    {
        $file = new FileStream($file, "w");

        if (!$isPermanent) {
            $file->write(Time::now()->getTime() + $time);
        }

        $file->write($data);
        $file->close();
    }

    /**
     * @param string $file
     * @param bool $isPermanent
     * @return mixed|false|FileStream
     * @throws IOException
     */
    public static function get(string $file, bool $isPermanent = true)
    {
        if (!fs::exists($file)) return false;

        $file = new FileStream($file, "r");

        if (!$isPermanent) {
            if ($file->length() < 13) return 0;

            $time = base_convert($file->read(13), 10, 10) - Time::now()->getTime();

            if ($time <= 0) {
                return false;
            }

            $data = $file->readFully();
            $file->close();
            return $data;
        }

        return $file;
    }
}