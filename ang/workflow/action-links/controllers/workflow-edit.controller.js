(function ($, _, angular, getCrmUrl) {
  var module = angular.module('workflow');

  module.controller('WorkflowEditController', WorkflowEditController);

  /**
   * @param {object} $scope scope object
   * @param {object} $window browsers window object
   */
  function WorkflowEditController ($scope, $window) {
    $scope.clickHandler = clickHandler;

    /**
     * Redirects to the Edit Workflow page
     *
     * @param {string/number} workflowID id of the workflow
     */
    function clickHandler (workflowID) {
      var url = getCrmUrl('civicrm/a/#/caseType/' + workflowID);

      $window.location.href = url;
    }
  }
})(CRM.$, CRM._, angular, CRM.url);
