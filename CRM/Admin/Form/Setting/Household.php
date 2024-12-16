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

require_once 'CRM/Core/Form.php';

use CRM_Householdmerge_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Admin_Form_Setting_Household extends CRM_Admin_Form_Setting {
  public function buildQuickForm() {
    // load relationships
    $relationshipOptions = $this->getEligibleRelationships();

    $this->addElement('select',
                    'hh_mode',
                    E::ts('Household Mode'),
                    CRM_Householdmerge_Logic_Configuration::getHouseholdModeOptions(),
                    array('class' => 'crm-select2 huge'));

    $this->addElement('select',
                    'hh_head_mode',
                    E::ts('Household Head Mode'),
                    CRM_Householdmerge_Logic_Configuration::getHouseholdHeadModeOptions(),
                    array('class' => 'crm-select2 huge'));

    $this->addElement('select',
                    'hh_member_relation',
                    E::ts('Household Member Relationship'),
                    $relationshipOptions,
                    array('class' => 'crm-select2 huge'));

    $this->addElement('select',
                    'hh_head_relation',
                    E::ts('Household Head Relationship'),
                    $relationshipOptions,
                    array('class' => 'crm-select2 huge'));

    $this->addElement('select',
                    'hh_location_types',
                    E::ts('Address Location Types to consider'),
                    CRM_Householdmerge_Logic_Configuration::getLocationTypeOptions(),
                    ['class' => 'crm-select2 huge', 'multiple' => true, 'placeholder' => E::ts('all location types')]);

    parent::buildQuickForm();
  }

  /**
   * preset the current values as default
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    // add our defaults
    $defaults['hh_mode']            = CRM_Householdmerge_Logic_Configuration::getHouseholdMode();
    $defaults['hh_head_mode']       = CRM_Householdmerge_Logic_Configuration::getHouseholdHeadMode();
    $defaults['hh_member_relation'] = CRM_Householdmerge_Logic_Configuration::getMemberRelationID();
    $defaults['hh_head_relation']   = CRM_Householdmerge_Logic_Configuration::getHeadRelationID();
    $defaults['hh_location_types']  = CRM_Householdmerge_Logic_Configuration::getSelectedLocationTypes();

    return $defaults;
  }

  public function postProcess() {
    $values = $this->exportValues();

    // store settings
    $expected_values = array('hh_mode', 'hh_head_mode', 'hh_member_relation', 'hh_head_relation', 'relationship_type', 'hh_location_types');
    foreach ($expected_values as $key) {
      if (isset($values[$key])) {
        CRM_Householdmerge_Logic_Configuration::setConfigValue($key, $values[$key]);
      }
    }
  }


  /**
   * load all Individual<->Household Relationships
   */
  protected function getEligibleRelationships() {
    $relationship_types = [];

    $list_ab = civicrm_api3('RelationshipType', 'get', array('contact_type_a' => 'Individual', 'contact_type_b' => 'Household'));
    foreach ($list_ab['values'] as $index => $relationship_type) {
      $relationship_types[$relationship_type['id']] = $relationship_type['label_a_b'];
    }
    $list_ba = civicrm_api3('RelationshipType', 'get', array('contact_type_b' => 'Individual', 'contact_type_a' => 'Household'));
    foreach ($list_ba['values'] as $index => $relationship_type) {
      $relationship_types[$relationship_type['id']] = $relationship_type['label_b_a'];
    }

    return $relationship_types;
  }
}
