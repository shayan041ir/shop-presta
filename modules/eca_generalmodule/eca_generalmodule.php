<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DataColumn;

class Eca_GeneralModule extends Module
{
    public function __construct()
    {
        $this->name = 'eca_generalmodule';
        $this->tab = 'administration';
        $this->version = '1.2.0';
        $this->author = 'esa.ir/shayanrezayi';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ECA General Module');
        $this->description = $this->l('Expiry date column & action in grid + inline date field with PersianDatepicker.');
    }

    /**
     * Register needed hooks and create DB column if missing.
     */
    public function install()
    {
        return parent::install()
            // GRID: column + row action + data formatting
            && $this->registerHook('actionProductGridDefinitionModifier')
            && $this->registerHook('actionProductGridQueryBuilderModifier')
            && $this->registerHook('actionProductGridDataModifier')

            // PRODUCT FORM (legacy hook area - new product page V2 renders it in Step 1, left column, bottom)
            && $this->registerHook('displayAdminProductsMainStepRightColumnBottom')

            // ASSETS + SAVE
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('actionProductSave')

            // DB migration
            && $this->installDb();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Ensure ps_product.expiry_date exists (MySQL-safe for older versions).
     */
    private function installDb()
    {
        $exists = Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'product` LIKE "expiry_date"'
        );

        if (!$exists) {
            return Db::getInstance()->execute(
                'ALTER TABLE `' . _DB_PREFIX_ . 'product` ADD COLUMN `expiry_date` DATE NULL DEFAULT NULL'
            );
        }

        return true;
    }

    /**
     * Inject our mini-card under the left column bottom of Main Step (Product Page V2).
     * Renders an input (Jalali UI) + hidden (Gregorian) that submits with the product form.
     */
    public function hookDisplayAdminProductsMainStepRightColumnBottom($params)
    {
        $idProduct = (int)($params['id_product'] ?? 0);
        $expiry = '';

        if ($idProduct) {
            $expiry = (string) Db::getInstance()->getValue(
                'SELECT `expiry_date` FROM `' . _DB_PREFIX_ . 'product` WHERE id_product=' . (int) $idProduct
            );
        }

        $this->context->smarty->assign([
            'expiryDate' => $expiry,
            'productId'  => $idProduct,
            'module_dir' => $this->_path,
        ]);

        // IMPORTANT: Make sure this file exists and is a Smarty TPL (not Twig)
        return $this->display(__FILE__, 'views/templates/hook/displayAdminProductsExtra.tpl');
    }

    /**
     * Load assets only on AdminProducts pages (BO).
     */
    public function hookDisplayBackOfficeHeader($params)
    {
        $controller = $this->context->controller ? $this->context->controller->controller_name : '';
        if ($controller !== 'AdminProducts') {
            return;
        }

        $this->context->controller->addCSS(
            $this->_path . 'views/lib/persianDatepicker/css/persianDatepicker-default.css'
        );
        $this->context->controller->addJS(
            $this->_path . 'views/lib/persianDatepicker/js/persianDatepicker.min.js'
        );
        $this->context->controller->addJS($this->_path . 'views/js/expiry.js');
    }

    /* ===================== GRID SECTION ===================== */

    /**
     * Add column "expiry_date" and a row action "Edit Expiry" that links to product edit page.
     */
    public function hookActionProductGridDefinitionModifier(array $params)
    {
        $definition = $params['definition'];

        // 1) Add expiry_date column after "active"
        if (!$definition->getColumns()->contains('expiry_date')) {
            $definition->getColumns()->addAfter(
                'active',
                (new DataColumn('expiry_date'))
                    ->setName($this->l('Expiry Date'))
                    ->setOptions([
                        'field' => 'expiry_date',
                        'sortable' => true,
                    ])
            );
        }

        // 2) Append our row action to the existing actions collection
        $columns = $definition->getColumns();
        $actionsCol = method_exists($columns, 'get') ? $columns->get('actions') : $columns->getColumn('actions');
        if (!$actionsCol) {
            return;
        }

        $opts = $actionsCol->getOptions();
        $rowActions = $opts['actions'] ?? new RowActionCollection();

        $rowActions->add(
            (new LinkRowAction('edit_expiry'))
                ->setName($this->l('Edit Expiry'))
                ->setIcon('event')
                ->setOptions([
                    'route' => 'admin_products_edit',
                    'route_param_name'  => 'productId',
                    'route_param_field' => 'id_product',
                    // Fragment is optional; helps scroll to pricing tab if your theme supports it:
                    'route_fragment'    => 'tab-product_pricing-tab',
                    'clickable_row'     => false,
                ])
        );

        $opts['actions'] = $rowActions;
        $actionsCol->setOptions($opts);
    }

    /**
     * Ensure expiry_date is selected in product grid query.
     */
    public function hookActionProductGridQueryBuilderModifier(array $params)
    {
        // In most PrestaShop versions alias "p" is used for product table
        $qb = $params['query_builder'];
        $qb->addSelect('p.expiry_date');
    }

    /**
     * Format grid data (e.g., convert Gregorian to Jalali for display).
     * Fallback to Gregorian if Intl/Persian calendar is not available.
     */
    public function hookActionProductGridDataModifier(array $params)
    {
        $data = $params['data'];
        $records = $data->getRecords();

        foreach ($records as &$record) {
            if (!empty($record['expiry_date'])) {
                $record['expiry_date'] = $this->toJalali($record['expiry_date']);
            }
        }

        $data->setRecords($records);
    }

    /**
     * Helper: Gregorian (YYYY-mm-dd) => Jalali (yyyy/MM/dd) when Intl is available, else fallback.
     */
    private function toJalali($gregorian)
    {
        if (!$gregorian) {
            return '';
        }

        if (class_exists('IntlDateFormatter')) {
            $ts = strtotime($gregorian . ' 00:00:00');
            $fmt = new \IntlDateFormatter(
                'fa_IR@calendar=persian',
                \IntlDateFormatter::NONE,
                \IntlDateFormatter::NONE,
                date_default_timezone_get(),
                \IntlDateFormatter::TRADITIONAL,
                'yyyy/MM/dd'
            );

            if ($ts && $fmt) {
                $out = $fmt->format($ts);
                if ($out !== false) {
                    return $out;
                }
            }
        }

        return $gregorian; // fallback to original Gregorian
    }

    /* ===================== SAVE SECTION ===================== */

    /**
     * Persist expiry_date from hidden input (Gregorian YYYY-mm-dd) when product is saved.
     */
    public function hookActionProductSave($params)
    {
        $idProduct = 0;

        if (!empty($params['id_product'])) {
            $idProduct = (int) $params['id_product'];
        } elseif (!empty($params['product']) && Validate::isLoadedObject($params['product'])) {
            $idProduct = (int) $params['product']->id;
        }

        if (!$idProduct) {
            return;
        }

        $date = trim((string) Tools::getValue('expiry_date'));

        if ($date === '') {
            Db::getInstance()->update(
                'product',
                ['expiry_date' => null],
                'id_product=' . (int) $idProduct
            );
            return;
        }

        // Accept single-digit month/day and normalize to YYYY-mm-dd
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $date, $m)) {
            $date = sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);

            Db::getInstance()->update(
                'product',
                ['expiry_date' => pSQL($date)],
                'id_product=' . (int) $idProduct
            );
        }
        // else: invalid format => silently ignore (no save)
    }
}
