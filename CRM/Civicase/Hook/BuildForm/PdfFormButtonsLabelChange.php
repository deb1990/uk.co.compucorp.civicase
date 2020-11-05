<?php

/**
 * Changes the label of buttons in the create pdf popup.
 */
class CRM_Civicase_Hook_BuildForm_PdfFormButtonsLabelChange {

  /**
   * Updates form buttons labels.
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
    $buttonGroup = $form->getElement('buttons');
    $buttons = $buttonGroup->getElements();
    $buttonLabels = [
      '_qf_PDF_submit_preview' => 'Download Preview',
    ];
    foreach ($buttons as $button) {
      $attr = $button->getAttributes();
      if (!empty($attr['name']) && isset($buttonLabels[$attr['name']])) {
        $button->setValue(ts($buttonLabels[$attr['name']]));
      }
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
