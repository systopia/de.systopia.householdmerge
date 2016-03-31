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

require_once 'CRM/Core/Page.php';

class CRM_Householdmerge_Page_Finder extends CRM_Core_Page {
  
  public function run() {
    CRM_Utils_System::setTitle(ts('Household Finder'));

    // first run the scanner
    $scanner = new CRM_Householdmerge_Logic_Scanner();
    $proposals = $scanner->findNewHouseholds(5);

    $this->assign('proposals', $proposals);

    parent::run();
  }
}
