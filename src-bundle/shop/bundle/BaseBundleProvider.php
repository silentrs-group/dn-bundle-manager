<?php

namespace shop\bundle;

use gui;
use php\compress\ZipFile;
use shop\dto\Bundle;

abstract class BaseBundleProvider
{
    /**
     * @var BundleOperation
     */
    public $bundleOperation;

    /**
     * @var Bundle[]
     */
    protected $list = [];

    public function __construct()
    {
        $this->bundleOperation = new BundleOperation();
        $this->init();
    }

    public abstract function init();

    public abstract function update();

    /**
     * @return Bundle[]
     */
    public function getList(): array
    {
        return $this->list;
    }

    public function addBundleFromUrl($url, $node = null): void
    {
        $this->bundleOperation->addBundleFromUrl($url, $node);
        $this->bundleOperation->refresh();
    }

    /**
     * @param string $url
     * @param string $name
     * @param $node
     * @return void
     */
    public function updateBundle(string $url, string $name, $node = null): void
    {
        $this->bundleOperation->update($url, $this->bundleOperation->get($name), $node);
        $this->bundleOperation->refresh();
    }

    /**
     * @param string $name
     * @return bool
     */
    public function uninstall(string $name): bool
    {
        $state = $this->bundleOperation->remove($this->bundleOperation->get($name));
        $this->bundleOperation->refresh();
        return $state;
    }
}