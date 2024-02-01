<?php

namespace shop\bundle;

use gui;
use php\compress\ZipFile;

abstract class BaseBundleProvider
{
    /**
     * @var BundleOperation
     */
    public $bundleOperation;

    protected $list = [];

    public function __construct()
    {
        $this->bundleOperation = new BundleOperation();
        $this->init();
    }

    public abstract function init();

    public abstract function update();

    public function getList()
    {
        return $this->list;
    }

    public function addBundleFromUrl($url, $node = null)
    {
        $this->bundleOperation->addBundleFromUrl($url, $node);
        $this->bundleOperation->refresh();
    }

    public function updateBundle($url, $name, $node = null)
    {
        $this->bundleOperation->update($url, $this->bundleOperation->get($name), $node);
        $this->bundleOperation->refresh();
    }

    /**
     * @throws \Exception
     */
    public function uninstall($name)
    {
        $state = $this->bundleOperation->remove($this->bundleOperation->get($name));
        $this->bundleOperation->refresh();
        return $state;
    }
}