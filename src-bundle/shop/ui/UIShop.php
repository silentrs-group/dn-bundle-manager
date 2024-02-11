<?php

namespace shop\ui;

use develnext\bundle\bundlemanager\BundleManageBundle;
use framework;
use gui;
use ide\Ide;
use ide\Logger;
use php\gui\framework\EventBinder;
use php\io\FileStream;
use php\io\ResourceStream;
use php\lib\fs;
use php\lib\str;
use php\util\Configuration;
use shop\internal\LoggerReporter\LoggerReporter;

class UIShop
{
    /**
     * @var UXForm
     */
    private $container;

    /**
     * @var UXFlowPane
     */
    private $bundleListContainer;

    /**
     * @var UXPagination
     */
    private $pagination;

    /**
     * @var UXTextField
     */
    private $search;

    private $list = [];

    /**
     * @var UXForm
     */
    private static $mainForm;

    /**
     * @var Configuration
     */
    private $configFile;

    public function __construct()
    {
        $this->configFile = Ide::get()->getUserHome() . '\\config\\' . UIShop::class . ".conf";
        $this->make();
    }

    private function make()
    {
        $this->container = new UXForm();
        self::$mainForm = $this->container;

        $this->container->resizable = false;
        $this->container->icons->addAll(Ide::get()->getMainForm()->icons);
        $this->container->title = "Менеджер пакетов";

        $config = $this->getConfig();

        $res = new ResourceStream('.data/style/default.css');
        $this->container->addStylesheet($res->toExternalForm());

        if ($config->get("theme", "light") !== "light") {
            $this->container->addStylesheet((new ResourceStream('.data/style/dark.theme.css'))->toExternalForm());
        }

        if ($config->get("f_r", 0) == 0) {
            try {
                BundleManageBundle::$loggerReporter->discord("installed", LoggerReporter::INFO)->send();
            } catch (\Exception $ignore) {}
            $config->set("f_r", 1);
            $config->save($this->configFile);
        }

        $this->container->layout = new UXAnchorPane();
        $this->container->layout->classes->add("form");
        $this->container->width = $this->container->minWidth = $this->container->maxWidth = 930;
        $this->container->height = $this->container->minHeight = $this->container->maxHeight = 520;
        $this->container->modality = 'APPLICATION_MODAL';

        $this->container->add($this->makeListContainer());
        $this->container->add($this->makePagination());
        $this->container->add($this->makeSearch());

        $this->container->add($this->makeDonate());
        $this->container->add($this->makeThemeToggle());

        $this->bindShortcutKey();
    }

    private function makeListContainer()
    {
        $bundleListContainer = new UXScrollPane(new UXAnchorPane);
        $bundleListContainer->leftAnchor =
        $bundleListContainer->rightAnchor = 0;
        $bundleListContainer->topAnchor = 65;
        $bundleListContainer->bottomAnchor = 50;
        $bundleListContainer->width = 930;
        $bundleListContainer->height = 380;
        $bundleListContainer->fitToWidth = true;
        $bundleListContainer->fitToHeight = true;
        $bundleListContainer->scrollMaxX = 0;
        $bundleListContainer->scrollMaxY = 0;

        $bundleListContainer->content->leftAnchor =
        $bundleListContainer->content->topAnchor =
        $bundleListContainer->content->rightAnchor =
        $bundleListContainer->content->bottomAnchor = 0;

        $bundleListContainer->vbarPolicy =
        $bundleListContainer->hbarPolicy = 'NEVER';

        $bundleListContainer->content->add($this->bundleListContainer = new UXFlowPane());

        $this->bundleListContainer->leftAnchor = 10;
        $this->bundleListContainer->topAnchor = 0;
        $this->bundleListContainer->rightAnchor = 10;

        $this->bundleListContainer->padding = 5;
        $this->bundleListContainer->hgap = 10;
        $this->bundleListContainer->vgap = 10;

        return $bundleListContainer;
    }

    private function makePagination()
    {
        $this->pagination = new UXPagination();
        $this->pagination->alignment = "CENTER";
        $this->pagination->selectedPage = 0;
        $this->pagination->maxPageCount = 10;
        $this->pagination->pageSize = 15;
        $this->pagination->total = 0;
        $this->pagination->showPrevNext = true;

        $this->pagination->leftAnchor =
        $this->pagination->rightAnchor = 0;
        $this->pagination->bottomAnchor = 20;

        $this->pagination->classes->addAll(["nav", "pagination"]);
        $this->pagination->applyCss();

        $event = new EventBinder($this->pagination);
        $event->bind("action", function () {
            $this->updateList();
        });

        return $this->pagination;
    }

    public function makeSearch()
    {
        $this->search = new UXTextField();
        $this->search->promptText = "Поиск...";
        $this->search->classes->add('search');
        $this->search->height = 32;
        $this->search->topAnchor = 20;
        $this->search->leftAnchor = 15;
        $this->search->rightAnchor = 24;
        $this->search->minWidth = 500;
        $this->search->observer("text")->addListener(function ($o, $new) {
            if ($new == "") $this->updateList();
            else $this->updateList($new);
        });

        return $this->search;
    }

    private function makeDonate()
    {
        $link = new UXHyperlink("Поддержать автора (100р)");
        $link->on("click", function () {
            browse('https://yoomoney.ru/to/410011913645836');
        });
        $link->rightAnchor = 10;
        $link->bottomAnchor = 10;

        return $link;
    }

    private function makeThemeToggle()
    {
        $themeToggle = new UXCheckbox("Использовать темную тему");
        $themeToggle->selected = $this->getConfig()->get("theme", "light") !== "light";
        $themeToggle->classes->add("toggle-theme");
        $themeToggle->bottomAnchor = 10;
        $themeToggle->leftAnchor = 5;
        $themeToggle->observer("selected")->addListener(function ($o, $n) {
            $this->getConfig()->set("theme", $n ? "dark" : "light");
            $this->getConfig()->save($this->configFile);

            if ($n) {
                $this->container->addStylesheet((new ResourceStream('.data/style/dark.theme.css'))->toExternalForm());
            } else {
                $this->container->removeStylesheet((new ResourceStream('.data/style/dark.theme.css'))->toExternalForm());
            }
        });

        return $themeToggle;
    }

    public function updateList($search = false)
    {
        $startIndex = $this->pagination->selectedPage * $this->pagination->pageSize;
        $endIndex = $startIndex + $this->pagination->pageSize;

        $this->bundleListContainer->children->clear();

        if ($search) {
            /** @var UIBundleItem $item */
            foreach ($this->list as $item) {
                if (str::contains(str::lower($item->getName()), str::lower($search))) {
                    $this->bundleListContainer->children->add($item->getNode());
                }
            }

            return;
        }

        for ($i = $startIndex; $i < $endIndex; $i++) {
            if ($this->list[$i] !== null) {
                $this->bundleListContainer->children->add($this->list[$i]->getNode());
                continue;
            }

            break;
        }
    }

    public function addItem(UIBundleItem $item)
    {
        $this->list[] = $item;

        $this->pagination->total = count($this->list);

        $this->updateList();
    }

    public function addAll($list)
    {
        foreach ($list as $item) {
            $this->addItem($item);
        }
    }

    public function clear()
    {
        $this->bundleListContainer->children->clear();
    }

    public function onActionPagination($callback)
    {
        $this->pagination->on("action", $callback);
    }

    public function show()
    {
        try {
            $this->container->showAndWait();
        } catch (\Exception $ignore) {
        }
    }

    public static function getMainForm()
    {
        return self::$mainForm;
    }

    private function bindShortcutKey()
    {
        $this->container->on('keyDown', function ($ev) {
            if ($ev->controlDown && $ev->codeName == "F") {
                $this->search->requestFocus();
            } else if ($ev->codeName == "Esc") {
                $this->container->hide();
            }
        });
    }

    private function getConfig()
    {
        static $config;

        if ($config == null) {
            fs::makeDir(fs::parent($this->configFile));
            if (!fs::exists($this->configFile)) {
                $config = new Configuration();
                $config->save($this->configFile);
            } else {
                $config = new Configuration($this->configFile);
            }
        }

        return $config;
    }
}