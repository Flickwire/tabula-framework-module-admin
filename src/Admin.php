<?php 
namespace Tabula\Modules\Admin;

use Tabula\Tabula;
use Tabula\Module;
use Tabula\Router;
use Tabula\Router\Route;

class Admin implements Module {
    
    private $panes = [];
    private $tabula;

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

    public function registerPane(AdminPane $pane){
        $this->panes[] = $pane;
    }

    public function render() {
        $currentPane = $this->tabula->registry->getRequest()->get("pane");
        $outMarkup = file_get_contents(__DIR__.DS."admin.html");
        $adminUrl = $this->tabula->registry->getUriBase() . "admin";
        foreach ($this->panes as $pane) {
            $class = ($currentPane === $pane->getSlug()) ? ' active' : '';
            $paneUrl = ($currentPane === $pane->getSlug()) ? '#' : ($adminUrl . "?pane=" . $pane->getSlug());
            $outMarkup = str_replace("_{PANE_NAME}_",
            `
            <a class="item{$class}" href="{$paneUrl}">
                {$pane->getName()}
            </a>
            _{PANE_NAME}_
            `,
            $outMarkup);
            if ($currentPane === $pane->getSlug()){
                $outMarkup = str_replace("_{CURRENT_PANE}_",$pane->render($this->tabula),$outMarkup);
                $outMarkup = str_replace("_{CURRENT_NAME}_",$pane->getName(),$outMarkup);
            }
        }
        $outMarkup = str_replace("_{PANE_NAME}_",'No Admin Panes Loaded',$outMarkup);
        $outMarkup = str_replace("_{CURRENT_PANE}_",'No Pane Selected',$outMarkup);
        $outMarkup = str_replace("_{CURRENT_NAME}_",'Admin',$outMarkup);
        echo($outMarkup);
    }
}