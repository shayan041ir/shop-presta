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