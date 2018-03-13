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

class CRM_Householdmerge_Logic_Worker {

  /** will store information on the relation types */
  protected $_relationID2fields = array();

  /** will store information on the location types to be used for the address */
  protected $_household_address_location_type_id = NULL;

  function __construct() {
  }


  /**
   * Will create a household
   * linking the member IDs (and head ID if present) to it.
   *
   * @return int household_id
   */
  public function createLinkedHousehold($last_name, $member_ids, $household_address, $head_id = NULL) {
    // create household
    $household = civicrm_api3('Contact', 'create', array(
      'contact_type'   => 'Household',
      'household_name' => $last_name,
    ));
    $household_id = $household['id'];

    // also, create the address
    if ($household_address) {
      $address = civicrm_api3('Address', 'create', array(
        'contact_id'             => $household_id,
        'location_type_id'       => $this->getHHAddressLocationTypeID(),
        'street_address'         => $household_address['street_address'],
        'supplemental_address_1' => $household_address['supplemental_address_1'],
        'supplemental_address_2' => $household_address['supplemental_address_2'],
        'city'                   => $household_address['city'],
        'postal_code'            => $household_address['postal_code'],
        'country_id'             => $household_address['country_id'],
        ));
    }

    // link head (if present)
    if ($head_id) {
      $head_relation_id = CRM_Householdmerge_Logic_Configuration::getHeadRelationID();
      if (!$head_relation_id) throw new CRM_Core_Exception("Cannot create Household: head realation not set.");
      $this->linkContactToHousehold($head_id, $household_id, $head_relation_id);
    }

    // link members
    $member_relation_id = CRM_Householdmerge_Logic_Configuration::getMemberRelationID();
    foreach ($member_ids as $member_id) {
      $this->linkContactToHousehold($member_id, $household_id, $member_relation_id);
    }

    return $household_id;
  }


  /**
   * Will link the contact to the given household
   * If $relation_type_id is empty, the default member relation will be used
   */
  public function linkContactToHousehold($contact_id, $household_id, $relation_type_id = NULL) {
    if (!$relation_type_id) {
      $relation_type_id = CRM_Householdmerge_Logic_Configuration::getMemberRelationID();
    }

    // look up the right parameters (direction)
    if (!isset($this->_relationID2fields[$relation_type_id])) {
      $relation_type = civicrm_api3('RelationshipType', 'getsingle', array('id' => $relation_type_id));
      if ($relation_type['contact_type_a'] == 'Household') {
        $this->_relationID2fields[$relation_type_id] = array(
          'household' => 'contact_id_a',
          'contact'   => 'contact_id_b');
      } else {
        $this->_relationID2fields[$relation_type_id] = array(
          'household' => 'contact_id_b',
          'contact'   => 'contact_id_a');
      }
    }

    // finally create the relationship
    $rspec = $this->_relationID2fields[$relation_type_id];
    civicrm_api3('Relationship', 'create', array(
      'relationship_type_id' => $relation_type_id,
      $rspec['household']    => $household_id,
      $rspec['contact']      => $contact_id,
      'is_active'            => 1,
    ));
  }

  /**
   * get the location type ID to be used for the new household's address
   */
  public function getHHAddressLocationTypeID() {
    if ($this->_household_address_location_type_id == NULL) {
      $types = civicrm_api3('LocationType', 'get', array('is_default' => 1));
      if ($types['count'] == 0) {
        $types = civicrm_api3('LocationType', 'get', array());
      }
      $type = reset($types['values']);
      $this->_household_address_location_type_id = $type['id'];
    }
    return $this->_household_address_location_type_id;
  }
}
