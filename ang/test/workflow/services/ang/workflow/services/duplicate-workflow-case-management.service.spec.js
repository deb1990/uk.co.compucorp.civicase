/* eslint-env jasmine */

((_) => {
  describe('duplicate case management workflow', () => {
    let civicaseCrmApiMock, CaseTypesMockData, DuplicateWorkflowCasemanagement;

    beforeEach(module('workflow', 'civicase.data', ($provide) => {
      civicaseCrmApiMock = jasmine.createSpy('civicaseCrmApi');

      $provide.value('civicaseCrmApi', civicaseCrmApiMock);
    }));

    beforeEach(inject((_DuplicateWorkflowCasemanagement_, _CaseTypesMockData_) => {
      DuplicateWorkflowCasemanagement = _DuplicateWorkflowCasemanagement_;
      CaseTypesMockData = _CaseTypesMockData_;
    }));

    describe('when duplicating a workflow', () => {
      var workflow;

      beforeEach(() => {
        workflow = CaseTypesMockData.getSequential(0);
        DuplicateWorkflowCasemanagement.create(workflow);
      });

      it('creates a duplicate workflow', () => {
        expect(civicaseCrmApiMock).toHaveBeenCalledWith([
          ['CaseType', 'create', _.extend({}, workflow, { id: null })]
        ]);
      });
    });
  });
})(CRM._);
