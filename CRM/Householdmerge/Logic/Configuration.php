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

class CRM_Householdmerge_Logic_Configuration {

  /**
   * will return the configured household mode:
   *  'merge'     - the individual contacts will be removed, only the household contact remains
   *  'link'      - the individual contacts will linked to the household as "members"
   *  'hierarchy' - one individual contact will be linked to the household as "head", the rest as "members"
   *
   * @return string
   */
  public static function getHouseholdMode() {
    // TODO: setting
    return 'hierarchy';
  }


  /**
   * will return the configured algorithm to determine the household head
   *  'topdonor2y_m' - the contact with the most contributions in the past 2 years will become head
   *                   in case it's a draw, male over female
   *
   * @return string
   */
  public static function getHouseholdHeadMode() {
    // TODO: setting
    return 'topdonor2y_m';
  }

  /**
   * get the minimal amount of members to be counted as a household
   */
  public static function getMinimumMemberCount() {
    // TODO: setting
    return 2;
  }


  /**
   * get the relation ID of the Member relation
   */
  public static function getMemberRelationID() {
    // TODO: setting
    return 2;
  }

  /**
   * get the relation ID of the HEAD relation
   */
  public static function getHeadRelationID() {
    // TODO: setting
    return 1;  
  }
}
