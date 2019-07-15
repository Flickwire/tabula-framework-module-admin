<?php 
namespace Tabula\Modules\Admin;

use Tabula\Tabula;
use Tabula\Module;
use Tabula\Router;
use Tabula\Router\Route;

class Admin implements Module {
    
    private $panes = [];
    private $groups = [];
    private $tabula;
    private $renderedPane = false;

    public function upgrade(string $version): string{
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
            if (!$this->groupActive($group)){
                $class = " dropdown";
            }
            $outMarkup = str_replace("_{PANE_NAME}_","
            <div class=\"item{$class}\">
            {$groupName}
            <div class=\"menu\">
            ",$outMarkup);
            $outMarkup = str_replace("_{PANE_NAME}_",$this->renderMenu($group),$outMarkup);
            $outMarkup = str_replace("_{PANE_NAME}_","
            </div>
            </div>
            ",$outMarkup);
        }
        $outMarkup = str_replace("_{PANE_NAME}_",$this->renderMenu($this->panes),$outMarkup);
        $outMarkup = str_replace("_{PANE_NAME}_","
        <div class=\"link item active\" href=\"#\">
        <i class=\"info circle icon\"></i>
        No Admin Panes Loaded
        </div>
        ",$outMarkup);
        $outMarkup = str_replace("_{CURRENT_PANE}_",'No Pane Selected',$outMarkup);
        $outMarkup = str_replace("_{CURRENT_NAME}_",'Admin',$outMarkup);
        $outMarkup = str_replace("_{SEMANTIC_PATH}_",$this->tabula->registry->getUriBase().'/vendor/semantic/ui/dist/',$outMarkup);
        echo($outMarkup);
    }

    private function renderMenu(array $items): string{
        $outMarkup = "_{PANE_NAME}_";
        $currentPane = $this->tabula->registry->getRequest()->get("pane");
        $adminUrl = $this->tabula->registry->getUriBase() . "admin";
        foreach ($items as $pane) {
            $class = ($currentPane === $pane->getSlug()) ? ' active' : '';
            $paneUrl = ($currentPane === $pane->getSlug()) ? '#' : ($adminUrl . "?pane=" . $pane->getSlug());
            if ($paneUrl === '#'){
                $outMarkup = str_replace("_{PANE_NAME}_",
                "
                <div class=\"item{$class}\">
                    {$pane->getName()}
                </div>
                _{PANE_NAME}_
                ",
                $outMarkup);
            } else {
                $outMarkup = str_replace("_{PANE_NAME}_",
                "
                <a class=\"item{$class}\" href=\"{$paneUrl}\">
                    {$pane->getName()}
                </a>
                _{PANE_NAME}_
                ",
                $outMarkup);
            }
            if ($currentPane === $pane->getSlug() && !$this->renderedPane){
                $outMarkup = str_replace("_{CURRENT_PANE}_",$pane->render($this->tabula),$outMarkup);
                $outMarkup = str_replace("_{CURRENT_NAME}_",$pane->getName(),$outMarkup);
                $this->renderedPane = true;
            }
        }
        return $outMarkup;
    }
}