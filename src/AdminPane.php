<?php 
namespace Tabula\Modules\Admin;

abstract class AdminPane {

    /**
     * Return the body HTML of your pane here
     * Will be directly echo'd so all processing must
     * finish before you return
     * 
     * Passed the tabula instance in case you need it
     */
    public abstract function render(\Tabula\Tabula $tabula): string;

    /**
     * Return the name of your admin pane,
     * for the menu
     */
    public abstract function getName(): string;

    /**
     * Return a url-friendly slug for your pane
     */
    public abstract function getSlug(): string;

    /**
     * Return an icon for the menu if you want to
     */
    public abstract function getIcon(): ?string;
}