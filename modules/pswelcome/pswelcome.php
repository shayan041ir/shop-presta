<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Pswelcome extends Module
{
    public function __construct()
    {
        $this->name = 'pswelcome';
        $this->tab = 'Other';
        $this->version = '1.0.0';
        $this->author = 'web7';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Welcome Message');
        $this->description = $this->l('Displays a customizable welcome message for logged-in users.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayCustomerAccount')
            && Configuration::updateValue('PSWELCOME_MSG', 'خوش آمدید!');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('PSWELCOME_MSG');
    }

    public function hookDisplayCustomerAccount($params)
    {
        if ($this->context->customer->isLogged()) {
            $this->context->smarty->assign([
                'welcome_msg' => Configuration::get('PSWELCOME_MSG'),
                'customer_name' => $this->context->customer->firstname,
            ]);
            return $this->display(__FILE__, 'views/templates/hook/displayCustomerAccount.tpl');
        }
    }

    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminPsWelcome')
        );
    }
}
