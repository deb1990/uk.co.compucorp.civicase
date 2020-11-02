(function ($, _, currentCaseCategory) {
  $(document).on('crmLoad', function () {
    (function init () {
      var $pdfSaveAndCreateButtonSelector = '[data-identifier="buttons[_qf_PDF_upload]"]';

      $('body').off('click', $pdfSaveAndCreateButtonSelector);
      $('body').on('click', $pdfSaveAndCreateButtonSelector, redirectOnFileDownload);
    })();

    /**
     * Redirect the user after pdf generation.
     */
    function redirectOnFileDownload () {
      if (!isFormValid()) {
        return;
      }

      var caseId = getCaseId();
      var $activityTabInContactRecordScreen = $('.ui-tabs-anchor[title="Activities"]');

      if (caseId) {
        var activityTabUrl = '/civicrm/case/a/?' +
          'case_type_category=' + currentCaseCategory +
          '#/case/list?cf={"case_type_category":"' + currentCaseCategory + '"}' +
          '&caseId=' + caseId + '&focus=1&tab=Activities';

        document.location = activityTabUrl;
      } else if ($activityTabInContactRecordScreen.length) {
        $activityTabInContactRecordScreen.click();
      }

      $('.ui-dialog-titlebar-close').click();
    }

    /**
     * Returns the case id if available.
     *
     * @returns {number} case id.
     */
    function getCaseId () {
      var entryUrlElem = $('[name="entryURL"]');
      if (entryUrlElem.length) {
        var entryUrl = entryUrlElem.val();
        return getParameterByName('caseid', entryUrl);
      }
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
    function getParameterByName (name, url) {
      url = parse(decodeURIComponent(url || window.location.href));

      return url[name];
    }

    /**
     * Parses sent url inot json object
     *
     * @param {string} url the URL to use for parsing its parameter
     * @returns {object} returns the given URL's parameters as an object.
     */
    function parse (url) {
      var urlParamPairs = url.split('?')
        .slice(1)
        .join('')
        .split('&amp;')
        .map(function (paramNameAndValue) {
          return paramNameAndValue.split('=');
        });

      return _.zipObject(urlParamPairs);
    }
  });
})(CRM.$, CRM._, CRM['civicase-base'].currentCaseCategory);
