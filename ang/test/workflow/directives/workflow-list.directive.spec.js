/* eslint-env jasmine */

((_) => {
  describe('workflow list', () => {
    let $q, $controller, $rootScope, $scope, CaseTypesMockData,
      civicaseCrmApiMock, WorkflowListActionItems;

    beforeEach(module('workflow', 'civicase.data', ($provide) => {
      civicaseCrmApiMock = jasmine.createSpy('civicaseCrmApi');

      $provide.value('civicaseCrmApi', civicaseCrmApiMock);
    }));

    beforeEach(inject((_$q_, _$controller_, _$rootScope_, _CaseTypesMockData_,
      _WorkflowListActionItems_) => {
      $q = _$q_;
      $controller = _$controller_;
      $rootScope = _$rootScope_;
      WorkflowListActionItems = _WorkflowListActionItems_;
      CaseTypesMockData = _CaseTypesMockData_;
    }));

    describe('basic tests', () => {
      beforeEach(() => {
        civicaseCrmApiMock.and.returnValue($q.resolve({
          values: CaseTypesMockData.getSequential()
        }));
        initController();
      });

      it('hides the empty message before case types are loaded', () => {
        expect($scope.isLoading).toBe(true);
      });

      describe('after case types are loaded', () => {
        beforeEach(() => {
          $scope.$digest();
        });

        it('shows the results after case types is loaded', () => {
          expect($scope.isLoading).toBe(false);
        });

        it('fetches the case types for the current case type category', () => {
          expect(civicaseCrmApiMock).toHaveBeenCalledWith('CaseType', 'get', {
            sequential: 1,
            case_type_category: 'some_case_type_category',
            options: { limit: 0 }
          });
        });

        it('displays the list of fetched workflows', () => {
          expect($scope.workflows).toEqual(CaseTypesMockData.getSequential());
        });

        it('displays the action items dropdown', () => {
          expect($scope.actionItems).toEqual(WorkflowListActionItems);
        });
      });
    });

    describe('when list refresh event is fired', () => {
      beforeEach(() => {
        civicaseCrmApiMock.and.returnValue($q.resolve({
          values: CaseTypesMockData.getSequential()
        }));
        initController();
        $scope.$digest();
        $scope.workflows = [];
        $rootScope.$broadcast('workflow::list::refresh');
        $scope.$digest();
      });

      it('fetches the case types for the current case type category', () => {
        expect(civicaseCrmApiMock.calls.count()).toBe(2);
        expect(civicaseCrmApiMock.calls.mostRecent().args).toEqual(['CaseType', 'get', {
          sequential: 1,
          case_type_category: 'some_case_type_category',
          options: { limit: 0 }
        }]);
      });

      it('refreshes the workflows list', () => {
        expect($scope.workflows).toEqual(CaseTypesMockData.getSequential());
      });
    });

    /**
     * Initializes the contact case tab case details controller.
     */
    function initController () {
      $scope = $rootScope.$new();
      $scope.caseTypeCategory = 'some_case_type_category';

      $controller('workflowListController', { $scope: $scope });
    }
  });
})(CRM._);
