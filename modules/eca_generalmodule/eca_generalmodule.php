<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Eca_GeneralModule extends Module
{
    public function __construct()
    {
        $this->name = 'eca_generalmodule';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'esa.ir/shayanrezayi';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ECA General Module');
        $this->description = $this->l('A general module for various functionalities.');
    }

    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }
}