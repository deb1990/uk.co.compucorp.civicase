(function ($, _) {
  $(document).one('crmLoad', function () {
    var $dialog;

    (function init () {
      var $form = $('.CRM_Contact_Form_Task_PDF');

      $('body').off('submit', $form);
      $('body').on('submit', $form, openFileNamePopup);
    })();

    /**
     * Open popup to ask filename
     *
     * @param {object} e event
     */
    function openFileNamePopup (e) {
      if (!isFormValid()) {
        return;
      }

      var $form = $(e.target);
      var $clickedButtonIdentifier = $(document.activeElement).data('identifier');
      var isDownloadDocumentButtonClicked = $clickedButtonIdentifier === 'buttons[_qf_PDF_upload]';

      var pdfFileNameHiddenField = $('#pdf_filename_hidden');

      if (pdfFileNameHiddenField.length > 0 || !isDownloadDocumentButtonClicked) {
        return;
      }

      e.preventDefault();

      $dialog = $('<div class="civicase__pdf-filename-dialog"></div>')
        .html(getPopupMarkUp($form))
        .dialog(getPopupSettings());

      $('form', $dialog).crmValidate();
    }

    /**
     * Checks whether the form has errors or not.
     *
     * @returns {boolean} form validity.
     */
    function isFormValid () {
      return $('.CRM_Contact_Form_Task_PDF').valid();
    }

    /**
     * Get html markup for the filename popup
     *
     * @param {object} $form form element
     *
     * @returns {string} markup
     */
    function getPopupMarkUp ($form) {
      var subject = $form.find('#subject').val();

      return '<form id="civicase__pdf-filename-form">' +
        '<div class="crm-form-block crm-block crm-contact-task-pdf-form-block">' +
          '<table class="form-layout-compressed">' +
             '<tbody>' +
                '<tr>' +
                  '<td class="label-left">' +
                    '<label for="template">Select a filename</label>' +
                  '</td>' +
                '</tr>' +
                '<tr>' +
                  '<td>' +
                    '<input type="text" id="pdf_filename" pattern="[a-zA-Z0-9-_. ]+" class="huge crm-form-text required" value="' + subject + '">' +
                  '</td>' +
                '</tr>' +
              '</tbody>' +
            '</table>' +
          '</div>' +
        '</form>';
    }

    /**
     * Get settings for the filename popup
     *
     * @returns {object} settings object
     */
    function getPopupSettings () {
      return {
        title: ts('Download Document'),
        width: 'auto',
        height: 'auto',
        buttons: [
          {
            text: 'Download Document',
            click: function () {
              if (!$('#civicase__pdf-filename-form').valid()) {
                return;
              }
              $('#pdf_filename_hidden').remove();

              var $form = $('.CRM_Contact_Form_Task_PDF');
              $('<input>').attr({
                type: 'hidden',
                id: 'pdf_filename_hidden',
                name: 'filename',
                value: $('#pdf_filename').val()
              }).appendTo($form);

              $dialog.remove();
              $('[data-identifier="buttons[_qf_PDF_upload]"]').trigger('click');
              $('#pdf_filename_hidden').remove();
            }
          },
          {
            text: 'Cancel',
            click: function () {
              $dialog.remove();
            }
          }
        ]
      };
    }
  });
})(CRM.$, CRM._);
