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
        return parent::install()
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('actionProductSave')
            && $this->installDb();
    }
    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
    * افزودن ستون expiry_date به ps_product
    */
    private function installDb()
    {
        $sql = 'ALTER TABLE `'._DB_PREFIX_.'product`
        ADD COLUMN IF NOT EXISTS `expiry_date` DATE NULL DEFAULT NULL';
        $exists = Db::getInstance()->executeS('SHOW COLUMNS FROM `'._DB_PREFIX_.'product` LIKE "expiry_date"');
        if (!$exists) {
            return Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'product` ADD COLUMN `expiry_date` DATE NULL DEFAULT NULL');
        }
        return true;
    }
    /**
    * فقط در صفحهٔ ویرایش/ایجاد محصول، منابع PersianDatepicker و اسکریپت اینیت را بارگذاری می‌کنیم.
    */
    public function hookDisplayBackOfficeHeader($params)
    {
        $controllerName = $this->context->controller ? $this->context->controller->controller_name : '';
        if ($controllerName !== 'AdminProducts') {
            return; 
        }


        $this->context->controller->addCSS($this->_path.'views/lib/persianDatepicker/css/persianDatepicker-default.css');
        $this->context->controller->addJS($this->_path.'views/lib/persianDatepicker/js/persianDatepicker.min.js');
        $this->context->controller->addJS($this->_path.'views/js/expiry.js');
    }

    /**
    * نمایش پنل اضافی در صفحهٔ محصول برای گرفتن تاریخ انقضا (نمایش جلالی، ذخیرهٔ میلادی)
    */
    public function hookDisplayAdminProductsExtra($params)
    {
        $idProduct = isset($params['id_product']) ? (int)$params['id_product'] : 0;
        $expiry = '';
        if ($idProduct) {
            $expiry = Db::getInstance()->getValue('SELECT `expiry_date` FROM `'._DB_PREFIX_.'product` WHERE id_product='.(int)$idProduct);
        }
        $this->context->smarty->assign([
            'pspe_expiry_date' => $expiry, // date in Y-m-d format
            'module_dir' => $this->_path,
        ]);
            return $this->fetch('module:'.$this->name.'/views/templates/hook/displayAdminProductsExtra.tpl');
    }

    /**
    * ذخیرهٔ مقدار دریافتی هنگام ذخیرهٔ محصول
    */
    public function hookActionProductSave($params)
    {
        // get the date from the request
        $date = Tools::getValue('pspe_expiry_date');
        $idProduct = 0;


        if (isset($params['id_product']) && (int)$params['id_product'] > 0) {
            $idProduct = (int)$params['id_product'];
        } elseif (isset($params['product']) && Validate::isLoadedObject($params['product'])) {
            $idProduct = (int)$params['product']->id;
        }
        if (!$idProduct) { return;}

        $date = trim((string)$date);
        
        if ($date === '' || $date === null) {
            $sql = 'UPDATE `'._DB_PREFIX_.'product` SET `expiry_date`=NULL WHERE id_product='.(int)$idProduct;
            Db::getInstance()->execute($sql);
            return;
        }

        // به‌جای الگوی سخت‌گیر:
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $date, $m)) {
            $date = sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        } else {
            return; // فرمت نامعتبر
        }



        Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'product` SET `expiry_date`=\''.pSQL($date).'\' WHERE id_product='.(int)$idProduct);
    }
}

