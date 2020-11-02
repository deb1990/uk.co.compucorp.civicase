<?php

/**
 * Makes the subject field required in the create PDF form.
 */
class CRM_Civicase_Hook_BuildForm_MakePdfFormSubjectRequired {

  /**
   * Makes the subject field required in the create PDF form.
   *
   * @param CRM_Core_Form $form
   *   Form Class object.
   * @param string $formName
   *   Form Name.
   */
  public function run(CRM_Core_Form &$form, $formName) {
    if (!$this->shouldRun($formName)) {
      return;
    }
    if ($form->elementExists('subject')) {
      $form->addRule('subject', 'Subject is a required field', 'required');
    }
  }

  /**
   * Determines if the hook will run.
   *
   * @param string $formName
   *   Form class object.
   *
   * @return bool
   *   returns a boolean to determine if hook will run or not.
   */
  private function shouldRun($formName) {
    return $formName == CRM_Contact_Form_Task_PDF::class;
  }

}
