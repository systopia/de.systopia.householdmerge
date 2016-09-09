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

class CRM_Householdmerge_Logic_Fixer {

  /**
   * Fix a "new houshold member" problem by connecting
   * the new contact to the household, unless he's already
   * connected
   */
  public static function fixHMNW($problem) {
    $activity_id = $problem->getActivityID();
    if (empty($activity_id)) return FALSE;

    // get all target contacts
    $contact_ids = CRM_Activity_BAO_ActivityTarget::retrieveTargetIdsByActivityId($activity_id);
    if (empty($contact_ids)) return FALSE;

    // load all contacts
    $new_contact_id = NULL;
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
}
