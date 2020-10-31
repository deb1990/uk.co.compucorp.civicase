(function ($) {

  $(document).on('crmLoad', function () {

    function redirectOnFileDownload() {
      if(isFormValid()) {
        var caseId = getCaseId();
        if (caseId) {
          var urlWithTab = updateQueryStringParameter('tab', 'Activities');
          document.location = updateQueryStringParameter('caseId', caseId, urlWithTab);
        } else {
          if ($('.ui-tabs-anchor[title="Activities"]').length) {
            $('.ui-tabs-anchor[title="Activities"]').click();
          }
        }
        $('.ui-dialog-titlebar-close').click();
      }
    }

    function getCaseId() {
      var entryUrlElem = $('[name="entryURL"]');
      var caseId = 0;
      if (entryUrlElem.length) {
        var entryUrl = entryUrlElem.val();
        caseId = getParameterByName('caseid', entryUrl);
      }

      return caseId;
    }

    function isFormValid() {
      return $('.CRM_Contact_Form_Task_PDF').find('.crm-error:not(.valid)').length == 0;
    }

    function getParameterByName(name, url = window.location.href) {
      name = name.replace(/[\[\]]/g, '\\$&');
      var regex = new RegExp('[?&;]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
      if (!results) {
        return null;
      }
      if (!results[2]) {
        return '';
      }
      return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    function updateQueryStringParameter(key, value, uri = window.location.href) {
      var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
      var separator = uri.indexOf('?') !== -1 ? "&" : "?";
      if (uri.match(re)) {
        return uri.replace(re, '$1' + key + "=" + value + '$2');
      }
      else {
        return uri + separator + key + "=" + value;
      }
    }

    $('body').off('click', '[data-identifier="buttons[_qf_PDF_upload]"]');
    $('body').on('click', '[data-identifier="buttons[_qf_PDF_upload]"]', function () {
      setTimeout(function () {
        redirectOnFileDownload();
      }, 200);
    });
  });
})(CRM.$);
