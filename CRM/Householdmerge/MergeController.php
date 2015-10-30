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

/*
 * This controller keeps track of merge operations
 * beyond process limits
 */
class CRM_Householdmerge_MergeController {

  protected $session;

  public function __construct() {
    $this->session = CRM_Core_Session::singleton();
  }

  /**
   * Register an ongoing houshold merge
   * This affects the merge hook and a comeback function
   */
  public function registerHHMerge($household_id, $contact_ids) {
    // store household_id => contact_ids mapping in session
    $contact_id_list = implode(',', $contact_ids);
    $this->session->set($household_id, $contact_id_list, 'hhmerge');
  }

  /**
   * Drop an ongoing houshold merge
   */
  public function unregisterHHMerge($household_id) {
    $this->session->set($household_id, NULL, 'hhmerge');
  }

  /**
   * check if this there is an ongoing household merge for this pair
   */
  protected function isHHMerge($mainId, $otherId) {
    // check if the key is present
    $merge = $this->session->get($mainId, 'hhmerge');
    if (empty($merge)) return FALSE;

    // check if the other ID is there
    $contact_ids = explode(',', $merge);
    return in_array($otherId, $contact_ids);
  }

  /**
   * store the conflict count for a merge pair
   */
  protected function setConflictCount($household_id, $contact_id, $count) {
    $this->session->set("$household_id//$contact_id", $count, 'hhmerge');
  }

  /**
   * get the conflict count for a merge pair
   */
  public function getConflictCount($household_id, $contact_id) {
    return (int) $merge = $this->session->get("$household_id//$contact_id", 'hhmerge');
  }

  /**
   * remove the obvious conflicts when merging individuals into housholds:
   *  first_name, contact_type, etc.
   */
  public function resolveConflicts($type, &$data, $mainId, $otherId) {
    if ($this->isHHMerge($mainId, $otherId)) {
      // DOESN'T WORK (goBackToHHMerge)
      // $this->session->popUserContext();
      // $this->session->pushUserContext(CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=7898'));

      // automatically resolve some of the problems
      $fields_to_ignore = array('move_contact_type', 'move_last_name', 'move_last_name', 'move_gender_id', 'move_birth_date', 'move_prefix_id');
      foreach ($fields_to_ignore as $field_name) {
        unset($data['fields_in_conflict'][$field_name]);
        unset($data[$field_name]);
        $this->setConflictCount($mainId, $otherId, count($data['fields_in_conflict']));
      }
    }
  }

  /**
   * redirect back to an existing (ongoing) household merge process.
   */
  public function goBackToHHMerge($household_id) {
    $contact_ids = $this->session->get($mainId, 'hhmerge');
    if (!empty($contact_ids)) {
      $mergeview_url = CRM_Utils_System::url('civicrm/household/mergeview', "hid=$household_id&oids=$contact_ids");
      CRM_Utils_System::redirect($mergeview_url);
    }
  }
}
