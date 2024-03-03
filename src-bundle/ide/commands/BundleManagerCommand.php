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
use php\io\IOException;
use php\lang\Thread;
use php\lib\str;
use shop\BundleService;
use shop\dto\Bundle;
use shop\ui\category\AbstractCategory;
use shop\ui\category\BundleCategory;
use shop\ui\category\FontsCategory;
use shop\ui\category\IconsCategory;
use shop\ui\category\TestCategory;
use shop\ui\UIActionButton;
use shop\ui\UIBundleItem;
use shop\ui\UIShop;

class BundleManagerCommand extends AbstractCommand
{

    const CACHE_DIR = '\bundleManager';

    /**
     * @var UIShop
     */
    private $form;

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

    /**
     * @return UXButton
     */
    public function makeUiForHead()
    {
        $this->form = new UIShop();
        $this->form->addCategory(BundleCategory::class);
        $this->form->addCategory(FontsCategory::class);
        $this->form->addCategory(IconsCategory::class);

        $this->service = new BundleService();

        $thread = new Thread(function () {
            $installList = $this->service->getInstalledList();

            foreach ($this->form->getCategories() as $category) {
                uiLater(function () use ($installList, $category) {
                    foreach ($this->service->getList()[$category->getKey()] as $item) {
                        $this->makeItem($item, $installList, $category);
                    }
                });
            }
        });
        $thread->setDaemon(true);
        $thread->start();


        $btn = new UXButton();
        $btn->graphic = ico("cart");
        $btn->font->bold = true;
        $btn->text = "Магазин пакетов";
        $btn->on("click", [$this, "onExecute"]);

        return $btn;
    }

    /**
     * @param Bundle $bundle
     * @param $installList
     * @param AbstractCategory $category
     * @return void
     * @throws IOException
     */
    function makeItem(Bundle $bundle, $installList, AbstractCategory $category): void
    {
        $node = new UIBundleItem();
        $this->form->addItem($node, $category);
        // $category->setContent($node->getNode());

        $node->setName($bundle->name);
        $node->setAuthor($bundle->author);
        $node->setDescription($bundle->description);
        $this->setIcon($bundle, $node);
        $node->setVersion($bundle->version);
        $this->setState($bundle, $node, $installList);
        $node->setUrlExample($bundle->exampleUrl);

        $node->setActionButton(UIActionButton::STATE_INSTALL, function () use ($bundle, $node) {
            try {
                if (($category = $this->form->getActiveCategory())->getKey() != 'bundle') {
                    return $category->install($bundle->url, $node);
                }
            } catch (\Exception $ignore) {}

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
     * @throws IOException
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
            $node->setState(UIActionButton::STATE_UNDEFINED);
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