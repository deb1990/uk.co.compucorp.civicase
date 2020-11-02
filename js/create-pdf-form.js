(function ($) {
  $(document).on('crmLoad', function () {
    /**
     * Redirect the user after pdf generation.
     */
    function redirectOnFileDownload () {
      if (isFormValid()) {
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

    /**
     * Returns the case id if available otherwise 0.
     *
     * @returns {number} case id.
     */
    function getCaseId () {
      var entryUrlElem = $('[name="entryURL"]');
      var caseId = 0;
      if (entryUrlElem.length) {
        var entryUrl = entryUrlElem.val();
        caseId = getParameterByName('caseid', entryUrl);
      }

      return caseId;
    }

    /**
     * Checks whether the form has errors or not.
     *
     * @returns {boolean} form validity.
     */
    function isFormValid () {
      return $('.CRM_Contact_Form_Task_PDF').find('.crm-error:not(.valid)').length === 0;
    }

    /**
     * Returns parameter value from a query string.
     *
     * @param {string} name  name of the parameter.
     * @param {string} url query string.
     * @returns {any} parameter value.
     */
    function getParameterByName (name, url = window.location.href) {
      name = name.replace(/[[]]/g, '\\$&');
      var regex = new RegExp('[?&;]' + name + '(=([^&#]*)|&|#|$)');
      var results = regex.exec(url);
      if (!results) {
        return null;
      }
      if (!results[2]) {
        return '';
      }
      return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    /**
     * Add or update a parameter in a url.
     *
     * @param {string} key parameter name.
     * @param {any} value parameter value.
     * @param {string} uri query string.
     * @returns {string} updates query string.
     */
    function updateQueryStringParameter (key, value, uri = window.location.href) {
      var re = new RegExp('([?&])' + key + '=.*?(&|$)', 'i');
      var separator = uri.indexOf('?') !== -1 ? '&' : '?';
      if (uri.match(re)) {
        return uri.replace(re, '$1' + key + '=' + value + '$2');
      } else {
        return uri + separator + key + '=' + value;
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
