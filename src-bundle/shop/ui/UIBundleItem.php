<?php

namespace shop\ui;

use framework;
use gui;
use ide\Ide;
use ide\Logger;
use php\io\Stream;
use php\lang\Thread;
use php\lib\fs;
use php\lib\str;
use shop\internal\Http;
use std;

class UIBundleItem
{
    private $container;

    /**
     * @var UXHBox
     */
    private $baseContainer;

    /**
     * @var UXImageArea
     */
    private $icon;

    /**
     * @var UXLabelEx
     */
    private $name;

    /**
     * @var UXLabelEx
     */
    private $description;

    /**
     * @var UXLabelEx
     */
    private $version;

    /**
     * @var UXLabelEx
     */
    private $author;

    /**
     * @var UIActionButton
     */
    private $actionButton;

    /**
     * @var UIProjectExampleButton
     */
    private $projectExampleButton;

    /**
     * @var UXProgressIndicator
     */
    private $progress;

    /**
     * @var int[]
     */
    private $iconSize = [32, 32];

    /**
     * @var int
     */
    private $containerWidth = 201;

    /**
     * @var int
     */
    private $state = 0;

    private $containerMaxWidth = 290;
    /**
     * @var mixed
     */
    private $exampleUrl;


    public function __construct()
    {
        $this->make();
    }

    private function make()
    {
        $this->actionButton = new UIActionButton();
        $this->makeProjectExampleButton();

        $this->container = new UXStackPane();
        $this->container->minWidth = $this->containerWidth;
        $this->container->maxWidth = $this->containerMaxWidth;
        $this->container->maxHeight = 60;

        $this->baseContainer = new UXHBox();

        $this->container->add($this->baseContainer);

        $this->baseContainer->alignment = 'CENTER_LEFT';
        $this->baseContainer->minWidth = $this->containerWidth;
        $this->baseContainer->padding = 5;
        $this->baseContainer->paddingLeft = 10;
        $this->baseContainer->maxWidth = $this->containerMaxWidth;
        $this->baseContainer->leftAnchor =
        $this->baseContainer->topAnchor =
        $this->baseContainer->rightAnchor =
        $this->baseContainer->bottomAnchor = 0;

        $this->baseContainer->classes->add("listitem");

        $this->baseContainer->add($this->makeIcon());
        $this->baseContainer->add($infoContainer = new UXVBox());

        $infoContainer->paddingLeft = 10;
        $infoContainer->minWidth = $this->containerWidth;
        $infoContainer->maxWidth = $this->containerMaxWidth;
        $infoContainer->width = 100;

        $infoContainer->add($header = new UXHBox([
            $l1 = new UXHbox([$this->makeName(), $o = new UXLabelEx(" ("), $this->makeVersion(), $c = new UXLabelEx(")")])
        ]));

        $this->baseContainer->add($buttonsList = new UXVbox([$this->actionButton->getNode(), $this->projectExampleButton->getNode()]));

        $buttonsList->spacing = 5;

        $o->textColor = $c->textColor = "darkgray";
        $header->minWidth = 240;
        $header->maxWidth = 240;
        $l1->minWidth = 215;


        if ($this->state == UIActionButton::STATE_UNINSTALL) {
            $this->baseContainer->classes->add("installed");
        }

        $this->actionButton->setState($this->state);

        $this->actionButton->setAction(UIActionButton::STATE_UNINSTALL, function () {
            Logger::info("uninstall " . $this->name->text);
        });

        $this->actionButton->setAction(UIActionButton::STATE_INSTALL, function () {
            Logger::info("install " . $this->name->text);
        });

        $infoContainer->add($this->makeDescription());
        $infoContainer->add($subInfoContainer = new UXHBox());

        $subInfoContainer->add($this->makeAuthor());

        $this->makeProgress();
    }

    private function makeIcon(): UXImageArea
    {
        $this->icon = new UXImageArea(new UXImage('res://.data/img/default.png', $this->iconSize[0], $this->iconSize[1]));
        $this->icon->smooth = true;
        $this->icon->width = $this->iconSize[0];
        $this->icon->height = $this->iconSize[1];
        $this->icon->backgroundColor = '#e3e3e3';

        $this->icon->clip = new UXCircle();
        $this->icon->clip->width = $this->iconSize[0];
        $this->icon->clip->height = $this->iconSize[1];
        $this->icon->clip->x = $this->icon->clip->y = 0;

        return $this->icon;
    }

    private function makeName(): UXLabelEx
    {
        $this->name = new UXLabelEx();
        $this->name->classes->add("name");
        $this->name->maxWidth = 160;
        $this->name->font->bold = true;

        return $this->name;
    }

    private function makeDescription(): UXLabelEx
    {
        $this->description = new UXLabelEx();
        $this->description->textColor = 'gray';
        $this->description->maxWidth = 220;

        return $this->description;
    }

    private function makeAuthor(): UXLabelEx
    {
        $this->author = new UXLabelEx();
        $this->author->minWidth = 173;
        $this->author->textColor = 'skyblue';

        return $this->author;
    }

    private function makeVersion(): UXLabelEx
    {
        $this->version = new UXLabelEx();
        $this->version->autoSize = true;
        $this->version->textColor = 'lightgray';

        return $this->version;
    }

    public function setActionButton($state, $callback)
    {
        $this->actionButton->setAction($state, $callback);
    }

    public function setUrlExample($url)
    {
        if (!str::startsWith($url, "http")) return;

        $this->exampleUrl = $url;
        $this->projectExampleButton->getNode()->visible = true;
    }

    public function getNode(): UXStackPane
    {
        return $this->container;
    }

    public function setState($state)
    {
        $this->actionButton->setState($state);
    }

    public function setIcon($path)
    {
        $this->icon->image = new UXImage('res://.data/img/default.png', $this->iconSize[0], $this->iconSize[1]);

        if (empty($path)) {
            return;
        }

        if (str::endsWith($path, 'res://.data/img/default.png')) {
            $path = 'res://.data/img/default.png';
        }

        if (str::startsWith($path, "http") && str::endsWith($path, "png") && !str::endsWith($path, 'res://.data/img/default.png')) {

            $th = new Thread(function () use ($path) {
                $memory = Http::get($path, "stream");
                if ($memory instanceof Stream) {
                    Logger::error($path);
                    fs::copy($memory, Ide::get()->getUserHome() . '\bundleManager\\' . md5($path));

                    uiLater(function () use ($path) {
                        $this->icon->image = new UXImage(Ide::get()->getUserHome() . '\bundleManager\\' . md5($path), $this->iconSize[0], $this->iconSize[1]);
                    });
                }
            });
            $th->setDaemon(true);
            $th->start();
        } else {
            if ($path instanceof Stream) {
                $path->seek(0);
            }

            $this->icon->image = new UXImage($path, $this->iconSize[0], $this->iconSize[1]);
            $this->icon->centered = true;
            $this->icon->stretch = true;
        }
    }

    public function setName($value)
    {
        $this->name->text = $value;
    }

    public function setDescription($value)
    {
        $this->description->text = $value;

        $this->description->on("mouseEnter", function ($e) use ($value) {
            $this->tool = $this->getTooltip($value);
            $x = 0;
            if (($width = UXFont::getDefault()->withSize(14)->calculateTextWidth($value)) > 220) {
                $x = -(($width - 220) / 2);
            }

            $this->tool->showByNode($e->sender, $x, 14);
        });

        $this->description->on("mouseExit", function () {
            $this->tool->hide();
        });
    }

    public function setAuthor($value)
    {
        $this->author->text = $value;
    }

    public function setVersion($value)
    {
        $this->version->text = "ver: " . trim($value);
    }


    /**
     * @param $text
     * @return UXTooltip
     */
    private function getTooltip($text): UXTooltip
    {
        static $tool = null, $label;

        if ($tool == null) {
            $tool = new UXTooltip();
            $tool->graphic = new UXVBox([$ar = new UXPane(), $label = new UXLabelEx($text)]);
            $tool->graphic->classes->add("custom-tooltip");
            $tool->graphic->spacing = 5;

            $ar->classes->add('arrow-toltip');
            $ar->maxWidth = 16;
            $ar->minHeight = 10;
            $tool->graphic->alignment = "CENTER";
            $tool->style = '-fx-background-color: transparent;';
        }

        $label->text = $text;

        return $tool;
    }

    public function getName()
    {
        return $this->name->text;
    }

    private function makeProgress()
    {
        $this->progress = new UXProgressIndicator();
        $this->container->add($this->progress);
        $this->progress->maxWidth = 32;
        $this->progress->maxHeight = 32;
        $this->progress->visible = false;
    }

    public function showProgress()
    {
        uiLater(function () {
            $this->baseContainer->enabled = false;
            $this->progress->visible = true;
        });
    }

    public function hideProgress()
    {
        uiLater(function () {
            $this->baseContainer->enabled = true;
            $this->progress->visible = false;
        });
    }

    /**
     * @return void
     */
    public function makeProjectExampleButton(): void
    {
        $this->projectExampleButton = new UIProjectExampleButton();
        $this->projectExampleButton->setAction(UIActionButton::STATE_UNDEFINED, function () {
            if (str::startsWith($this->exampleUrl, "http")) {
                browse($this->exampleUrl);
            }
        });

        $this->projectExampleButton->getNode()->visible = false;
    }


}