<?php
/*-------------------------------------------------------+
| Household Merger Extension                             |
| Copyright (C) 2015 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Householdmerge_Form_Task_Fix extends CRM_Activity_Form_Task {
  
  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts("Automatic Correction of Household Problems", array('domain' => 'de.systopia.householdmerge')));    
    $this->addDefaultButtons(ts("Try to fix", array('domain' => 'de.systopia.householdmerge')), 'done');
    parent::buildQuickForm();
  }


  function postProcess() {
    parent::postProcess();
  }

}
