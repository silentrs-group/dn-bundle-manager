<?php

namespace shop\ui\category;

use gui;
use http\Exception\RuntimeException;
use ide\Logger;
use php\compress\ZipFile;
use php\io\File;
use php\lib\fs;

abstract class AbstractCategory
{
    /**
     * @var UXTab
     */
    protected $tab;

    /**
     * @var UXScrollPane
     */
    protected $scrollPane;

    /**
     * @var UXFlowPane
     */
    protected $container;

    protected $id;


    public function __construct()
    {
        Logger::info(__METHOD__);
        $this->getTab();
    }

    public function getName()
    {
        return __CLASS__;
    }

    public function getKey()
    {
        return $this->id;
    }

    public function getTab()
    {
        if (!$this->tab) {
            $this->tab = new UXTab();
            $this->tab->text = $this->getName();
            $this->tab->data("key", $this->getKey());
            $this->tab->draggable = false;
            $this->tab->closable = false;
            $this->tab->content = $this->scrollPane = new UXScrollPane($anc = new UXAnchorPane());
            $this->tab->id = $this->id;

            $anc->children->add($this->container = new UXTilePane);
            $anc->padding = 0;

            $this->scrollPane->vbarPolicy = 'AS_NEEDED';

            $this->scrollPane->leftAnchor =
            $this->scrollPane->topAnchor =
            $this->scrollPane->rightAnchor =
            $this->scrollPane->bottomAnchor = 0;
            $this->scrollPane->fitToWidth = true;
            $this->scrollPane->fittoHeight = true;

            $this->container->leftAnchor =
            $this->container->rightAnchor = 0;

            $this->container->padding = 5;
            $this->container->hgap = 10;
            $this->container->vgap = 10;
        }

        return $this->tab;
    }

    public function setContent($node)
    {
        $this->container->children->add($node);
    }

    public function install($url)
    {
        throw new RuntimeException('Not implementation method ' . __METHOD__);
    }

    protected function unpack(File $file, $to)
    {
        $to = fs::normalize($to);
        fs::makeDir($to);

        $zip = new ZipFile($file);
        $zip->unpack($to);
        $file->deleteOnExit();
    }

    public function removeContent($node)
    {
        $this->container->children->remove($node);
    }

    public function clear()
    {
        $this->container->children->clear();
    }
}