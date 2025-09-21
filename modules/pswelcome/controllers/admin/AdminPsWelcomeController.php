<?php

class AdminPsWelcomeController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {

        if (Tools::isSubmit('submit_pswelcome')) {
            $msg = Tools::getValue('PSWELCOME_MSG');
            Configuration::updateValue('PSWELCOME_MSG', $msg);
            $this->confirmations[] = $this->l('Settings updated');
        }

        $this->context->smarty->assign([
            'welcome_msg' => Configuration::get('PSWELCOME_MSG')
        ]);

        $this->content .= $this->module->display(
            _PS_MODULE_DIR_ . $this->module->name,
            'views/templates/admin/configure.tpl'
        );
        parent::initContent();
        
    }
}

