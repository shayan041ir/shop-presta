{* modules/eca_generalmodule/views/templates/hook/expiry_field.tpl *}
<style>
  .expiry-card {
    max-width: 350px; /* عرض کارت کوچکتر */
    border-radius: 8px; /* گوشه‌های گرد */
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* سایه ملایم */
    margin-bottom: 1rem;
  }
  .expiry-card .card-header {
    padding: 0.5rem 1rem; /* کاهش پدینگ هدر */
    background-color: #f8f9fa; /* رنگ پس‌زمینه ملایم */
    border-bottom: 1px solid #e9ecef;
  }
  .expiry-card .card-header-title {
    font-size: 1.1rem; /* اندازه فونت کوچکتر */
    font-weight: 500;
    color: #333;
    margin: 0;
  }
  .expiry-card .card-body {
    padding: 1rem; /* کاهش پدینگ بدنه */
  }
  .expiry-card .form-group {
    margin-bottom: 0.75rem; /* کاهش فاصله فرم */
  }
  .expiry-card .form-control {
    font-size: 0.9rem; /* اندازه فونت اینپوت کوچکتر */
    padding: 0.4rem 0.75rem; /* پدینگ کمتر برای اینپوت */
    border-radius: 6px; /* گوشه‌های گردتر */
    border: 1px solid #ced4da;
    transition: border-color 0.2s ease-in-out;
  }
  .expiry-card .form-control:focus {
    border-color: #007bff; /* رنگ حاشیه هنگام فوکوس */
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  }
  .expiry-card .form-text {
    font-size: 0.8rem; /* متن راهنما کوچکتر */
    color: #6c757d;
    margin-top: 0.25rem;
  }
</style>

<div class="card expiry-card">
  <div class="card-header">
    <h3 class="card-header-title">{l s='تاریخ انقضا محصول' mod='eca_generalmodule'}</h3>
  </div>
  <div class="card-body">
    <div class="form-group">
      <label for="pspe_expiry_display">{l s='انتخاب تاریخ انقضا' mod='eca_generalmodule'}</label>
      <input id="pspe_expiry_display" type="text" class="form-control" autocomplete="off" />
      <small class="form-text text-muted">
        {l s='فرمت تاریخ (YYYY-MM-DD)' mod='eca_generalmodule'}
      </small>
    </div>

    <input type="hidden" id="pspe_expiry_date" name="expiry_date"
           value="{$expiryDate|escape:'html':'UTF-8'}" />
  </div>
</div>

<link rel="stylesheet" href="{$module_dir}views/lib/persianDatepicker/css/persianDatepicker-default.css">
<script src="{$module_dir}views/lib/persianDatepicker/js/persianDatepicker.min.js"></script>
<script src="{$module_dir}views/js/expiry.js"></script>
<script>
  if (window.PSPE && typeof window.PSPE.initExpiryField === 'function') {
    PSPE.initExpiryField(
      document.getElementById('pspe_expiry_display'),
      document.getElementById('pspe_expiry_date')
    );
  }
</script>