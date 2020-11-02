/* eslint-env jasmine */

((_, getCrmUrl) => {
  describe('workflow edit controller', () => {
    let $controller, $rootScope, $scope, $window;

    beforeEach(module('workflow', ($provide) => {
      $provide.value('$window', { location: {} });
    }));

    beforeEach(inject((_$controller_, _$rootScope_, _$window_) => {
      $controller = _$controller_;
      $rootScope = _$rootScope_;
      $window = _$window_;
    }));

    describe('when clicking on edit button', () => {
      beforeEach(() => {
        getCrmUrl.and.returnValue('crm url mock');
        getCrmUrl.calls.reset();
        initController();
        $scope.clickHandler(5);
      });

      it('redirects to the case type page for the clicked workflow', () => {
        expect(getCrmUrl).toHaveBeenCalledWith('civicrm/a/#/caseType/5');
        expect($window.location.href).toBe('crm url mock');
      });
    });

    /**
     * Initializes the workflow epic controller.
     */
    function initController () {
      $scope = $rootScope.$new();

      $controller('WorkflowEditController', { $scope: $scope });
    }
  });
})(CRM._, CRM.url);
