<?php

/**
 * Add script to create pdf form.
 */
class CRM_Civicase_Hook_BuildForm_AddScriptToCreatePdfForm {

  /**
   * Add script to create pdf form.
   *
   * @param CRM_Core_Form $form
   *   Form Class object.
   * @param string $formName
   *   Form Name.
   */
  public function run(CRM_Core_Form &$form, $formName) {
    if (!$this->shouldRun($form, $formName)) {
      return;
    }
    CRM_Core_Resources::singleton()->addScriptFile(
      'uk.co.compucorp.civicase', 'js/create-pdf-form.js');
  }

  /**
   * Determines if the hook will run.
   *
   * @param CRM_Core_Form $form
   *   Form Class object.
   * @param string $formName
   *   Form class object.
   *
   * @return bool
   *   returns a boolean to determine if hook will run or not.
   */
  private function shouldRun(CRM_Core_Form $form, $formName) {
    return $formName == CRM_Contact_Form_Task_PDF::class && $form->getAction() === CRM_Core_Action::ADD;
  }

}
