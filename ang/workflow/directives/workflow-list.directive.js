(function ($, _, angular) {
  var module = angular.module('workflow');

  module.directive('workflowList', function () {
    return {
      scope: {
        caseTypeCategory: '@'
      },
      controller: 'workflowListController',
      templateUrl: '~/workflow/directives/workflow-list.directive.html',
      restrict: 'E'
    };
  });

  module.controller('workflowListController', workflowListController);

  /**
   * @param {object} $scope scope object
   * @param {object} ts ts
   * @param {object} civicaseCrmApi service to use civicrm api
   * @param {object[]} WorkflowListActionItems list of workflow list action items
   */
  function workflowListController ($scope, ts, civicaseCrmApi,
    WorkflowListActionItems) {
    $scope.ts = ts;
    $scope.isLoading = false;
    $scope.workflows = [];
    $scope.actionItems = WorkflowListActionItems;

    (function init () {
      refreshWorkflowsList();

      $scope.$on('workflow::list::refresh', refreshWorkflowsList);
    }());

    /**
     * Refresh workflows list
     */
    function refreshWorkflowsList () {
      $scope.isLoading = true;

      getWorkflows($scope.caseTypeCategory)
        .then(function (workflows) {
          $scope.workflows = workflows;
        })
        .finally(function () {
          $scope.isLoading = false;
        });
    }

    /**
     * Get list of workflows for the sent case type category
     *
     * @param {string} caseTypeCategory case type category
     * @returns {Promise} list of workflows
     */
    function getWorkflows (caseTypeCategory) {
      return civicaseCrmApi('CaseType', 'get', {
        sequential: 1,
        case_type_category: caseTypeCategory,
        options: { limit: 0 }
      }).then(function (data) {
        return data.values;
      });
    }
  }
})(CRM.$, CRM._, angular);
