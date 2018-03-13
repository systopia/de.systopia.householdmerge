<?php
/*-------------------------------------------------------+
| Household Merger Extension                             |
| Copyright (C) 2015-2018 SYSTOPIA                       |
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

/**
 * Creates a new household according to the specs
 */
function civicrm_api3_contact_create_household($params) {
  if (empty($params['mode'])) {
    $params['mode'] = CRM_Householdmerge_Logic_Configuration::getHouseholdMode();
  }

  $head_id = NULL;
  switch ($params['mode']) {
    case 'hierarchy':
      $head_id = (int) $params['head_id'];
    case 'link':
      // get member IDs
      $member_ids = $params['member_ids'];
      if (is_string($member_ids)) $member_ids = explode(',', $member_ids);

      // sanitise member IDs
      $sanitised_member_ids = array();
      foreach ($member_ids as $member_id) {
        $sanitised_member_id = (int) $member_id;
        if ($sanitised_member_id) {
          $sanitised_member_ids[] = $sanitised_member_id;
        }
      }

      // now pass the work on the the worker
      $worker = new CRM_Householdmerge_Logic_Worker();
      $household_id = $worker->createLinkedHousehold($params['household_name'], $sanitised_member_ids, $params['address'], $head_id);

      return civicrm_api3_create_success();

    default:
      return civicrm_api3_create_error("Contact.create_household cannot process mode '{$params['mode']}'.");
  }
}


function _civicrm_api3_contact_create_household_spec(&$params) {
  $params['mode'] = array(
    'title'        => "Household Mode",
    'description'  => "See CRM_Householdmerge_Logic_Configuration for valid modes. If omitted, the configured mode will be used.",
    'type'         => CRM_Utils_Type::T_STRING,
  );

  $params['member_ids'] = array(
    'title'        => "New members' contact IDs",
    'description'  => "list of contact IDs",
    // 'type'         => CRM_Utils_Type::T_STRING,
  );

  $params['head_id'] = array(
    'title'        => "Contact ID of household head",
    'description'  => "Only used in mode 'hierarchy'",
    'type'         => CRM_Utils_Type::T_STRING,
  );

  $params['address'] = array(
    'title'        => "Address to be created with the household",
    'description'  => "Only used when a new household is created.",
  );

  $params['household_name'] = array(
    'title'        => "name for the household",
    'description'  => "Only used when a new household is created.",
    'type'         => CRM_Utils_Type::T_STRING,
  );
}

