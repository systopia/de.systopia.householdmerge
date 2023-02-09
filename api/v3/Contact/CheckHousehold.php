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

/**
 * Will run the household checker
 */
function civicrm_api3_contact_check_household($params) {

  $checker = new CRM_Householdmerge_Logic_Checker();

  if (!empty($params['household_id'])) {
    $household_id = (int) $params['household_id'];
    if ($household_id) {
      $checker->checkHousehold($household_id);
    }

  } else {
    $max_count = NULL;
    if (!empty($params['max_count'])) {
      $max_count = (int) $params['max_count'];
    }

    $checker->checkAllHouseholds($max_count);
  }

  return civicrm_api3_create_success();
}


function _civicrm_api3_contact_check_household_spec(&$params) {
  $params['max_count'] = array(
    'title'        => "Household Mode",
    'description'  => "See CRM_Householdmerge_Logic_Configuration for valid modes. If omitted, the configured mode will be used.",
    'type'         => CRM_Utils_Type::T_STRING,
  );

  $params['household_id'] = array(
    'title'        => "Household Mode",
    'description'  => "See CRM_Householdmerge_Logic_Configuration for valid modes. If omitted, the configured mode will be used.",
    'type'         => CRM_Utils_Type::T_STRING,
  );
}

