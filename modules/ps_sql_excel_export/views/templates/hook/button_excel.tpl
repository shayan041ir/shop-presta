{if isset($excel_export_url)}
  <a href="{$excel_export_url}"
     class="btn tooltip-link js-link-row-action"
     data-toggle="pstooltip"
     title="{l s='دریافت خروجی اکسل' mod='ps_sql_excel_export'}">
     <i class="material-icons">table_chart</i>
  </a>
{else}
  <div class="alert alert-warning mt-2">
    {l s='هیچ SQL Request ID یافت نشد.' mod='ps_sql_excel_export'}
  </div>
{/if}
