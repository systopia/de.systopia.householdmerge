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

class CRM_Householdmerge_Logic_Checker {

  function __construct() {
  }

  /**
   * investigates if the given household still complies
   * with all the requirements for a proper household entity
   */
  function checkHousehold($household_id) {
    $problems_identified = array();

    // load household
    $household = civicrm_api3('Contact', 'getsingle', array('id' => $household_id));

    // load members
    $members = $this->getMembers($household_id);
    

    // CHECK 1: number of members
    if (count($members) < CRM_Householdmerge_Logic_Configuration::getMinimumMemberCount()) {
      if (count($members) == 0) {
        $problems_identified[] = ts("Household has no members any more.", array('domain' => 'de.systopia.housholdmerge'));
      } else {
        $problems_identified[] = ts("Household has only %1 members left.", array(1 => count($members), 'domain' => 'de.systopia.housholdmerge'));
      }
    }

    // HEAD related checks
    if ('hierarchy' == CRM_Householdmerge_Logic_Configuration::getHouseholdModeOptions()) {
      $heads = array();
      foreach ($members as $member) {
        if ($member['relation'] == 'head') {
          $heads[] = $member;
        }
      }

      // CHECK 2: is there still a head?
      if (empty($heads)) {
        $problems_identified[] = ts("Household has no head any more.", array('domain' => 'de.systopia.housholdmerge'));
      }

      // CHECK 3: is there more than one head?
      if (count($heads) > 1) {
        $problems_identified[] = ts("Household has multiple heads.", array('domain' => 'de.systopia.housholdmerge'));
      }

      // CHECK 4: does the head have a DO NOT mail/phone/sms/email
      $donts = CRM_Householdmerge_Logic_Configuration::getDontXXXChecks();
      foreach ($heads as $head) {
        foreach ($donts as $field_name) {
          if (!empty($head[$field_name])) {
            $problems_identified[] = ts("Household head has one of the 'do not contact' attributes set.", array('domain' => 'de.systopia.housholdmerge'));
            break;
          }
        }
      }

      // CHECK 5: does the head have certain tags
      $bad_tags = CRM_Householdmerge_Logic_Configuration::getBadHeadTags();
      foreach ($heads as $head) {
        $tags = CRM_Core_BAO_EntityTag::getContactTags($contact_id);
        foreach ($tags as $tag) {
          if (in_array($tag, $bad_tags)) {
            $problems_identified[] = ts("Household head has tag '%1'.", array(1 => $tag, 'domain' => 'de.systopia.housholdmerge'));
          }
        }
      }

      // CHECK 6: is the head also head of another household?
      // TODO
    }

    // all member checks

    // CHECK 7: does one of the members not have the household address any more?
    // TODO


    // CHECK 8: Is there a potential new member for this houshold?
    // TODO

    if (!empty($problems_identified)) {
      $this->createActivity($household, $problems_identified, $members);
    }
  }
}
