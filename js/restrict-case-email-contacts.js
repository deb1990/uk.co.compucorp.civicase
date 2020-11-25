(function ($, _, caseSettings) {
  var $recipientFields;
  var CASE_CONTACTS = JSON.parse(caseSettings.case_contacts);

  // Without the timeout the CC and BCC fields can't be properly replaced
  setTimeout(function init () {
    initSelectors();
    addNewRecipientDropdowns();
  });

  /**
   * Replaces the "To", "CC", "BCC" dropdowns with new ones that only contain
   * case contacts. The fields will only display contacts that have not been
   * selected by other fields.
   *
   * The "To" field values must be stored in a `123::contact@example.com` format,
   * where `123` is the contact's ID. The "CC" and "BCC" field values must contain
   * the contact ID only.
   */
  function addNewRecipientDropdowns () {
    $recipientFields.each(function () {
      var $field = $(this);
      var isToField = $field.attr('name') === 'to';

      $field.crmSelect2({
        multiple: true,
        data: function () {
          var myContacts = getFieldContactValues($field);
          var otherContacts = getFieldContactValues($recipientFields.not($field));
          var availableContacts = getFilteredCaseContacts({
            include: myContacts,
            exclude: otherContacts
          });
          var caseContactSelect2Options = isToField
            ? getCaseContactOptions({ caseContacts: availableContacts, idFieldName: 'value' })
            : getCaseContactOptions({ caseContacts: availableContacts, idFieldName: 'contact_id' });

          return { results: caseContactSelect2Options };
        }
      });
    });
  }

  /**
   * @param {object} options list of options.
   * @param {object[]} options.caseContacts A list of case contacts to generate
   *   the options from.
   * @param {string} options.idFieldName The contact's field name to use as the
   *   option's ID.
   * @returns {object[]} The list of case contacts as expected by the select2
   *   dropdowns.
   */
  function getCaseContactOptions (options) {
    return _.chain(options.caseContacts)
      .uniq(function (caseContact) {
        return caseContact.contact_id;
      })
      .map(function (caseContact) {
        return {
          id: caseContact[options.idFieldName],
          text: _.template('<%= display_name %> <<%= email %>>')(caseContact)
        };
      })
      .value();
  }

  /**
   * @param {object} filters A list of filters.
   * @param {string[]} filters.exclude Contact values to exclude from the
   *   returned objects.
   * @param {string[]} filters.include Contact values that must be included
   *   even if they have been marked as excluded.
   * @returns {object[]} A list of contacts filtered by the given parameters.
   */
  function getFilteredCaseContacts (filters) {
    return _.filter(CASE_CONTACTS, function (caseContact) {
      var isIncluded = !!_.find(filters.include, function (fieldValue) {
        return fieldValue === caseContact.value || fieldValue === caseContact.contact_id;
      });
      var isExcluded = !!_.find(filters.exclude, function (fieldValue) {
        return fieldValue === caseContact.value || fieldValue === caseContact.contact_id;
      });

      return isIncluded || !isExcluded;
    });
  }

  /**
   * @param {object} $fields jQuery selector.
   * @returns {string[]} The contact values as stored on the given fields.
   */
  function getFieldContactValues ($fields) {
    return $fields.map(function () {
      return $(this).val().split(',');
    }).toArray();
  }

  /**
   * Populates the Recipient form rows selectors.
   */
  function initSelectors () {
    var recipientFieldRowsSelectors = [
      '.crm-contactEmail-form-block-recipient input[name]',
      '.crm-contactEmail-form-block-cc_id input[name]',
      '.crm-contactEmail-form-block-bcc_id input[name]'
    ];
    $recipientFields = $(recipientFieldRowsSelectors.join(','));
  }
})(CRM.$, CRM._, CRM['civicase-base']);
