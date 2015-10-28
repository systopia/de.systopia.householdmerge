<?php

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
