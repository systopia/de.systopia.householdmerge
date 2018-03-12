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

    $activity_type_id    = CRM_Householdmerge_Logic_Configuration::getCheckHouseholdActivityTypeID();
    $activity_status_ids = CRM_Householdmerge_Logic_Configuration::getLiveActivityStatusIDs();

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
      CRM_Core_BAO_Setting::setItem('0', CRM_Householdmerge_Logic_Configuration::$HHMERGE_SETTING_DOMAIN, 'hh_check_last_id');
    } else {
      CRM_Core_BAO_Setting::setItem($last_contact_id_processed, CRM_Householdmerge_Logic_Configuration::$HHMERGE_SETTING_DOMAIN, 'hh_check_last_id');
    }

    return;
  }


  /**
   * investigates if the given household still complies
   * with all the requirements for a proper household entity
   *
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
        $problems_identified[] = CRM_Householdmerge_Logic_Problem::createProblem('HOM0', $household_id);
      } else {
        $problems_identified[] = CRM_Householdmerge_Logic_Problem::createProblem('HOMX', $household_id, array('count' => count($members)));
      }
    }

    // HEAD related checks
    if ('hierarchy' == CRM_Householdmerge_Logic_Configuration::getHouseholdMode()) {
      $heads = array();
      foreach ($members as $member) {
        if ($member['hh_relation'] == 'head') {
          $heads[] = $member;
        }
      }

      // CHECK 2: is there still a head?
      if (empty($heads)) {
        $problems_identified[] = CRM_Householdmerge_Logic_Problem::createProblem('HHN0', $household_id);
      }

      // CHECK 3: is there more than one head?
      if (count($heads) > 1) {
        $problems_identified[] = CRM_Householdmerge_Logic_Problem::createProblem('HHN2', $household_id);
      }

      // CHECK 4: does the head have a DO NOT mail/phone/sms/email
      $donts = CRM_Householdmerge_Logic_Configuration::getDontXXXChecks();
      foreach ($heads as $head) {
        foreach ($donts as $field_name) {
          if (!empty($head[$field_name])) {
            $problems_identified[] = CRM_Householdmerge_Logic_Problem::createProblem('HHNC', $household_id);
            break;
          }
        }
      }

      // CHECK 5: does the head have certain tags
      $bad_tags = CRM_Householdmerge_Logic_Configuration::getBadHeadTags();
      foreach ($heads as $head) {
        $tags = CRM_Core_BAO_EntityTag::getContactTags($head['id']);
        foreach ($tags as $tag) {
          if (in_array($tag, $bad_tags)) {
            $problems_identified[] = CRM_Householdmerge_Logic_Problem::createProblem('HHTG', $household_id, array('tag' => $tag));
          }
        }
      }

      // CHECK 6: is the head also head of another household?
      foreach ($heads as $head) {
        $head_relation_id = CRM_Householdmerge_Logic_Configuration::getHeadRelationID();
        $relationships_a  = civicrm_api3('Relationship', 'get', array('contact_id_a' => $head['id'], 'relationship_type_id' => $head_relation_id, 'is_active' => 1));
        $relationships_b  = civicrm_api3('Relationship', 'get', array('contact_id_b' => $head['id'], 'relationship_type_id' => $head_relation_id, 'is_active' => 1));
        if ($relationships_a['count'] + $relationships_b['count'] > 1) {
          $problems_identified[] = CRM_Householdmerge_Logic_Problem::createProblem('HHMM', $household_id);
        }
      }
    }

    // all member checks

    // CHECK 7: does one of the members not have the household address any more?
    $this->checkAddresses($household, $members, $problems_identified);

    // CHECK 8: Is there a potential new member for this household?
    $this->findNewMembers($household, $members, $problems_identified);

    // if (!empty($problems_identified)) {
    //   $this->createActivity($household, $problems_identified, $members);
    // }
    foreach ($problems_identified as $problem) {
      $problem->createActivity();
    }
  }



  /**
   * Check if all these members still have the same address as the household
   */
  protected function checkAddresses(&$household, &$members, &$problems_identified) {
    if (empty($members)) return;

    if (empty($household['street_address']) && empty($household['postal_code']) && empty($household['city'])) {
      // not enough information...
      return;
    }

    // build a list of all members...
    $member_ids = array();
    foreach ($members as $member) {
      if (!in_array($member['id'], $member_ids)) {
        $member_ids[] = $member['id'];
      }
    }

    // ...and remove all members that still share the household's main address
    $addresses = civicrm_api3('Address', 'get', array('contact_id' => array('IN' => $member_ids), 'option.limit' => 999999));
    foreach ($addresses['values'] as $address) {
      if (  $address['city'] == $household['city']
        &&  $address['street_address'] == $household['street_address']
        &&  $address['postal_code'] == $household['postal_code']
        &&  in_array($address['contact_id'], $member_ids)) {

        // this contact still has/shares this address, remove him/her from the list
        unset($member_ids[array_search($address['contact_id'], $member_ids)]);
      }
    }

    // every contact that's still on the list should NOT have the address any more
    foreach ($member_ids as $member_id) {
      $problems_identified[] = CRM_Householdmerge_Logic_Problem::createProblem('HMBA', $household['id'], array('member_id' => $member_id));
    }
  }


  /**
   * identify potential new household members
   */
  protected function findNewMembers(&$household, &$members, &$problems_identified) {
    if (  empty($household['household_name'])
       || empty($household['street_address'])
       || empty($household['postal_code'])
       || empty($household['city'])) {
      // not enough information...
      return;
    }

    $member_relation_id = CRM_Householdmerge_Logic_Configuration::getMemberRelationID();
    $head_relation_id   = CRM_Householdmerge_Logic_Configuration::getHeadRelationID();
    if (!$member_relation_id) return;
    $relationship_ids = array($member_relation_id);
    if ($head_relation_id) $relationship_ids[] = $head_relation_id;
    $relationship_id_list = implode(',', $relationship_ids);

    $search_sql = "SELECT DISTINCT(civicrm_contact.id) AS contact_id
                     FROM civicrm_contact
                     LEFT JOIN civicrm_address ON civicrm_address.contact_id = civicrm_contact.id
                     WHERE (civicrm_contact.is_deleted IS NULL OR civicrm_contact.is_deleted = 0)
                       AND civicrm_contact.contact_type = 'Individual'
                       AND civicrm_contact.last_name = %1
                       AND civicrm_address.street_address = %2
                       AND civicrm_address.postal_code = %3
                       AND civicrm_address.city = %4
                       AND NOT EXISTS (SELECT id
                                         FROM civicrm_relationship
                                        WHERE (contact_id_a = civicrm_contact.id OR contact_id_b = civicrm_contact.id)
                                          AND (relationship_type_id IN ($relationship_id_list))
                                          AND (end_date IS NULL OR end_date > NOW())
                                          AND (is_active = 1)
                                        );";
    $queryParameters = array(
      1 => array($household['household_name'], 'String'),
      2 => array($household['street_address'], 'String'),
      3 => array($household['postal_code'], 'String'),
      4 => array($household['city'], 'String'),
    );
    $new_members = CRM_Core_DAO::executeQuery($search_sql, $queryParameters);
    while ($new_members->fetch()) {
      $problems_identified[] = CRM_Householdmerge_Logic_Problem::createProblem('HMNW', $household['id'], array('member_id' => $new_members->contact_id));
    }
  }




  /**
   * Load all the household members
   */
  protected function getMembers($household_id) {
    $member_relation_id = CRM_Householdmerge_Logic_Configuration::getMemberRelationID();
    $head_relation_id   = CRM_Householdmerge_Logic_Configuration::getHeadRelationID();
    $head_ids   = array();
    $member_ids = array();

    // load the relationships (both ways)
    $query = array(
      'relationship_type_id' => array('IN' => array($member_relation_id, $head_relation_id)),
      'is_active'            => 1,
      'contact_id_a'         => $household_id,
      'option.limit'         => 99999
      );
    $member_query = civicrm_api3('Relationship', 'get', $query);
    foreach ($member_query['values'] as $relationship) {
      $member_ids[] = $relationship['contact_id_b'];
      if ($relationship['relationship_type_id'] == $head_relation_id) {
        $head_ids[] = $relationship['contact_id_b'];
      }
    }
    $query['contact_id_b'] = $household_id;
    unset($query['contact_id_a']);
    $member_query = civicrm_api3('Relationship', 'get', $query);
    foreach ($member_query['values'] as $relationship) {
      $member_ids[] = $relationship['contact_id_a'];
      if ($relationship['relationship_type_id'] == $head_relation_id) {
        $head_ids[] = $relationship['contact_id_a'];
      }
    }

    if (!empty($member_ids)) {
      // and load the memeber contacts
      $contact_query = civicrm_api3('Contact', 'get', array(
          'id'           => array('IN' => $member_ids),
          'contact_type' => 'Individual',
          'is_deleted'   => 0));
      $members = $contact_query['values'];

      // set the relationship type
      foreach ($members as &$member) {
        if (in_array($member['id'], $head_ids)) {
          $member['hh_relation'] = 'head';
        } else {
          $member['hh_relation'] = 'member';
        }
      }

      return $members;
    }

    return array();
  }

}
