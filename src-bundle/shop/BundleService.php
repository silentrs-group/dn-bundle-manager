<?php

namespace shop;

use ide\Ide;
use php\io\MemoryStream;
use php\lib\fs;
use shop\dto\Bundle;
use shop\bundle\CacheProvider;
use shop\bundle\GithubProvider;
use shop\bundle\LocalProvider;

class BundleService
{

    /**
     * @var GithubProvider
     */
    private $github;

    /**
     * @var LocalProvider
     */
    private $local;

    /**
     * @var CacheProvider
     */
    private $cache;


    public function __construct()
    {
        $this->github = new GithubProvider();
        $this->local = new LocalProvider();
        $this->cache = new CacheProvider();
        $this->cache->setLocal($this->local);
        $this->cache->setGithub($this->github);
        $this->cache->update();
    }

    /**
     * @return Bundle[]
     */
    public function getList()
    {
        return $this->cache->getList();
    }

    public function getInstalledList()
    {
        return $this->local->getList();
    }

    public function install($url, $node = null)
    {
        if (empty($url)) return;
        $this->github->addBundleFromUrl($url, $node);
    }

    /**
     * @param $name
     * @return bool
     * @throws \Exception
     */
    public function uninstall($name)
    {
        return $this->github->uninstall($name);
    }

    public function update ($url, $name, $node = null)
    {
        $this->github->updateBundle($url, $name, $node);
    }

    public function loadFromCache($image)
    {
        $memory = new MemoryStream();
        $memory->write(file_get_contents(Ide::get()->getUserHome() . '\bundleManager\\' . md5($image)));
        $memory->seek(0);
        return $memory;
    }

    public function has($image)
    {
        return fs::exists(Ide::get()->getUserHome() . '\bundleManager\\' . md5($image)) && fs::size(Ide::get()->getUserHome() . '\bundleManager\\' . md5($image)) > 0;
    }
}