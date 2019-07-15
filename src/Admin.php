<?php 
namespace Tabula\Modules\Admin;

use Tabula\Tabula;
use Tabula\Module;
use Tabula\Router;
use Tabula\Router\Route;
use Tabula\Database\Adapter\AbstractAdapter;

class Admin implements Module {
    
    private $panes = [];
    private $groups = [];
    private $tabula;
    private $renderedPane = false;
    private $hasPanes = false;

    public function upgrade(string $version, AbstractAdapter $db): string{
        return '1.0';
    }

    public function registerRoutes(Router $router): void{
        $router->register(new Route("/admin",$this,"render"));
    }

    public function preInit(Tabula $tabula): void{
        $this->tabula = $tabula;
        $tabula->registry->setAdminPanel($this);
    }

    public function init(): void{
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
        $outMarkup = file_get_contents(__DIR__.DS."admin.html");
        foreach ($this->groups as $groupName => $group) {
            $class = "";
            $icon = "";
            $ui = "";
            if (!$this->groupActive($group)){
                $class = " dropdown";
                $icon = "<i class=\"dropdown icon\"></i>";
                $ui = "ui ";
            }
            $outMarkup = str_replace("_{PANE_NAME}_","
            <div class=\"{$ui}item{$class}\">
            {$groupName}
            {$icon}
            <div class=\"{$ui}menu\">
            _{PANE_NAME}_
            ",$outMarkup);
            $outMarkup = $this->renderMenu($outMarkup,$group);
            $outMarkup = str_replace("_{PANE_NAME}_","
            </div>
            </div>
            _{PANE_NAME}_
            ",$outMarkup);
        }
        $outMarkup = $this->renderMenu($outMarkup,$this->panes);
        if ($this->hasPanes){
            $outMarkup = str_replace("_{PANE_NAME}_","",$outMarkup);
        } else {
            $outMarkup = str_replace("_{PANE_NAME}_","
            <div class=\"ui link item active\" href=\"#\">
            <i class=\"info circle icon\"></i>
            No Admin Panes Loaded
            </div>
            ",$outMarkup);
        }
        $outMarkup = str_replace("_{CURRENT_PANE}_",'No Pane Selected',$outMarkup);
        $outMarkup = str_replace("_{CURRENT_NAME}_",'Admin',$outMarkup);
        $outMarkup = str_replace("_{SEMANTIC_PATH}_",$this->tabula->registry->getUriBase().'/vendor/semantic/ui/dist/',$outMarkup);
        echo($outMarkup);
    }

    private function renderMenu(string $outMarkup, array $items): string{
        $currentPane = $this->tabula->registry->getRequest()->get("pane");
        $adminUrl = $this->tabula->registry->getUriBase() . "admin";
        foreach ($items as $pane) {
            $this->hasPanes = true;
            $class = ($currentPane === $pane->getSlug()) ? ' active' : '';
            $paneUrl = ($currentPane === $pane->getSlug()) ? '#' : ($adminUrl . "?pane=" . $pane->getSlug());
            $icon = $pane->getIcon();
            if ($icon === null){
                $icon = "";
            } else {
                $icon = "<i class=\"{$icon} icon\"></i>";
            }
            if ($paneUrl === '#'){
                $outMarkup = str_replace("_{PANE_NAME}_",
                "
                <div class=\"item{$class}\">
                    {$icon}
                    {$pane->getName()}
                </div>
                _{PANE_NAME}_
                ",
                $outMarkup);
            } else {
                $outMarkup = str_replace("_{PANE_NAME}_",
                "
                <a class=\"item{$class}\" href=\"{$paneUrl}\">
                    {$icon}
                    {$pane->getName()}
                </a>
                _{PANE_NAME}_
                ",
                $outMarkup);
            }
            if ($currentPane === $pane->getSlug() && !$this->renderedPane){
                $render = $pane->render($this->tabula);
                $outMarkup = str_replace("_{CURRENT_PANE}_",$render,$outMarkup);
                $outMarkup = str_replace("_{CURRENT_NAME}_",$pane->getName(),$outMarkup);
                $this->renderedPane = true;
            }
        }
        return $outMarkup;
    }

    //Check if any pane in a group is active, to expand the group
    private function groupActive(array $group): bool{
        $active = false;
        $currentPane = $this->tabula->registry->getRequest()->get("pane");
        if ($currentPane === null) return false;
        foreach ($group as $pane){
            if ($currentPane === $pane->getSlug()) return true;
        }
        return false;
    }
}