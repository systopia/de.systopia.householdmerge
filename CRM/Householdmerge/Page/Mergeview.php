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

class CRM_Householdmerge_Page_Mergeview extends CRM_Core_Page {

  /**
   * Page will try and merge as many contacts as possible into the household,
   * and give an overview of the current status
   *
   * @param (via URL) hid  household ID
   * @param (via URL) oids other contact IDs, comma separated, to be merged int hid
   */
  public function run() {
    CRM_Utils_System::setTitle(ts('Merge Contacts into Household'));

    // extract IDs
    $household_id = (int) CRM_Utils_Array::value('hid', $_REQUEST);
    error_log($household_id);
    $other_ids    = array();
    $oids = preg_split('#,#', CRM_Utils_Array::value('oids', $_REQUEST, ""));
    error_log(CRM_Utils_Array::value('oids', $_REQUEST, ""));
    error_log($oids);
    foreach ($oids as $oid) {
      $oid = (int) $oid;
      if ($oid) {
        $other_ids[] = $oid;
      }
    }
    error_log(print_r($other_ids,1));

    // verify parameters
    if (empty($household_id) || empty($other_ids)) {
      CRM_Core_Session::setStatus(ts('Household-Merge page cannot be called without "hid" or "oids" parameter.'), ts('Error'), 'error');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/dashboard'));
      return;
    }

    // NOW: load all contacts
    $household = civicrm_api3('Contact', 'getsingle', array('id' => $household_id));

    $other_contacts = array();
    foreach ($other_ids as $other_id) {
      $other_contact = civicrm_api3('Contact', 'getsingle', array('id' => $other_id));
      error_log(print_r($other_contact,1));
      $other_contact['was_merged'] = (bool) !empty($other_contact['is_deleted']);
      $other_contacts[] = $other_contact;
    }

    // AND: try to merge the (remaining) contacts
    foreach ($other_contacts as $other_contact) {
      if ($other_contact['was_merged']) continue;
      $dedupe[] = array('srcID' => $other_contact['id'], 'dstID' => $household_id['id']);
      
      CRM_Householdmerge_Logic_Util::enableMerge();
      $result = CRM_Dedupe_Merger::merge($dedupe, array(), 'safe', FALSE);
      CRM_Householdmerge_Logic_Util::disableMerge();

      // TODO: process result

      print_r($result);
    }

  // $mode = CRM_Utils_Array::value('mode', $params, 'safe');
  // $autoFlip = CRM_Utils_Array::value('auto_flip', $params, TRUE);

  // $dupePairs = array(array(
  // 'srcID' => CRM_Utils_Array::value('main_id', $params),
  //     'dstID' => CRM_Utils_Array::value('other_id', $params),
  //   ));
  // $result = CRM_Dedupe_Merger::merge($dupePairs, array(), $mode, $autoFlip);

    $this->assign('household', $household);
    $this->assign('other',     $other_contacts);

    parent::run();
  }
}
