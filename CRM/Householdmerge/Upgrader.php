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

use CRM_Householdmerge_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Householdmerge_Upgrader extends CRM_Householdmerge_Upgrader_Base {

  /**
   * Migrate individual settings into bucket
   */
  public function upgrade_0102() {
    $this->ctx->log->info('Planning update 0120');

    // create a new setting
    $settings = CRM_Householdmerge_Logic_Configuration::getSettings();
    if (empty($settings)) {
      // migrate settings
      $settings['hh_head_mode']       = Civi::settings()->get('hh_head_mode') ?? 'topdonor2y_m';
      $settings['hh_mode']            = Civi::settings()->get('hh_mode') ?? 'merge';
      $settings['hh_member_relation'] = Civi::settings()->get('hh_member_relation');
      $settings['hh_head_relation']   = Civi::settings()->get('hh_head_relation');
      Civi::settings()->set('householdmerge', $settings);
    }

    return TRUE;
  }
}
