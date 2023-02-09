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

class CRM_Householdmerge_Page_Mergeview extends CRM_Core_Page {

  /**
   * Page will try and merge as many contacts as possible into the household,
   * and give an overview of the current status
   *
   * @param (via URL) hid  household ID
   * @param (via URL) oids other contact IDs, comma separated, to be merged int hid
   */
  public function run() {
    CRM_Utils_System::setTitle(ts('Merge Contacts into Household', array('domain' => 'de.systopia.householdmerge')));

    // extract IDs
    $household_id = (int) CRM_Utils_Array::value('hid', $_REQUEST);
    $other_ids    = [];
    $oids = preg_split('#,#', CRM_Utils_Array::value('oids', $_REQUEST, ""));
    foreach ($oids as $oid) {
      $oid = (int) $oid;
      if ($oid) {
        $other_ids[] = $oid;
      }
    }

    // verify parameters
    if (empty($household_id) || empty($other_ids)) {
      CRM_Core_Session::setStatus(ts('Household-Merge page cannot be called without "hid" or "oids" parameter.', array('domain' => 'de.systopia.householdmerge')), ts('Error', array('domain' => 'de.systopia.householdmerge')), 'error');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/dashboard'));
      return;
    }

    // NOW: load all contacts
    $household = civicrm_api3('Contact', 'getsingle', array('id' => $household_id));

    $other_contacts = [];
    foreach ($other_ids as $other_id) {
      $other_contact = civicrm_api3('Contact', 'getsingle', array('id' => $other_id));
      $other_contact['was_merged'] = (bool) !empty($other_contact['contact_is_deleted']);
      $other_contacts[] = $other_contact;
    }

    // AND: try to merge the (remaining) contacts
    $merge_controller = new CRM_Householdmerge_MergeController();
    $merge_controller->registerHHMerge($household_id, $other_ids);

    $merge_complete = TRUE;
    foreach ($other_contacts as &$other_contact) {
      if ($other_contact['was_merged']) continue;
      $cacheParams = [];
      $mode = 'safe';
      $dupePairs = [];
      $dupePairs[] = array('srcID' => $other_contact['id'], 'dstID' => $household_id);

      $result = CRM_Dedupe_Merger::merge($dupePairs, $cacheParams, $mode, FALSE);

      // process result
      if (!empty($result['skipped'])) {
        $other_contact['was_merged'] = FALSE;
        $merge_complete = FALSE;
      } else {
        $other_contact['was_merged'] = TRUE;
      }
    }

    // set the conflict counts
    $hhmerge_controller = new CRM_Householdmerge_MergeController();
    foreach ($other_contacts as &$other_contact) {
      $other_contact['conflict_count'] = $hhmerge_controller->getConflictCount($household_id, $other_contact['id']);
    }

    $this->assign('household',      $household);
    $this->assign('other',          $other_contacts);
    $this->assign('merge_complete', $merge_complete);

    if ($merge_complete) {
      $merge_controller->unregisterHHMerge($household_id);
    }

    parent::run();
  }
}
