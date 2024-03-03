<?php

namespace shop\dto;

use ide\Logger;
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
    public $exampleUrl;

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
        $this->url = GithubProvider::BASE_HOST . $data["url"];
        $this->description = $data["description"];
        $this->exampleUrl = $data["exampleUrl"];
    }
}