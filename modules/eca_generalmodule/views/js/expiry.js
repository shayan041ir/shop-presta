/* نیازمندی: jQuery + views/lib/persianDatepicker/js/persianDatepicker.min.js */
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

  function init(displaySel, hiddenSel) {
    var $display = $(displaySel);
    var $hidden  = $(hiddenSel);
    if (!$display.length || !$hidden.length) return;

    // مقدار اولیه میلادی از hidden → نمایش با اسلش
    var initialG = ($hidden.val() || '').trim();
    if (initialG) {
      var clean = normToYMDDash(initialG);
      $hidden.val(clean);
      $display.val(clean.replace(/-/g,'/')).attr('data-gdate', clean.replace(/-/g,'/'));
    }

    if (typeof $display.persianDatepicker !== 'function') {
      // اگر پلاگین لود نبود، حداقل با تایپ دستی نرمال کن
      $display.on('input change', function () {
        $hidden.val(normToYMDDash($(this).val().trim()));
      });
      return;
    }

    $display.persianDatepicker({
      showGregorianDate: true,       // انتخاب = میلادی
      formatDate: 'YYYY/MM/DD',
      onSelect: function () {
        var g = $display.attr('data-gdate') || $display.val();
        $hidden.val(normToYMDDash(g));
      }
    });

    $display.on('input', function () {
      if (!$(this).val().trim()) {
        $hidden.val('');
        $display.removeAttr('data-gdate data-jdate');
      }
    });
  }

  // فرم‌های مدرن محصول Ajaxی رندر می‌شن؛ پس با تاخیر کوتاه تلاش کنیم
  function tryInit() {
    var $d = $('#pspe_expiry_display');
    var $h = $('#pspe_expiry_date');
    if ($d.length && $h.length) {
      init($d, $h);
    } else {
      // اگر هنوز تب/Step لود نشده بود، دوباره تلاش کن
      setTimeout(tryInit, 300);
    }
  }

  $(document).ready(tryInit);
})(window, window.jQuery);
