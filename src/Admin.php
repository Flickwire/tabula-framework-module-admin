<?php 
namespace Tabula\Modules\Admin;

use Tabula\Tabula;
use Tabula\Module;
use Tabula\Router;
use Tabula\Router\Route;
use Tabula\Database\Adapter\AbstractAdapter;
use Tabula\Renderer\Page;

class Admin implements Module {
    
    private $panes = [];
    private $groups = [];
    private $tabula;
    private $activePane;

    public function upgrade(string $version, AbstractAdapter $db): string{
        return '1.0';
    }

    public function registerRoutes(Router $router): void{
    }

    public function preInit(Tabula $tabula): void{
        $this->tabula = $tabula;
        $tabula->registry->setAdminPanel($this);
        $tabula->renderer->registerTemplateDir(__DIR__.DS.'templates');
        $tabula->renderer->registerScriptDir(__DIR__.DS.'scripts');
    }

    public function init(): void{
        if(class_exists("\Tabula\Modules\Auth\SecureRoute")){
            $this->tabula->router->register(new \Tabula\Modules\Auth\SecureRoute($this->tabula,"/admin",$this,"render"));
        } else {
            $this->tabula->router->register(new Route("/admin",$this,"render"));
        }
    }

    public function getName(): string{
        return 'tabula-admin';
    }

    public function registerPane(AdminPane $pane, ?string $group = null): void{
        //Throw pane into a group if one was provided
        if ($group !== null){
            if (!isset($this->groups[$group])){
                $this->groups[$group] = [];
            }
            $this->groups[$group][] = $pane;
        } else {
            $this->panes[] = $pane;
        }
    }

    public function render(): void{
        $page = new Page($this->tabula, 'modules/admin/admin.html');
        $this->tabula->renderer->addScript('admin/admin.js');

        $groups = $this->prepareGroups();
        $activeItem = $this->activeItem();
        
        $errors = $this->tabula->session->getErrors();
        $semantic = $this->tabula->registry->getUriBase().'/vendor/semantic/ui/dist/';
        $semanticJs = $semantic . 'semantic.min.js';
        $semanticCss = $semantic . 'semantic.min.css';

        $page->set('adminUrl',$this->tabula->registry->getUriBase().'admin?pane=');
        $page->set('groups',$groups);
        $page->set('items',$this->panes);
        $page->set('activeItem',$activeItem);

        $page->set('errors',$errors);
        $page->set('semanticJs',$semanticJs);
        $page->set('semanticCss',$semanticCss);

        $page->render();
    }

    /**
     * Prepare groups for render
     */
    private function prepareGroups(): array{
        $groups = [];

        foreach ($this->groups as $groupname => $group) {
            $groups[] = [
                "name" => $groupname,
                "active" => $this->groupActive($group),
                "items" => $group
            ];
        }

        return $groups;
    }

    //Check if any pane in a group is active, to expand the group
    private function groupActive(array $group): bool{
        $currentPane = $this->tabula->registry->getRequest()->get("pane");
        if ($currentPane === null) return false;
        foreach ($group as $pane){
            if ($currentPane === $pane->getSlug()){
                $this->activePane = $pane;
                return true;
            }
        }
        return false;
    }

    /**
     * Return active pane, if there is one
     */
    private function activeItem(){
        $currentPane = $this->tabula->registry->getRequest()->get("pane");
        if ($this->activePane instanceof AdminPane){
            return $this->activePane;
        }
        foreach ($this->panes as $pane){
            if ($currentPane === $pane->getSlug()) return $pane;
        }
    }
}