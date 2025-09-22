/* modules/ps_product_expiry/views/js/expiry.js */
(function (window, $) {
  if (!$) return;

  function normToYMDDash(input) {
    if (!input) return '';
    var p = String(input).split(/[\/\-\.]/);
    if (p.length !== 3) return '';
    var y = String(p[0]).padStart(4, '0');
    var m = String(parseInt(p[1], 10) || 0).padStart(2, '0');
    var d = String(parseInt(p[2], 10) || 0).padStart(2, '0');
    return y + '-' + m + '-' + d;
  }

  function init(selectorDisplay, selectorHidden) {
    var $display = $(selectorDisplay);
    var $hidden  = $(selectorHidden);
    if (!$display.length || !$hidden.length) return;

    // مقدار اولیهٔ میلادی از hidden (DB) → نمایش جلالی/میلادی استاندارد
    var initialG = ($hidden.val() || '').trim();
    if (initialG) {
      var clean = normToYMDDash(initialG);
      $hidden.val(clean);
      $display.val(clean.replace(/-/g,'/')).attr('data-gdate', clean.replace(/-/g,'/'));
    }

    if (typeof $display.persianDatepicker !== 'function') {
      // اگر پلاگین لود نیست، حداقل با تایپ دستی نرمال کن
      $display.on('input change', function () {
        $hidden.val(normToYMDDash($(this).val().trim()));
      });
      return;
    }

    $display.persianDatepicker({
    showGregorianDate: true,          // انتخاب = میلادی
    formatDate: 'YYYY/MM/DD',
    onSelect: function () {
        var g = $display.attr('data-gdate') || $display.val(); // 2025/09/18 یا 2025/9/18
        // نرمال به YYYY-MM-DD
        var p = String(g).split(/[\/\-\.]/);
        var y = String(p[0]).padStart(4,'0');
        var m = String(parseInt(p[1]||0,10)).padStart(2,'0');
        var d = String(parseInt(p[2]||0,10)).padStart(2,'0');
        $hidden.val(y + '-' + m + '-' + d);
    }
    });


    $display.on('input', function () {
      if (!$(this).val().trim()) {
        $hidden.val('');
        $display.removeAttr('data-gdate data-jdate');
      }
    });
  }

  // اگر IDs پیش‌فرض استفاده شده باشد، خودکار اینیت می‌شود
  $(function () { init('#pspe_expiry_display', '#pspe_expiry_date'); });

  // API اختیاری
  window.PSPE = window.PSPE || {};
  window.PSPE.initExpiryField = function (displayEl, hiddenEl) {
    var dSel = displayEl && displayEl.nodeType === 1 ? displayEl : '#pspe_expiry_display';
    var hSel = hiddenEl  && hiddenEl.nodeType  === 1 ? hiddenEl  : '#pspe_expiry_date';
    init(dSel, hSel);
  };
})(window, window.jQuery);
