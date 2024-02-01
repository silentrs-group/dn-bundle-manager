<?php

namespace shop\dto;

class Bundle
{
    public $name;
    public $author;
    public $image;
    public $version;
    public $url;
    public $description;

    public static function of($data)
    {
        return new self($data);
    }

    private function __construct($data)
    {
        $this->name = $data["name"];
        $this->author = $data["author"];
        $this->image = $data["image"];
        $this->version = $data["version"];
        $this->url = $data["url"];
        $this->description = $data["description"];
    }
}