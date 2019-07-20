<?php 
namespace Tabula\Modules\Admin;

abstract class AdminPane {
    public $tabula;

    public function __construct($tabula){
        $this->tabula = $tabula;
    }

    /**
     * Return the body HTML of your pane here
     * Will be thrown into a raw tag, so please escape
     * your shit with the twig renderer first <3
     */
    abstract public function render(): string;

    /**
     * Return the name of your admin pane,
     * for the menu
     */
    abstract public function getName(): string;

    /**
     * Return a url-friendly slug for your pane
     */
    abstract public function getSlug(): string;

    /**
     * Return an icon for the menu if you want to
     */
    abstract public function getIcon(): ?string;

    /**
     * Check if this pane is active
     */
    public function isActive(): bool{
        return $this->getSlug() === $this->tabula->registry->getRequest()->get("pane");
    }
}