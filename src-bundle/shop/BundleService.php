<?php

namespace shop;

use ide\commands\BundleManagerCommand;
use ide\Ide;
use php\io\IOException;
use php\io\MemoryStream;
use php\lib\fs;
use shop\dto\Bundle;
use shop\bundle\CacheProvider;
use shop\bundle\GithubProvider;
use shop\bundle\LocalProvider;
use shop\internal\Cache;

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

    public function update($url, $name, $node = null)
    {
        $this->github->updateBundle($url, $name, $node);
    }

    /**
     * @throws IOException
     */
    public function loadFromCache($image)
    {
        return Cache::get(Ide::get()->getUserHome() . BundleManagerCommand::CACHE_DIR . '\\' . md5($image));
    }

    public function has($image)
    {
        $path = Ide::get()->getUserHome() . BundleManagerCommand::CACHE_DIR . '\\' . md5($image);
        return fs::exists($path) && fs::size($path) > 0;
    }
}