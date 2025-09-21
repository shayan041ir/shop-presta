<?php
require_once __DIR__ . '/vendor/autoload.php';
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_Sql_Excel_Export extends Module
{
    public function __construct()
    {
        $this->name = 'ps_sql_excel_export';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'esa.ir/shayanrezayi';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('SQL Manager Excel Export');
        $this->description = $this->l('Adds Excel export option to SQL Manager in PrestaShop 8.2.1.');
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