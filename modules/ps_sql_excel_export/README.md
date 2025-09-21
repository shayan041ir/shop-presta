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
