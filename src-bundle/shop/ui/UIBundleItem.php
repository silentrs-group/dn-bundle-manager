<?php
namespace shop\ui;

use bundle\http\HttpClient;
use bundle\http\HttpResponse;
use framework;
use app;
use ide\Ide;
use ide\Logger;
use php\io\Stream;
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
        $this->icon = new UXImageView(new UXImage('res://.data/img/default.png', $this->iconSize[0], $this->iconSize[1]));
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
        // $this->icon->image = new UXImage($stream, $this->iconSize[0], $this->iconSize[1]);

        $this->icon->image = new UXImage('res://.data/img/default.png', $this->iconSize[0], $this->iconSize[1]);

        if (empty($path)) {
            return;
        }


        if (str::startsWith($path, "http")) {
            $http = new HttpClient();
            $http->responseType = "STREAM";
            $http->getAsync($path, [], function (HttpResponse $response) use ($path) {
                $image = $response->body();
                fs::copy($image, Ide::get()->getUserHome() . '\bundleManager\\' . md5($path));
                $this->icon->image = new UXImage(Ide::get()->getUserHome() . '\bundleManager\\' . md5($path), $this->iconSize[0], $this->iconSize[1]);
            });
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

        $tooltip = new UXTooltip();
        $tooltip->text = $value;
        UXTooltip::install($this->description, $tooltip);
    }
    
    public function setAuthor ($value)
    {
        $this->author->text = $value;
    }
    
    public function setVersion ($value)
    {
        $this->version->text = "ver: " . $value;
    }
    
    
    
}