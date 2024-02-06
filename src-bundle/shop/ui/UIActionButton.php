<?php
namespace shop\ui;

use app;
use gui;
use ide\Logger;

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
        $this->container->classes->add("action-button");
        $this->container->maxWidth =
        $this->container->minWidth =
        $this->container->maxHeight =
        $this->container->minHeight = 16;
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
            case UIActionButton::STATE_INSTALL:    $img = 'install';   break;
            case UIActionButton::STATE_UNINSTALL:  $img = 'uninstall'; break;
            case UIActionButton::STATE_UPDATE:     $img = 'update';    break;
            default:                               $img = 'undefined';
        }

        $this->container->classes->add($img);
        foreach ($this->container->classes as $class) {
            if ($class == $img || $class == "action-button" || $class == "button") continue;
            Logger::warn("remove class: " . $class);
            $this->container->classes->remove($class);
        }
    }
    
    public function getNode ()
    {
        return $this->container;
    }
}