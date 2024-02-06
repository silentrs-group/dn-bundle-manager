<?php

namespace shop\dto;

use php\io\Stream;
use php\lib\str;
use shop\bundle\GithubProvider;

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

        // для обратной своместимости со старым форматом
        if (str::startsWith($data["image"], "http") || $data["image"] instanceof Stream) {
            $this->image = $data["image"];
        } else {
            $this->image = GithubProvider::BASE_HOST . 'images/' . $data["image"];
        }
        $this->version = $data["version"];

        if (str::startsWith($data["url"], "http")) {
            $this->url = $data["url"];
        } else {
            $this->url = GithubProvider::BASE_HOST . 'bundles/' . $data["url"];
        }

        $this->description = $data["description"];
    }
}