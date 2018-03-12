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

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Householdmerge_Form_Task_Fix extends CRM_Activity_Form_Task {

  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts("Automatic Correction of Household Problems", array('domain' => 'de.systopia.householdmerge')));
    $this->addDefaultButtons(ts("Try to fix", array('domain' => 'de.systopia.householdmerge')), 'done');

    // calculate some stats
    $activity_type_id    = (int) CRM_Householdmerge_Logic_Configuration::getCheckHouseholdActivityTypeID();
    $activity_status_ids = CRM_Householdmerge_Logic_Configuration::getFixableActivityStatusIDs();
    $activity_ids        = implode(',', $this->_activityHolderIds);
    $stats_query = "SELECT COUNT(id) AS activity_count,
                           COUNT(DISTINCT(SUBSTRING(civicrm_activity.subject FROM 1 FOR 6))) AS activity_class_count
                    FROM   civicrm_activity
                     WHERE civicrm_activity.activity_type_id = $activity_type_id
                       AND civicrm_activity.status_id IN ($activity_status_ids)
                       AND civicrm_activity.id IN ($activity_ids);";
    $stats = CRM_Core_DAO::executeQuery($stats_query);
    $stats->fetch();

    $this->assign('total_activities',    count($this->_activityHolderIds));
    $this->assign('relevant_activities', $stats->activity_count);
    $this->assign('class_count',         $stats->activity_class_count);

    parent::buildQuickForm();
  }


  function postProcess() {
    // define some stats
    $activities_total     = count($this->_activityHolderIds);
    $activities_processed = 0;
    $activities_detected  = 0;
    $activities_fixed     = 0;

    // filter for relevant activities
    $activity_type_id    = (int) CRM_Householdmerge_Logic_Configuration::getCheckHouseholdActivityTypeID();
    $activity_status_ids = CRM_Householdmerge_Logic_Configuration::getFixableActivityStatusIDs();
    $activity_ids        = implode(',', $this->_activityHolderIds);
    $filter_query        = "SELECT id AS activity_id FROM civicrm_activity
                     WHERE civicrm_activity.activity_type_id = $activity_type_id
                       AND civicrm_activity.status_id IN ($activity_status_ids)
                       AND civicrm_activity.id IN ($activity_ids);";
    $filtered_activities = CRM_Core_DAO::executeQuery($filter_query);

    // go through all activites and try to fix them
    while ($filtered_activities->fetch()) {
      $activities_processed += 1;
      $problem = CRM_Householdmerge_Logic_Problem::extractProblem($filtered_activities->activity_id);
      if ($problem) {
        $activities_detected += 1;
        if ($problem->fix()) {
          $activities_fixed += 1;
        }
      }
    }

    // show stats
    CRM_Core_Session::setStatus(
      ts('%1 of the %2 selected activities were processed, %3 of them could be fixed.', array(1 => $activities_detected, 2 => $activities_total, 3 => $activities_fixed, 'domain' => 'de.systopia.householdmerge')),
      ts('%1 Household Problems Fixed', array(1 => $activities_fixed, 'domain' => 'de.systopia.householdmerge')),
      ($activities_fixed > 0)? 'info' : 'warn');

    parent::postProcess();
  }

}
