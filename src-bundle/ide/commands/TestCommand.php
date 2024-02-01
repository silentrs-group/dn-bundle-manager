<?php

namespace ide\commands;

use ide\editors\AbstractEditor;
use ide\Logger;
use ide\misc\AbstractCommand;
use php\gui\UXButton;
use php\gui\UXForm;
use php\gui\layout\UXTilePane;
use php\gui\layout\UXAnchorPane;
use php\gui\layout\UXScrollPane;
use php\lang\Thread;
use php\lib\str;
use shop\BundleService;
use shop\dto\Bundle;
use shop\ui\UIActionButton;
use shop\ui\UIBundleItem;
use shop\ui\UIShop;

class TestCommand extends AbstractCommand
{

    /**
     * @var UIShop
     */
    private $form;
    private $container;
    private $installedBundleList = [];

    /**
     * @var BundleService
     */
    private $service;

    public function getName()
    {
        return __CLASS__;
    }

    public function getIcon()
    {
        return "icons/cart.png";
    }


    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        $this->form->show();
    }

    public function makeUiForHead()
    {
        $this->form = new UIShop();
        $this->service = new BundleService();

        $thread = new Thread(function () {
            $installList = $this->service->getInstalledList();

            foreach ($this->service->getList() as $bundle) {
                uiLater(function () use ($installList, $bundle) {
                    $this->makeItem($bundle, $installList);
                });
            }
        });
        $thread->setDaemon(true);
        $thread->start();


        $btn = new UXButton();
        $btn->graphic = ico("cart");
        $btn->font->bold = true;
        $btn->text = "Магазин бандлов";
        try {
            $btn->on("click", [$this, "onExecute"]);
        } catch (\Exception $ignore) {

        }

        return $btn;
    }

    /**
     * @param Bundle $bundle
     * @param $installList
     * @return void
     */
    function makeItem(Bundle $bundle, $installList): void
    {
        $node = new UIBundleItem();
        $node->setName($bundle->name);
        $node->setAuthor($bundle->author);
        $node->setDescrition($bundle->description);
        $this->setIcon($bundle, $node);
        $node->setVersion($bundle->version);
        $this->setState($bundle, $node, $installList);

        $this->form->addItem($node);
        $node->setActionButton(UIActionButton::STATE_INSTALL, function () use ($bundle, $node) {
            $this->service->install($bundle->url, $node);
        });

        $node->setActionButton(UIActionButton::STATE_UNINSTALL, function () use ($bundle, $node) {
            if ($this->service->uninstall($bundle->name)) {
                $node->setState(UIActionButton::STATE_INSTALL);
            }
        });

        $node->setActionButton(UIActionButton::STATE_UPDATE, function () use ($bundle, $node) {
            $this->service->update($bundle->url, $bundle->name, $node);
        });
    }

    /**
     * @param Bundle $bundle
     * @param UIBundleItem $node
     * @return void
     */
    private function setIcon(Bundle $bundle, UIBundleItem $node): void
    {
        if ($this->service->has($bundle->image)) {
            $node->setIcon($this->service->loadFromCache($bundle->image));
        } else {
            $node->setIcon($bundle->image);
        }
    }

    /**
     * @param Bundle $bundle
     * @param UIBundleItem $node
     * @param $installList
     * @return void
     */
    private function setState(Bundle $bundle, UIBundleItem $node, $installList): void
    {
        if (empty($bundle->url)) {
            $node->setState(UIActionButton::STATE_UNENDEFINED);
        } else {
            $state = UIActionButton::STATE_INSTALL;
            foreach ($installList as $item) {
                if ($item->name == $bundle->name) {
                    $state = UIActionButton::STATE_UNINSTALL;

                    if (str::compare($item->version, $bundle->version) <= -1) {
                        $state = UIActionButton::STATE_UPDATE;
                    }

                    break;
                }
            }

            $node->setState($state);
        }
    }


}