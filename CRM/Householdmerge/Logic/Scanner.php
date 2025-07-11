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

class CRM_Householdmerge_Logic_Scanner {

  protected $_lastID = 0;

  function __construct() {
  }

  /**
   * find a number of prposed households.
   *
   * @return: array(array( 'id'           => string
   *                       'household_id' => int,
   *                       'member_ids'   => array(int),
   *                       'head_id'      => int,
   *                       'address'      => array(<address data>)
   *                       'contacts'     => array(int=>contact_data)
   */
  public function findNewHouseholds($count) {
    $proposals = [];

    // first, find candidates
    $candidates = $this->findCandidates($count);

    // then go through all candidates
    foreach ($candidates as $contact_id => $signature) {
      $proposal = $this->investigateCandidate($contact_id, $signature);
      if ($proposal) {
        $proposals[$proposal['id']] = $proposal;
      }
    }

    return $proposals;
  }

  /**
   * see if this could be a household
   *
   * @return array see findNewHouseholds()
   */
  protected function investigateCandidate($contact_id, $signature) {
    // signature last name should at least have two letters
    if (!preg_match("/\\w.*\\w/", $signature['last_name'])) return NULL;

    // compile members
    $contact_ids   = explode('||', $signature['contact_ids']);
    $display_names = explode('||', $signature['display_names']);
    $gender_ids    = explode('||', $signature['gender_ids']);
    $members = [];
    for ($i=0; $i < count($contact_ids); $i++) {
      $contact_id = $contact_ids[$i];
      $members[$contact_id] = array(
        'id' => $contact_id,
        'display_name'           => $display_names[$i],
        'gender_id'              => $gender_ids[$i],
        'last_name'              => $signature['last_name'],
        'street_address'         => $signature['street_address'],
        'supplemental_address_1' => $signature['supplemental_address_1'],
        'supplemental_address_2' => $signature['supplemental_address_2'],
        'postal_code'            => $signature['postal_code'],
        'city'                   => $signature['city'],
        'country_id'             => $signature['country_id'],
      );
    }

    // stop here if there's not enough
    if (count($members) < CRM_Householdmerge_Logic_Configuration::getMinimumMemberCount()) {
      return NULL;
    }

    $candidate = array(
      'id'             => $this->createID(),
      'household_id'   => 0,
      'head_id'        => 0,
      'household_name' => '',
      'member_ids'     => [],
      'contacts'       => [],
      'address'        => array('street_address'         => $signature['street_address'],
                                'supplemental_address_1' => $signature['supplemental_address_1'],
                                'supplemental_address_2' => $signature['supplemental_address_2'],
                                'postal_code'            => $signature['postal_code'],
                                'city'                   => $signature['city'],
                                'country_id'             => $signature['country_id']),
      );

    foreach ($members as $member_id => $member) {
      $candidate['member_ids'][] = $member['id'];
      $candidate['contacts'][$member['id']] = $member;
      $candidate['household_name'] = $member['last_name'];
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
  protected function findCandidates($count) {
    $minimum_member_count = (int) CRM_Householdmerge_Logic_Configuration::getMinimumMemberCount();
    if ($count == 'all') {
      $limit_clause = '';
    } else {
      $count = (int) $count;
      $limit_clause = "LIMIT $count";
    }

    // compile relationship conditions ("NOT ALREADY MEMBER OF")
    $RELATIONSHIP_CONDITION = $RELATIONSHIP_JOIN = '';
    $member_relation_id = CRM_Householdmerge_Logic_Configuration::getMemberRelationID();
    $head_relation_id = CRM_Householdmerge_Logic_Configuration::getHeadRelationID();
    if ($member_relation_id) {
      $relationship_ids = array($member_relation_id);
      if ($head_relation_id) {
        $relationship_ids[] = $head_relation_id;
      }
      $relationship_id_list = implode(',', $relationship_ids);

      //  try joining valid, active household relationships...
      $RELATIONSHIP_JOIN = "
              LEFT JOIN civicrm_relationship relation_ab ON contact_id = relation_ab.contact_id_a
                                                         AND relation_ab.relationship_type_id IN ($relationship_id_list)
                                                         AND (relation_ab.end_date IS NULL OR relation_ab.end_date > NOW())
                                                         AND relation_ab.is_active = 1
                                                         AND (relation_ab.contact_id_b IN (SELECT id FROM civicrm_contact WHERE is_deleted = 0))
              LEFT JOIN civicrm_relationship relation_ba ON contact_id = relation_ba.contact_id_b
                                                         AND relation_ba.relationship_type_id IN ($relationship_id_list)
                                                         AND (relation_ba.end_date IS NULL OR relation_ba.end_date > NOW())
                                                         AND relation_ba.is_active = 1
                                                         AND (relation_ba.contact_id_a IN (SELECT id FROM civicrm_contact WHERE is_deleted = 0))
        ";

      // ...and then make sure there are none
      $RELATIONSHIP_CONDITION = "
              AND relation_ab.id IS NULL AND relation_ba.id IS NULL  -- NO ACTIVE HH RELATIONSHIP EXISTS
        ";
    }

    // add location_type restrictions (if any)
    $location_types = CRM_Householdmerge_Logic_Configuration::getSelectedLocationTypes();
    if ($location_types) {
      $location_type_term = implode(',', $location_types);
      $AND_LOCATION_TYPE_CONSIDERED = "AND civicrm_address.location_type_id IN ({$location_type_term})";
    } else {
      $AND_LOCATION_TYPE_CONSIDERED = '';
    }

    $candidates = [];
    $scanner_sql = "
      SELECT *
        FROM (SELECT civicrm_contact.id AS contact_id,
                     GROUP_CONCAT(civicrm_contact.id SEPARATOR '||') AS contact_ids,
                     GROUP_CONCAT(civicrm_contact.display_name SEPARATOR '||') AS display_names,
                     GROUP_CONCAT(civicrm_contact.gender_id SEPARATOR '||') AS gender_ids,
                     civicrm_contact.last_name AS last_name,
                     civicrm_address.street_address AS street_address,
                     civicrm_address.supplemental_address_1 AS supplemental_address_1,
                     civicrm_address.supplemental_address_2 AS supplemental_address_2,
                     civicrm_address.postal_code AS postal_code,
                     civicrm_address.city AS city,
                     civicrm_address.country_id AS country_id,
                     COUNT(DISTINCT(civicrm_contact.id)) AS mitgliederzahl
              FROM civicrm_contact
              LEFT JOIN civicrm_address ON civicrm_address.contact_id = civicrm_contact.id
              $RELATIONSHIP_JOIN
              WHERE (civicrm_contact.is_deleted IS NULL OR civicrm_contact.is_deleted = 0)
                AND civicrm_contact.contact_type = 'Individual'
                AND civicrm_address.id IS NOT NULL
                AND (civicrm_address.street_address IS NOT NULL AND civicrm_address.street_address != '')
                AND (civicrm_address.postal_code IS NOT NULL AND civicrm_address.postal_code != '')
                AND (civicrm_address.city IS NOT NULL AND civicrm_address.city != '')
                {$AND_LOCATION_TYPE_CONSIDERED}
                {$RELATIONSHIP_CONDITION}
              GROUP BY civicrm_contact.last_name, civicrm_address.street_address, civicrm_address.postal_code, civicrm_address.city) households
        WHERE mitgliederzahl >= $minimum_member_count
        $limit_clause;
      ";
     //CRM_Core_Error::debug_log_message("Scanner Query:\n" . $scanner_sql);
    $scanner = CRM_Core_DAO::executeQuery($scanner_sql);
    while ($scanner->fetch()) {
      $candidates[$scanner->contact_id] = array(
        'contact_ids'            => $scanner->contact_ids,
        'display_names'          => $scanner->display_names,
        'gender_ids'             => $scanner->gender_ids,
        'street_address'         => $scanner->street_address,
        'supplemental_address_1' => $scanner->supplemental_address_1,
        'supplemental_address_2' => $scanner->supplemental_address_2,
        'postal_code'            => $scanner->postal_code,
        'city'                   => $scanner->city,
        'country_id'             => $scanner->country_id,
        'last_name'              => $scanner->last_name);
    }
    return $candidates;
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
      $donations = [];
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
            AND (contribution_status_id = 1)
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
        if ($donation_amount > $topdonor_amount || $topdonor_amount===NULL) {
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
      $member_keys = array_keys($members);
      return reset($member_keys);
    }
  }


  /**
   * Create a new unique ID for the candidates
   */
  protected function createID() {
    $this->_lastID++;
    return "hhcandidate{$this->_lastID}";
  }
}
