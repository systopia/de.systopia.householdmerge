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

class CRM_Householdmerge_Logic_Scanner {

  function __construct() {
  }

  /**
   * find a number of prposed households.
   *
   * @return: array(array( 'id'           => string
   *                       'household_id' => int, 
   *                       'member_ids'   => array(int), 
   *                       'head_id'      => int, 
   *                       'contacts'     => array(int=>contact_data))
   */
  public function findNewHouseholds($count) {
    $proposals = array();

    // first, find candidates (will return only one ID)
    $candidates = $this->findCandidateIDs($count);

    // then go through all candidates
    foreach ($candidates as $contact_id) {
      $proposal = $this->investigateCandidate($contact_id);
      if ($proposal) {
        $proposals[] = $proposal;
      }
    }

    return $proposals;
  }

  /**
   * see if this could be a household
   *
   * @return array see findNewHouseholds()
   */
  protected function investigateCandidate($contact_id) {
    $template = civicrm_api3('Contact', 'getsingle', array('id' => $contact_id));

    // load all members
    $member_result = civicrm_api3('Contact', 'get', array(
      'last_name'      => $template['last_name'],
      'street_address' => $template['street_address'],
      'postal_code'    => $template['postal_code'],
      'city'           => $template['city'],
      ));

    // stop here if there's not enough
    if ($member_result['count'] < CRM_Householdmerge_Logic_Configuration::getMinimumMemberCount()) {
      return NULL;
    }
    $members = $member_result['values'];

    
    // TODO: check if they are already connected to a household


    $candidate = array(
      'household_id' => 0,
      'head_id'      => 0,
      'member_ids'   => array(),
      'contacts'     => array()
      );
    foreach ($members as $member_id => $member) {
      $candidate['member_ids'][] = $member['id'];
      $candidate['contacts'][$member['id']] = $member;
    }

    if ('hierarchy' == CRM_Householdmerge_Logic_Configuration::getHouseholdMode()) {
      // we need to identify the HEAD
      $head_id = $this->identifyHead($members);
      $candidate['head_id'] = $head_id;
      // remeove head from member_ids
      $index = array_search($head_id, $candidate['member_ids']);
      unset($candidate['member_ids'][$index]);
    }

    return $candidate;
  }

  /**
   * find the contact_ids of some potential candidates
   */
  protected function findCandidateIDs($count) {
    $count = (int) $count;
    $minimum_member_count = (int) 2;

    $candidate_ids = array();
    // TODO: THAT ARE NOT MEMBERS OF...
    $scanner_sql = "
      SELECT *
        FROM (SELECT civicrm_contact.id AS contact_id,
                     COUNT(DISTINCT(civicrm_contact.id)) AS mitgliederzahl
              FROM civicrm_contact
              LEFT JOIN civicrm_address ON civicrm_address.contact_id = civicrm_contact.id
              WHERE (civicrm_contact.is_deleted IS NULL OR civicrm_contact.is_deleted = 0)
                AND civicrm_contact.contact_type = 'Individual'
                AND civicrm_address.id IS NOT NULL
              GROUP BY civicrm_contact.last_name, civicrm_address.street_address, civicrm_address.postal_code, civicrm_address.city) households
        WHERE mitgliederzahl >= $minimum_member_count
        LIMIT $count;
      ";
    $scanner = CRM_Core_DAO::executeQuery($scanner_sql);
    while ($scanner->fetch()) {
      $candidate_ids[] = $scanner->contact_id;
    }
    return $candidate_ids;
  }


  /**
   * Identify the contact to be considered the HEAD under the given member_data objects
   * 
   * @return int  contact_id of the head
   */
  protected function identifyHead(&$members) {
    $method = CRM_Householdmerge_Logic_Configuration::getHouseholdHeadMode();
    if ($method == 'topdonor2y_m') {

      // init donations array
      $donations = array();
      foreach ($members as $member_id => $member) {
        $donations[$member_id] = 0;
      }

      $contact_ids = implode(',', array_keys($members));
      $td_amounts_sql = "
          SELECT contact_id AS contact_id, 
                 SUM(total_amount) AS amount
           FROM civicrm_contribution
          WHERE contact_id IN ($contact_ids)
            AND (is_test IS NULL OR is_test = 0)
            AND (contribution_status_id = 2)
            AND (receive_date BETWEEN (NOW() - INTERVAL 2 YEAR) AND NOW())
          GROUP BY contact_id;
          ";
      $td_amounts = CRM_Core_DAO::executeQuery($td_amounts_sql);
      while ($td_amounts->fetch()) {
        $donations[$td_amounts->contact_id] = $td_amounts->amount;
      }

      // now determin the head
      $topdonor_id = NULL;
      $topdonor_amount = NULL;

      foreach ($donations as $member_id => $donation_amount) {
        if ($donation_amount > $topdonor_amount  || $topdonor_amount===NULL) {
          $topdonor_id     = $member_id;
          $topdonor_amount = $donation_amount;
        } elseif ($donation_amount == $topdonor_amount) {
          if ($members[$member_id]['gender_id'] == 2) {
            // if donor has same amount and is male => take over
            $topdonor_id     = $member_id;
            $topdonor_amount = $donation_amount;
          }
        }
      }

      // now $topdonor_id should be the HEAD
      return $topdonor_id;

    } else {
      error_log("UNDEFINED METHOD TO DETERMINE HEAD: $method");
      return reset(array_keys($members));
    }
  }
}
