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

  /** cached values */
  protected $_activity_type_id    = NULL;
  protected $_activity_status_ids = NULL;

  function __construct() {
  }


  /**
   * Identify all the households to check and do it
   *
   * If max_count is set, it will stop after that amount,
   * saving the last household id for the next call
   */
  public function checkAllHouseholds($max_count = NULL) {
    $max_count = (int) $max_count;
    
    $activity_type_id    = $this->getCheckActivityTypeID();
    $activity_status_ids = $this->getActiveActivityStatusIDs();

    if ($max_count) {
      $contact_id_minimum = CRM_Core_BAO_Setting::getItem(CRM_Householdmerge_Logic_Configuration::$HHMERGE_SETTING_DOMAIN, 'hh_check_last_id');
      if (!$contact_id_minimum) $contact_id_minimum = 0;
      $limit_clause = "LIMIT $max_count";
    } else {
      $contact_id_minimum = 0;
      $limit_clause = '';
    }

    $last_contact_id_processed = 0;
    $selector_sql = "SELECT civicrm_contact.id AS contact_id
                     FROM civicrm_contact
                     LEFT JOIN civicrm_activity_contact ON civicrm_activity_contact.contact_id = civicrm_contact.id
                     LEFT JOIN civicrm_activity ON civicrm_activity_contact.activity_id = civicrm_activity.id AND civicrm_activity.activity_type_id = $activity_type_id AND civicrm_activity.status_id IN ($activity_status_ids)
                     WHERE contact_type = 'Household'
                       AND civicrm_activity.id IS NULL                       
                       AND (civicrm_contact.is_deleted IS NULL or civicrm_contact.is_deleted = 0)
                       AND civicrm_contact.id > $contact_id_minimum
                     GROUP BY civicrm_contact.id
                     ORDER BY civicrm_contact.id ASC
                     $limit_clause";
    $query = CRM_Core_DAO::executeQuery($selector_sql);
    while ($query->fetch()) {
      $last_contact_id_processed = $query->contact_id;
      $this->checkHousehold($last_contact_id_processed);
      $max_count--;
    }

    // done
    if ($max_count > 0) {
      // we're through the whole list, reset marker
      CRM_Core_BAO_Setting::setItem(0, CRM_Householdmerge_Logic_Configuration::$HHMERGE_SETTING_DOMAIN, 'hh_check_last_id');
    } else {
      CRM_Core_BAO_Setting::setItem($last_contact_id_processed, CRM_Householdmerge_Logic_Configuration::$HHMERGE_SETTING_DOMAIN, 'hh_check_last_id');
    }

    return;
  }


  /**
   * investigates if the given household still complies
   * with all the requirements for a proper household entity
   */
  function checkHousehold($household_id) {
    error_log("checking household $household_id");
    $problems_identified = array();

    // load household
    $household = civicrm_api3('Contact', 'getsingle', array('id' => $household_id));

    // load members
    $members = $this->getMembers($household_id);
    
    // CHECK 1: number of members
    if (count($members) < CRM_Householdmerge_Logic_Configuration::getMinimumMemberCount()) {
      if (count($members) == 0) {
        $problems_identified[] = ts("Household has no members any more.", array('domain' => 'de.systopia.householdmerge'));
      } else {
        $problems_identified[] = ts("Household has only %1 members left.", array(1 => count($members), 'domain' => 'de.systopia.householdmerge'));
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
        $problems_identified[] = ts("Household has no head any more.", array('domain' => 'de.systopia.householdmerge'));
      }

      // CHECK 3: is there more than one head?
      if (count($heads) > 1) {
        $problems_identified[] = ts("Household has multiple heads.", array('domain' => 'de.systopia.householdmerge'));
      }

      // CHECK 4: does the head have a DO NOT mail/phone/sms/email
      $donts = CRM_Householdmerge_Logic_Configuration::getDontXXXChecks();
      foreach ($heads as $head) {
        foreach ($donts as $field_name) {
          if (!empty($head[$field_name])) {
            $problems_identified[] = ts("Household head has one of the 'do not contact' attributes set.", array('domain' => 'de.systopia.householdmerge'));
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
            $problems_identified[] = ts("Household head has tag '%1'.", array(1 => $tag, 'domain' => 'de.systopia.householdmerge'));
          }
        }
      }

      // CHECK 6: is the head also head of another household?
      // TODO
    }

    // all member checks

    // CHECK 7: does one of the members not have the household address any more?
    // TODO


    // CHECK 8: Is there a potential new member for this household?
    // TODO


    if (!empty($problems_identified)) {
      // $this->createActivity($household, $problems_identified, $members);
      // error_log(print_r($problems_identified,1));
    }
  }


  /**
   * Load all the household members
   */
  protected function getMembers($household_id) {
    
    // TODO
  }


  /**
   * get the activty type ID for the "Check Households" activity
   */
  protected function getCheckActivityTypeID() {
    if ($this->_activity_type_id == NULL) {
      $this->_activity_type_id = CRM_Householdmerge_Logic_Configuration::getCheckHouseholdActivityType();
    }

    if ($this->_activity_type_id == NULL) {
      throw new API_Exception("Couldn't find activity type for 'check household' activity.");
    }

    return $this->_activity_type_id;
  }

  /**
   * get the activty status IDs that are considered to be relevant for skipping
   * 
   * @return string  comma separated ids
   */
  protected function getActiveActivityStatusIDs() {
    if ($this->_activity_status_ids == NULL) {
      $status_ids = array();
      $status_ids[] = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name');
      $status_ids[] = CRM_Core_OptionGroup::getValue('activity_status', 'Not Required', 'name');
      $this->_activity_status_ids = implode(',', $status_ids);
    }

    return $this->_activity_status_ids;
  }

  /**
   * Create a new activity with the 
   */
  protected function createActivity($household, $problems, $members) {
    // render the content
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->pushScope(array(
      'household'  => $household,
      'problems'   => $problems,
      'members'    => $members,
      ));
    $activity_content = $smarty->fetch('CRM/Householdmerge/Checker/Activity.tpl');
    $smarty->popScope();

    // compile activity
    $activity_data = array();
    $activity_data['subject']            = ts("Address Cleanup Failed", array('domain' => 'de.systopia.householdmerge'));
    $activity_data['details']            = $activity_content;
    $activity_data['activity_date_time'] = date("Ymdhis");
    $activity_data['activity_type_id']   = $this->getCheckActivityTypeID();
    $activity_data['status_id']          = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name');
    $activity_data['target_contact_id']  = (int) $household['id'];
    $activity_data['source_contact_id']  = (int) $household['id'];

    $activity = CRM_Activity_BAO_Activity::create($activity_data);
    if (empty($activity->id)) {
      $this->metadata['application_errors'][] = dpaf_ts("Couldn't create activity for household [%1]", array(1=>$household['id']));
    }

  }
}
