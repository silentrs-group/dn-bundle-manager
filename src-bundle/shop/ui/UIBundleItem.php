<?php
namespace shop\ui;

use bundle\http\HttpClient;
use bundle\http\HttpResponse;
use framework;
use app;
use ide\Ide;
use ide\Logger;
use php\gui\framework\EventBinder;
use php\io\MemoryStream;
use php\io\Stream;
use php\lang\Thread;
use php\lib\fs;
use php\lib\str;
use std;
use gui;

class UIBundleItem 
{
    /**
     * @var UXHBox
     */
    private $container;
    
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
    
    private $iconSize = [32, 32];
    private $containerWidth = 200;
    private $state = 0;


    public function __construct () {
        $this->make();
    }
    
    private function make ()
    {
        $this->actionButton = new UIActionButton();
        
        $this->container = new UXHBox();
        $this->container->alignment = 'CENTER_LEFT';
        $this->container->minWidth = $this->containerWidth;
        $this->container->padding = 5;
        $this->container->paddingLeft = 10;
        $this->container->maxWidth = 291;
        
        $this->container->classes->add("listitem");
        
        $this->container->add($this->makeIcon());
        $this->container->add($infoContainer = new UXVBox());
        
        $infoContainer->paddingLeft = 10;
        
        $infoContainer->add(new UXHBox([
            $this->makeName(),
            $this->actionButton->getNode()
        ]));


        if ($this->state == UIActionButton::STATE_UNINSTALL) {
            $this->container->classes->add("installed");
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
        $subInfoContainer->add($this->makeVersion());
    }
    
    private function makeIcon ()
    {
        $this->icon = new UXImageArea(new UXImage('res://.data/img/default.png', $this->iconSize[0], $this->iconSize[1]));
        $this->icon->smooth = true;
        $this->icon->width = $this->iconSize[0];
        $this->icon->height = $this->iconSize[1];
        $this->icon->backgroundColor = '#0000000F';
        
        $this->icon->clip = new UXCircle();
        $this->icon->clip->width = $this->iconSize[0];
        $this->icon->clip->height = $this->iconSize[1];
        $this->icon->clip->x = $this->icon->clip->y = 0;
        
        return $this->icon;
    }
    
    private function makeName ()
    {
        $this->name = new UXLabelEx();
        $this->name->minWidth =
        $this->name->maxWidth = 216;
        $this->name->font->bold = true;
        
        return $this->name;
    }
    
    private function makeDescription ()
    {
        $this->description = new UXLabelEx();
        $this->description->textColor = 'gray';
        
        return $this->description;
    }
    
    private function makeAuthor ()
    {
        $this->author = new UXLabelEx();
        $this->author->minWidth = 173;
        $this->author->textColor = 'skyblue';
        
        return $this->author;
    }
    
    private function makeVersion ()
    {
        $this->version = new UXLabelEx();
        $this->version->minWidth = 60;
        $this->version->textColor = 'lightgray';
        
        return $this->version;
    }
    
    public function setActionButton ($state, $callback)
    {
        $this->actionButton->setAction($state, $callback);
    }
    
    public function getNode ()
    {
        return $this->container;
    }

    public function setState ($state)
    {
        $this->actionButton->setState($state);
    }
    
    public function setIcon ($path)
    {
        $this->icon->image = new UXImage('res://.data/img/default.png', $this->iconSize[0], $this->iconSize[1]);

        if (empty($path)) {
            return;
        }


        if (str::startsWith($path, "http")) {
            $th = new Thread(function () use ($path) {
                $memory = new MemoryStream();
                $memory->write(file_get_contents($path));

                if ($memory->length() > 0) {
                    $memory->seek(0);
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
    
    public function setName ($value)
    {
        $this->name->text = $value;
    }
    
    public function setDescrition ($value)
    {
        $this->description->text = $value;

        #UXTooltip::install($this->description, UXToolTip::of($value));
        #return;


        $this->description->on("mouseEnter", function ($e) use ($value) {
            $this->tool = $this->getTooltip($value);
            // $tool->showByNode($this->description, $position - $this->description->width / 2 - 12 , 28);
            $x = 0;
            if (($width = UXFont::getDefault()->withSize(14)->calculateTextWidth($value)) > 220) {
                $x  = -(($width - 220) / 2);
            }

            $this->tool->showByNode($e->sender, $x, 12);
        });

        $this->description->on("mouseExit", function () {
            $this->tool->hide();
        });
    }
    
    public function setAuthor ($value)
    {
        $this->author->text = $value;
    }
    
    public function setVersion ($value)
    {
        $this->version->text = "ver: " . $value;
    }


    /**
     * @return UXTooltip
     */
    private function getTooltip ($text) {
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


}