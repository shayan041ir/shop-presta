{* کارت ساده داخل صفحه محصول *}
<div class="card" id="pspe-expiry-card">
  <div class="card-header">
    <h3 class="card-header-title">تاریخ انقضا</h3>
  </div>

  <div class="card-body">
    <div class="form-group">
      <label for="pspe_expiry_display">تاریخ انقضا (نمایش جلالی)</label>
      <input type="text" class="form-control" id="pspe_expiry_display" autocomplete="off">
      <small class="form-text text-muted">مقدار در دیتابیس به‌صورت میلادی ذخیره می‌شود.</small>
    </div>

    {*
      مقدار میلادی جهت ارسال با فرم محصول.
      نام این فیلد در hookActionProductSave خوانده می‌شود.
    *}
    <input type="hidden" name="pspe_expiry_date" id="pspe_expiry_date" value="{$pspe_expiry_date|escape:'htmlall':'UTF-8'}" />

    {if $pspe_expiry_date}
      <div class="alert alert-info mt-2">
        مقدار فعلی (میلادی): <code>{$pspe_expiry_date|escape:'htmlall':'UTF-8'}</code>
      </div>
    {/if}
  </div>
</div>

{* درجا اینیت کن (همراه با auto-init داخل expiry.js) *}
<script>
  if (window.PSPE && typeof window.PSPE.initExpiryField === 'function') {
    window.PSPE.initExpiryField(
      document.getElementById('pspe_expiry_display'),
      document.getElementById('pspe_expiry_date')
    );
  }
</script>
