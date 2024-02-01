<?php
namespace shop\ui;

use framework;
use app;
use gui;
use ide\formats\ProjectFormat;
use ide\Ide;
use ide\Logger;
use ide\project\behaviours\bundle\BundlesProjectControlPane;
use php\gui\framework\EventBinder;
use php\io\ResourceStream;

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
    
    public function __construct ()
    {
        $this->make();
    }
    
    private function make ()
    {
        $this->container = new UXForm();
        $this->container->resizable = false;
        $this->container->icons->addAll(Ide::get()->getMainForm()->icons);
        $this->container->title = "Менеджер пакетов";

        $res = new ResourceStream('.data/style/default.css');
        $this->container->addStylesheet($res->toExternalForm());

        $this->container->minWidth = $this->container->maxWidth = 930;
        $this->container->minHeight = $this->container->maxHeight = 520;
        $this->container->modality = 'APPLICATION_MODAL';

        $this->container->add($this->makeListContainer());
        $this->container->add($this->makePagination());
        $this->container->add($this->makeSearch());
    }
    
    private function makeListContainer ()
    {
        $bundleListContainer = new UXScrollPane(new UXAnchorPane);
        $bundleListContainer->leftAnchor = 
        $bundleListContainer->rightAnchor = 0;
        $bundleListContainer->topAnchor = 65;
        $bundleListContainer->bottomAnchor = 50;
        $bundleListContainer->fitToWidth = true;
        $bundleListContainer->fitToHeight = true;
        $bundleListContainer->scrollMaxX = 0;
        $bundleListContainer->scrollMaxY = 0;

        /*$bundleListContainer->on("click", function () {
            Logger::error('Click!');
            $var = Ide::project()->getRegisteredFormat(ProjectFormat::class);

            foreach ($var->getControlPanes() as $pane) {
                /** @var $pane BundlesProjectControlPane * /
                if ($pane instanceof BundlesProjectControlPane) {
                    $pane->refresh();
                }
            }
        });*/
        
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
        
        $this->bundleListContainer->hgap = 10;
        $this->bundleListContainer->vgap = 10;
    
        return $bundleListContainer;
    }
    
    private function makePagination ()
    {
        $this->pagination = new UXPagination();
        $this->pagination->alignment = "CENTER";
        $this->pagination->selectedPage = 0;
        $this->pagination->pageSize = 15;
        $this->pagination->total = 0;
        $this->pagination->showPrevNext = true;
        
        $this->pagination->leftAnchor = 
        $this->pagination->rightAnchor = 0;
        $this->pagination->bottomAnchor = 10;
        
        $this->pagination->classes->addAll(["nav", "pagination"]);
        $this->pagination->applyCss();
        
        $event = new EventBinder($this->pagination);
        $event->bind("action",function () {
            $this->updateList();
        });
        
        return $this->pagination;
    }
    
    public function makeSearch ()
    {
        $this->search = new UXTextField();
        $this->search->promptText = "Поиск...";
        $this->search->classes->add('search');
        $this->search->height = 32;
        $this->search->leftAnchor = 
        $this->search->topAnchor = 10;
        $this->search->rightAnchor = 60;
        $this->search->visible = false;
        
        return $this->search;
    }
    
    public function updateList ($isSearch = false)
    {
        $startIndex = $this->pagination->selectedPage * $this->pagination->pageSize;
        $endIndex = $startIndex + $this->pagination->pageSize;
        
        $this->bundleListContainer->children->clear();
        
        if ($isSearch) {
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
    
    public function addItem (UIBundleItem $item)
    {
        $this->list[] = $item;
        
        $this->pagination->total = count($this->list);
        
        $this->updateList();
    }
    
    public function addAll ($list)
    {
        foreach ($list as $item) {
            $this->addItem($item);
        }
    }
    
    public function clear ()
    {
        $this->bundleListContainer->children->clear();
    }
    
    public function onActionPagination ($callback)
    {
        $this->pagination->on("action", $callback);
    }
    
    public function show ()
    {
        try {
            $this->container->showAndWait();
        } catch (\Exception $ignore) {}
    }
}