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

/*
 * Helper methods
 */
class CRM_Householdmerge_Logic_Util {
  // flag to mark a pending household merge
  private static $_isHouseholdMerge = FALSE;

  /*
   * Returns whether there is a pending household merge
   * @return boolean
   */
  public static function isMerge() {
    return self::$_isHouseholdMerge;
  }

  public static function enableMerge() {
    self::$_isHouseholdMerge = TRUE;
  }

  public static function disableMerge() {
    self::$_isHouseholdMerge = FALSE;
  }

  /*
   * Toggles the current merge status
   */
  public static function toggleMerge() {
    self::$_isHouseholdMerge = !$self::$_isHouseholdMerge;
  }
}
