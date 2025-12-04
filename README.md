# shop-presta
# 2 madules

#1
# ECA General Module — Expiry Date for Products (PrestaShop 8.x)

Add an **expiry date** to products:

* A new **`expiry_date`** column in the products grid + a **row action** “Edit Expiry” that deep-links to the product edit page.
* An inline **Jalali (Persian) date picker** in the **Product Page V2** (Main Step → Right column → bottom), with a hidden **Gregorian** value that gets saved into `ps_product.expiry_date`.

---

## File structure

```
modules/eca_generalmodule/
├─ eca_generalmodule.php                 # Main module class (hooks, DB install, logic)
└─ views/
   ├─ js/
   │  └─ expiry.js                       # Datepicker init + synchronization (Jalali <-> Gregorian)
   ├─ lib/
   │  └─ persianDatepicker/
   │     ├─ css/
   │     │  ├─ persianDatepicker-default.css
   │     │  └─ themes/
   │     │     └─ persianDatepicker-dark.css
   │     └─ js/
   │        └─ persianDatepicker.min.js  # Behzadi PersianDatepicker
   └─ templates/
      └─ hook/
         └─ displayAdminProductsExtra.tpl # Smarty template for the inline card (right column)
```

> **Important:** the module folder name **must** be exactly `eca_generalmodule` (case-sensitive), matching `$this->name`.

---

## What it does

1. **Database**

* On install, creates column `ps_product.expiry_date` (`DATE NULL`) if it doesn’t exist.

2. **Back-office assets**

* Loads **PersianDatepicker** CSS/JS and `expiry.js` **only** on `AdminProducts` pages.

3. **Product grid (Catalog → Products)**

* Adds a new **data column** `expiry_date` (displayed Jalali if `Intl` Persian calendar is available, otherwise Gregorian).
* Appends a **row action** “Edit Expiry” linking to the product edit page (with an optional fragment to the Pricing tab).

4. **Product edit page (Product Page V2)**

* Renders a card in **Main Step → Right column → Bottom** (`displayAdminProductsMainStepRightColumnBottom`):

  * **Visible** input (`#pspe_expiry_display`) uses PersianDatepicker (Jalali UI).
  * **Hidden** input (`name="expiry_date"`, `#pspe_expiry_date`) holds normalized Gregorian `YYYY-MM-DD` used for saving.

5. **Saving**

* On product save (`actionProductSave`), if `expiry_date` is present:

  * Empty value → saves `NULL`
  * Otherwise validates and normalizes to `YYYY-MM-DD`, then updates `ps_product.expiry_date`.

---

## Hooks used

* **Grid**

  * `actionProductGridDefinitionModifier` – adds the `expiry_date` column and the “Edit Expiry” row action.
  * `actionProductGridQueryBuilderModifier` – ensures `p.expiry_date` is selected.
  * `actionProductGridDataModifier` – optional display formatting (Gregorian → Jalali if possible).

* **Product edit page**

  * `displayAdminProductsMainStepRightColumnBottom` – renders the inline card (form fields) in the right column (bottom) of Main Step.

* **Assets & save**

  * `displayBackOfficeHeader` – loads CSS/JS (datepicker + init script).
  * `actionProductSave` – persists the normalized date to DB.

> If you prefer the **left** column instead, you can additionally/alternatively register and implement `displayAdminProductsMainStepLeftColumnBottom` and reuse the same rendering code.

---

## How it works (data flow)

1. **Load page** → `displayBackOfficeHeader` enqueues assets → template `displayAdminProductsExtra.tpl` renders the card → `expiry.js` initializes the datepicker on `#pspe_expiry_display`.
2. **User picks a date (Jalali)** → PersianDatepicker writes a **Gregorian** mirror to the display input’s `data-gdate` attribute → `expiry.js` copies/normalizes it into hidden `#pspe_expiry_date` as `YYYY-MM-DD`.
3. **User clicks Save** → PrestaShop submits `expiry_date` with the rest of the form → `actionProductSave` validates and writes to `ps_product.expiry_date`.
4. **Grid** → query hook selects `p.expiry_date`, data hook formats for display (Jalali if available).

---

## Installation

1. Copy the module to:

   ```
   modules/eca_generalmodule/
   ```
2. In Back Office → **Modules → Module Manager**, install **ECA General Module**.
3. Clear cache:

   * BO → Advanced Parameters → Performance → **Clear cache**
     (or manually delete `var/cache/dev` and `var/cache/prod`).
4. Open **Catalog → Products** to see the **Expiry Date** column and **Edit Expiry** action.
5. Open a product (Product Page V2) and scroll to **Main Step → Right column → bottom** to see the card.

> **Product Page V2 note:** in PS 8.1+, ensure the **new product page** is enabled if you expect this hook area. (Advanced Parameters → Experimental features, depending on your build.)

---

## Configuration / customization

* **Where the card appears**

  * Right column (default): `displayAdminProductsMainStepRightColumnBottom`
  * Left column: `displayAdminProductsMainStepLeftColumnBottom`
* **Grid action target tab**
  Change `route_fragment` if your theme uses different fragment IDs (e.g., `tab-product_details-tab`).
* **Jalali formatting in grid**
  `toJalali()` uses `IntlDateFormatter` with `fa_IR@calendar=persian`. If `Intl` is missing or fails, the grid falls back to plain Gregorian.

---

## Troubleshooting

* **Template not found / 500**
  Ensure the file exists:
  `modules/eca_generalmodule/views/templates/hook/displayAdminProductsExtra.tpl`
  It **must be a Smarty `.tpl`**, not Twig. Clear cache.

* **No datepicker UI**
  Check browser console for errors and confirm assets loaded:

  * `persianDatepicker-default.css`
  * `persianDatepicker.min.js`
  * `expiry.js`
    Also ensure jQuery is present on the BO page (Admin uses it by default).

* **Saved value looks wrong (e.g., far future year)**
  You’re likely saving Jalali text directly. The hidden field **must** contain normalized Gregorian `YYYY-MM-DD`. `expiry.js` handles this; make sure it runs and that `#pspe_expiry_display` and `#pspe_expiry_date` IDs match your template.

* **Grid column empty**
  Confirm the `actionProductGridQueryBuilderModifier` executed and added `p.expiry_date` to the SELECT; then clear cache.

* **Multistore**
  This writes to `ps_product.expiry_date` (base table). If you need shop-scoped dates, move the column to a shop-specific table and adjust hooks accordingly.

---

## Security & performance notes

* Saving uses `Db::getInstance()->update()` with `pSQL` for the date string.
* The installer checks column existence via `SHOW COLUMNS` before running `ALTER TABLE`.
* Assets are conditionally enqueued **only** for `AdminProducts` to keep BO light elsewhere.

---

## Uninstall

Currently, uninstall does **not** drop the column (to preserve data). If you want a full revert, manually remove `expiry_date` from `ps_product` after uninstalling the module.

---

## Credits

* PersianDatepicker by **Behzadi** (bundled locally under `views/lib/persianDatepicker/`).

---

## Quick test checklist

* [ ] Install module, clear cache.
* [ ] Open **Products grid** → see **Expiry Date** column and **Edit Expiry** action.
* [ ] Open a product → Right column → bottom, see the **expiry card**.
* [ ] Pick a date (Jalali) → Save → DB `ps_product.expiry_date` updates (Gregorian).
* [ ] Grid shows formatted date (Jalali if `Intl` available).
* [ ] Edit again → previous value is prefilled.


* License
* This module is released under the MIT License. You are free to use, modify, and distribute it as per the license terms.
* Author

* Name: Shayan Rezayi
* Website: esa.ir
* Module Version: 1.2.0







#2

# PsSqlExcelExport — README (Copy‑paste friendly)

The **PsSqlExcelExport** module adds a per-row **“Export to Excel”** action to the SQL Manager grid in PrestaShop and generates an `.xlsx` file from the query result using PhpSpreadsheet.

---

## Project Structure

```
modules/ps_sql_excel_export/
├─ composer.json
├─ composer.lock
├─ config/
│  ├─ routes.yml
│  └─ services.yml
├─ Controller/
│  └─ Admin/
│     └─ AdminPsSqlExcelExportController.php
├─ ps_sql_excel_export.php
├─ src/
│  └─ Exporter/
│     └─ SqlRequestExcelExporter.php
├─ vendor/
└─ views/
   └─ templates/
```

> Notes
> • This module integrates via a **grid row action**; no Twig template changes are required.
> • Paths are case-sensitive on Linux. Keep the capitalized `Controller/Admin` path as shown.

---

## Requirements

* PrestaShop **1.7+** (tested on **8.2.x**)
* PHP **7.2+** (match your PrestaShop version requirements)
* Composer

---

## Installation

1. Copy the module into `modules/ps_sql_excel_export`.

2. From the module directory, install dependencies:

```bash
author$ composer install
```

3. Ensure the module’s autoloader is included. At the top of `ps_sql_excel_export.php`:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

4. Register services and routes.

**config/services.yml**

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: true

  PsSqlExcelExport\Controller\:
    resource: '%kernel.project_dir%/modules/ps_sql_excel_export/Controller/'
    public: true
    tags: ['controller.service_arguments']
  PsSqlExcelExport\Controller\Admin\:
    resource: '%kernel.project_dir%/modules/ps_sql_excel_export/Controller/Admin/'
    public: true
    tags: ['controller.service_arguments']
  PsSqlExcelExport\Controller\Admin\AdminPsSqlExcelExportController:
    class: PsSqlExcelExport\Controller\Admin\AdminPsSqlExcelExportController
    arguments:
      $queryBus: '@prestashop.core.query_bus'
    public: true
    autowire: true
    autoconfigure: true
    tags: ['controller.service_arguments']
  
```

**config/routes.yml**

```yaml
admin_ps_sql_excel_export:
  path: /modules/excel_export/{id_sql_request}
  methods: [GET]
  defaults:
    _controller: 'PsSqlExcelExport\Controller\Admin\AdminPsSqlExcelExportController::export'
    _legacy_controller: 'AdminPsSqlExcelExport'
  requirements:
    id_sql_request: '\\d+'
```

5. Clear cache **from the project root**:

```bash
php bin/console cache:clear --no-warmup
rm -rf var/cache/*
```

---

## Controller (example)

**Controller/Admin/AdminPsSqlExcelExportController.php**

```php
<?php
namespace PsSqlExcelExport\Controller\Admin;

use PrestaShop\PrestaShop\Core\Domain\SqlManagement\Query\GetSqlRequestExecutionResult;
use PrestaShop\PrestaShop\Core\Domain\SqlManagement\ValueObject\SqlRequestId;
use PsSqlExcelExport\Exporter\SqlRequestExcelExporter;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminPsSqlExcelExportController extends FrameworkBundleAdminController
{
    /** @var object */
    private $queryBus;

    public function __construct($queryBus)
    {
        $this->queryBus = $queryBus; // @prestashop.core.query_bus
    }

    /**
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))", redirectRoute="admin_sql_requests_index")
     */
    public function export(Request $request, TranslatorInterface $translator)
    {
        $sqlRequestId = (int) $request->get('id_sql_request');
        if ($sqlRequestId <= 0) {
            $this->addFlash('error', $translator->trans('Missing SQL request ID.', [], 'Admin.Notifications.Error'));
            return $this->redirectToRoute('admin_sql_requests_index');
        }

        try {
            $result = $this->queryBus->handle(new GetSqlRequestExecutionResult($sqlRequestId));

            $exporter = new SqlRequestExcelExporter();
            $file = $exporter->exportToFile(new SqlRequestId($sqlRequestId), $result);

            $response = new BinaryFileResponse($file->getPathname());
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getFilename());

            register_shutdown_function(static function () use ($file) {
                @unlink($file->getPathname());
            });

            return $response;
        } catch (\Exception $e) {
            $this->addFlash('error', $translator->trans(
                'Error exporting to Excel: %message%',
                ['%message%' => $e->getMessage()],
                'Admin.Notifications.Error'
            ));
            return $this->redirectToRoute('admin_sql_requests_index');
        }
    }
}
```

---

## Grid Integration (no Twig)

Add a **row action** to the SQL Manager grid definition so each row gets an **“Export to Excel”** action.

Edit:

* **Most installs**: `src/Core/Grid/Definition/Factory/RequestSqlGridDefinitionFactory.php`
* Some installs use lowercase paths: `src/core/grid/definition/factory/RequestSqlGridDefinitionFactory.php`

Add the `LinkRowAction` to the row actions collection (where other row actions are added):

```php
->add(
    (new LinkRowAction('excel_export'))
        ->setName('خروجی اکسل') // or Export to Excel
        ->setIcon('cloud_download')
        ->setOptions([
            'route' => 'admin_ps_sql_excel_export',
            'route_param_name' => 'id_sql_request',
            'route_param_field' => 'id_request_sql',
        ])
)
```

**Important:**

* `route_param_field` must match the grid’s primary key column (usually `id_request_sql` in SQL Manager).
* Clear cache after editing core files.

---

## Usage

1. In Back Office, go to **Advanced Parameters → Database → SQL Manager**.
2. Save your SQL query.
3. In the listing grid, click the **Export to Excel** row action for the desired query.
4. The `.xlsx` file is generated and downloaded.

---

## Troubleshooting

* **Controller not callable / not found**
  Ensure PSR-4 matches the physical paths and class namespace:

  * `composer.json` must map:

    ```json
    {
      "autoload": {
        "psr-4": {
          "PsSqlExcelExport\\": "src/",
          "PsSqlExcelExport\\Controller\\Admin\\": "Controller/Admin/"
        }
      }, 
    }
    ```
  * Then run: `composer dump-autoload -o`

* **File locator / services path errors**
  Check `config/services.yml` `resource` path matches your real module path and casing (Linux is case-sensitive).

* **`translator` service not found in controller**
  Don’t call `$this->get('translator')`. Inject `TranslatorInterface $translator` into the action method.

* **`prestashop.core.query_bus` service not found**
  Inject it via constructor and wire it explicitly in `services.yml`:

  ```yaml
  PsSqlExcelExport\Controller\Admin\AdminPsSqlExcelExportController:
    arguments:
      $queryBus: '@prestashop.core.query_bus'
  ```

* **`bin/console` not found**
  Run CLI commands from the **project root**, not the module folder:

  ```bash
  php bin/console cache:clear --no-warmup
  ```

---

## Notes

* Editing core files may be overwritten by future upgrades. For long-term stability, prefer extending/overriding via services or decorators where possible.
* This README assumes the controller method name is `export` and the route is `admin_ps_sql_excel_export`.

---

## License

Internal use / project-specific (update as appropriate).
