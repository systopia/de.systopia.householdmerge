<?php
/*-------------------------------------------------------+
| Household Merger Extension                             |
| Copyright (C) 2015-2023 SYSTOPIA                       |
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

    // determine result count
    if (!empty($_REQUEST['count'])) {
      if ($_REQUEST['count'] == 'all') {
        $result_count = 'all';
      } else {
        $result_count = (int) $_REQUEST['count'];
        if ($result_count <= 0) $result_count = 25;
      }
    } else {
      $result_count = 25;
    }

    // first run the scanner
    $scanner = new CRM_Householdmerge_Logic_Scanner();
    $proposals = $scanner->findNewHouseholds($result_count);

    // set the country names
    $countries = CRM_Core_PseudoConstant::country();
    foreach ($proposals as $pid => $proposal) {
      foreach ($proposal['contacts'] as $contact_id => $contact) {
        if (!empty($contact['country_id'])) {
          $proposals[$pid]['contacts'][$contact_id]['country'] = $countries[$contact['country_id']];
        }
      }
    }

    $this->assign('proposals', $proposals);
    $this->assign('proposals_json', json_encode($proposals));
    $this->assign('result_count', $result_count);

    parent::run();
  }
}
