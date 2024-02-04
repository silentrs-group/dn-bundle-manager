<?php
namespace shop\ui;

use app;
use gui;

class UIActionButton 
{
    const STATE_INSTALL     = 0b001;
    const STATE_UNINSTALL   = 0b010;
    const STATE_UPDATE      = 0b100;
    const STATE_UNDEFINED   = 0;
    
    private $container;
    
    /**
     * @var int
     */
    private $state = 0;
    
    private $actionList = [];
    
    public function __construct ()
    {
        $this->make();
    }
    
    private function make ()
    {
        $this->container = new UXButton();
        $this->container->style = '-fx-padding: 0; -fx-background-color: transparent; -fx-border-width: 0;';
        $this->container->maxWidth =
        $this->container->minWidth =
        $this->container->maxHeight =
        $this->container->minHeight = 16;
        $this->container->graphic = new UXImageArea(new UXImage('res://.data/img/close.png', 16, 16));
        $this->container->graphic->width = 16;
        $this->container->graphic->height = 16;
        $this->container->opacity = 0.5;
        
        $this->container->on("mouseEnter", function () {
            $this->container->opacity = 0.7;
        });
        
        $this->container->on("mouseExit", function () {
            $this->container->opacity = 0.5;
        });
        
        $this->container->on("click", function () {
            if (is_callable($this->actionList[$this->state])) {
                call_user_func_array($this->actionList[$this->state], []);
            }
        });
        
        $this->container->contentDisplay = 'GRAPHIC_ONLY';
    }
    
    
    public function setAction ($state, $callback)
    {
        $this->actionList[$state] = $callback;
    }
    
    public function setState ($value)
    {
        $this->state = $value;
        
        switch ($this->state) {
            case UIActionButton::STATE_INSTALL:    $img = 'res://.data/img/download.png';  break;
            case UIActionButton::STATE_UNINSTALL:  $img = 'res://.data/img/close.png';     break;
            case UIActionButton::STATE_UPDATE:     $img = 'res://.data/img/update.png';    break;
            default:                               $img = 'res://.data/img/undefined.png';
        }
        
        
        $this->container->graphic->image = new UXImage($img, 16, 16);
    }
    
    public function getNode ()
    {
        return $this->container;
    }
}