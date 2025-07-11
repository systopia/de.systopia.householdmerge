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

class CRM_Householdmerge_Logic_Fixer {

  /**
   * Fix a "new household member" problem by connecting
   * the new contact to the household, unless he's already
   * connected
   */
  public static function fixHMNW($problem) {
    $activity_id = $problem->getActivityID();
    if (empty($activity_id)) return FALSE;

    // get all target contacts
    $contact_ids = CRM_Householdmerge_Logic_Fixer::retrieveTargetIdsByActivityId($activity_id);
    if (empty($contact_ids)) return FALSE;

    // load all contacts
    $new_contact_id = NULL;
    $contact_id_list = implode(',', $contact_ids);
    Civi::log()->debug("civicrm_api3('Contact', 'get', array('id' => array('IN' => {$contact_id_list})))");
    $contact_data = civicrm_api3('Contact', 'get', array('id' => array('IN' => $contact_ids)));
    foreach ($contact_data['values'] as $contact_id => $contact) {
      if ($contact['contact_type'] == 'Individual') {
        if ($new_contact_id === NULL) {
          $new_contact_id = $contact['id'];
        } else {
          // there are multiple new contacts here...
          return FALSE;
        }
      }
    }

    if (empty($new_contact_id)) {
      // no contact found
      return FALSE;
    }


    // check if there is already a relationship
    $relation_ids[] = CRM_Householdmerge_Logic_Configuration::getMemberRelationID();
    $relation_ids[] = CRM_Householdmerge_Logic_Configuration::getHeadRelationID();
    $existing_relationship = civicrm_api3('Relationship', 'get', array(
      'contact_id_a'         => $new_contact_id,
      'contact_id_b'         => $problem->getHouseholdID(),
      'relationship_type_id' => array('IN' => $relation_ids),
      'is_active'            => 1));
    if ($existing_relationship['count']) {
      // there already is a relationship, the problem IS fixed already...
    } else {
      // create a new relationship
      civicrm_api3('Relationship', 'create', array(
        'contact_id_a'         => $new_contact_id,
        'contact_id_b'         => $problem->getHouseholdID(),
        'relationship_type_id' => CRM_Householdmerge_Logic_Configuration::getMemberRelationID(),
        'is_active'            => 1));
    }

    return TRUE;
  }

  /**
   * Get all the target contact IDs for the given activity_id
   *
   * @param int $activity_id
   *
   * @return array list of contact IDs
   */
  public static function retrieveTargetIdsByActivityId($activity_id) {
    if (empty($activity_id)) {
      return [];
    }

    // find all target contact IDs for this activity
    $activityTargetContacts = \Civi\Api4\ActivityContact::get(FALSE)
      ->addSelect('activity_id.target_contact_id')
      ->addWhere('activity_id', '=', $activity_id)
      ->addWhere('record_type_id:name', '=', 'Activity Targets')
      ->execute();

    // and return as an array
    $targetIds = [];
    foreach ($activityTargetContacts as $activity_record) {
      $targetIds[] = $activity_record['activity_id.target_contact_id'];
    }
    $targetIds = array_unique($targetIds);
    Civi::log()->debug(json_encode($targetIds));
    return $targetIds;
  }
}
