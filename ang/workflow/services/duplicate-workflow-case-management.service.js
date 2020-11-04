(function (_, angular) {
  var module = angular.module('workflow');

  module.service('DuplicateWorkflowCasemanagement', DuplicateWorkflow);

  /**
   * Duplicate Case management workflows service
   *
   * @param {Function} civicaseCrmApi civicrm api service
   */
  function DuplicateWorkflow (civicaseCrmApi) {
    this.create = create;

    /**
     * @param {object} workflow workflow object
     * @returns {Array} api call parameters
     */
    function create (workflow) {
      return civicaseCrmApi([
        ['CaseType', 'create', _.extend({}, workflow, { id: null })]
      ]);
    }
  }
})(CRM._, angular);
