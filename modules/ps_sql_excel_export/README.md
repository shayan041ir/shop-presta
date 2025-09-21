# PsSqlExcelExport Module

The **PsSqlExcelExport** module is designed for the PrestaShop platform, enabling data extraction from the database into Excel files. It allows users to execute SQL queries and export the results as Excel files.

## Project Structure

```
ps_sql_excel_export/
│
├── composer.json                # Autoload settings and dependencies
├── composer.lock                # Locked dependency versions
├── config/                      # Module configuration directory
|   └──routes.yml                       
├── controller/                 # Controllers directory
│   └── admin/
│       └── AdminPsSqlExcelExportController.php # Main controller for Excel export
├── ps_sql_excel_export.php      # Main module file
├── src/                         # Source code directory
│   └── Exporter/
│       └── SqlRequestExcelExporter.php # Class for processing and generating Excel files
├── translations/                # Translation files directory
├── vendor/                      # Composer libraries (e.g., PhpSpreadsheet)
└── views/                       # Templates directory
    └── templates/
        └── admin/
        └── hook/
            └── button_excel.tpl # Template for the Export button
```

## Features

* Execute SQL queries and export results as Excel files
* Persian language support
* Simple user interface with an Export button in the admin panel
* Utilizes the PhpSpreadsheet library for Excel file generation
* Modular structure compliant with PrestaShop standards

## Prerequisites

* PrestaShop version 1.7 or higher
* PHP version 7.2 or higher
* Composer for dependency installation
* PhpSpreadsheet library (automatically installed via Composer)

## Installation

1. Copy the module files to the `modules/ps_sql_excel_export` directory.
2. In the PrestaShop admin panel, navigate to the **Modules** section and install the **PsSqlExcelExport** module.
3. Install dependencies by running:

   ```bash
   composer install
   ```

## Usage

1. In the PrestaShop admin panel, go to the **SQL Manager** section.
2. Enter your SQL query.
3. Click the **Export** button to generate and download the Excel file.

## Template Integration

In addition to installing the module, you must also add a button integration snippet inside the PrestaShop admin grid template.
Edit the following file:

**`src/PrestaShopBundle/Resources/views/Admin/Common/Grid/Blocks/table.html.twig`**

Add this code block where you want the Export button to appear:

```twig
{# export excel btn #}
<div class="btn-group-action text-right">
  <div class="btn-group">
    {% if record.id_request_sql is defined and record.id_request_sql %}
      {{ renderhook('displayAdminSqlManagerButtons', { 'id_sql_request': record.id_request_sql }) }}
    {% endif %}
  </div>
</div>
{# export excel btn #}
```

This ensures that the **Export to Excel** button is only displayed when a valid SQL Request ID exists, and otherwise shows a warning message instead of breaking the page.

## Development Guide

For developers interested in customizing or extending the module, the key files are:

* **Main module file**: `ps_sql_excel_export.php` – Entry point and initial module setup
* **Main controller**: `controllers/admin/AdminPsSqlExcelExportController.php` – Handles server-side logic and user interface
* **Excel generation class**: `src/Exporter/SqlRequestExcelExporter.php` – Core logic for data processing and Excel file creation
* **Button template**: `views/templates/admin/hook/button_excel.tpl` – Renders the Export button in the admin panel

To begin development, it’s recommended to start by reviewing the `ps_sql_excel_export.php` file to understand the module’s overall structure.

## Support

For issues or guidance, contact the development team or refer to the official PrestaShop documentation.
