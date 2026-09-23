<?php
/*-------------------------------------------------------+
| Household Merger Extension                             |
| Copyright (C) 2016-2018 SYSTOPIA                       |
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

class CRM_Householdmerge_Logic_Problem {

  /**
   * This is the list of all known problems
   */
  static $_problem_classes = NULL;

  public static function getProblemClasses() {
    if (self::$_problem_classes === NULL) {
      self::$_problem_classes = [
      'HOM0' => [
                  'code'  => 'HOM0',
                  'title' => ts("Household has no members any more", ['domain' => 'de.systopia.householdmerge']),
                  ],
      'HOMX' => [
                  'code'  => 'HOMX',
                  'title' => ts("Household has only {count} member(s) left", ['domain' => 'de.systopia.householdmerge']),
                  ],
      'HHN0' => [
                  'code'  => 'HHN0',
                  'title' => ts("Household has no head any more", ['domain' => 'de.systopia.householdmerge']),
                  ],
      'HHN2' => [
                  'code'  => 'HHN2',
                  'title' => ts("Household has multiple heads", ['domain' => 'de.systopia.householdmerge']),
                  ],
      'HHNC' => [
                  'code'  => 'HHNC',
                  'title' => ts("Household head has one of the 'do not contact' attributes set", ['domain' => 'de.systopia.householdmerge']),
                  ],
      'HHTG' => [
                  'code'  => 'HHTG',
                  'title' => ts("Household head has tag '{tag}'", ['domain' => 'de.systopia.householdmerge']),
                  ],
      'HHMM' => [
                  'code'  => 'HHMM',
                  'title' => ts("Household head is head of multiple households", ['domain' => 'de.systopia.householdmerge']),
                  ],
      'HMBA' => [
                  'code'  => 'HMBA',
                  'title' => ts("Household member does not share the household's address any more", ['domain' => 'de.systopia.householdmerge']),
                  ],
      'HMNW' => [
                  'code'  => 'HMNW',
                  'title' => ts("New household member detected", ['domain' => 'de.systopia.householdmerge']),
                  ],
      ];
    }
    return self::$_problem_classes;
  }

  /**
   * create a problem instance defined by the given code
   */
  public static function createProblem($code, $household_id, $params = []) {
    $problem_classes = self::getProblemClasses();
    if (isset($problem_classes[$code])) {
      return new CRM_Householdmerge_Logic_Problem($code, $household_id, $params);
    } else {
      // unknown problem code
      return NULL;
    }
  }

  /**
   * extract a problem from a given activity
   */
  public static function extractProblem($activity_id) {
    $activity = civicrm_api3('Activity', 'getsingle', ['id' => $activity_id]);
    if ($activity['activity_type_id'] != CRM_Householdmerge_Logic_Configuration::getCheckHouseholdActivityTypeID()) {
      return NULL;
    }

    $fixable_status_ids = explode(',', CRM_Householdmerge_Logic_Configuration::getFixableActivityStatusIDs());
    if (!in_array($activity['status_id'], $fixable_status_ids)) {
      return NULL;
    }

    $code = substr($activity['subject'], 1, 4);

    // TODO: load member at this point?
    return self::createProblem($code, $activity['source_contact_id'], ['activity_id' => $activity_id]);
  }



  protected $code;
  protected $household_id;
  protected $params;

  protected static $live_activity_status_ids = NULL;
  protected static $activity_type_id = NULL;

  protected function __construct($code, $household_id, $params = []) {
    $this->code = $code;
    $this->household_id = $household_id;
    $this->params = $params;
  }


  /**
   * Try to automatically fix a problem
   *
   * @return TRUE if fix was successful
   */
  public function fix($close_activity = TRUE) {
    switch ($this->code) {
      case 'HMNW':
        $fixed = CRM_Householdmerge_Logic_Fixer::fixHMNW($this);
        break;

      default:
        $fixed = FALSE;
    }

    if ($fixed && $close_activity && $this->getActivityID()) {
      // mark activity as completed
      civicrm_api3('Activity','create', [
        'id'        => $this->getActivityID(),
        'status_id' => CRM_Householdmerge_Logic_Configuration::getCompletedActivityStatusID()]);
    }

    return $fixed;
  }

  /**
   * get the id of the activity (if exists) associated with this problem
   */
  public function getActivityID() {
    if (empty($this->params['activity_id'])) {
      return NULL;
    } else {
      return (int) $this->params['activity_id'];
    }
  }

  /**
   * get the id of the activity (if exists) associated with this problem
   */
  public function getHouseholdID() {
    return $this->household_id;
  }

  /**
   *
   * @return ?int activity_id if a new activity was created
   */
  public function createActivity() {
    // only create if no live activity exists (don't create the same one over and over)
    if ($this->hasLiveActivity()) {
      return NULL;
    }

    // DISABLED DETAILS:
    // render the content
    // $smarty = CRM_Core_Smarty::singleton();
    // $smarty->pushScope(array(
    //   'household'  => $household,
    //   'problems'   => $problems,
    //   'members'    => $members,
    //   ));
    // $activity_content = $smarty->fetch('CRM/Householdmerge/Checker/Activity.tpl');
    // $smarty->popScope();

    // compile activity
    $activity_data = [];
    $activity_data['subject']            = $this->getTitle();
    // $activity_data['details']            = $activity_content;
    $activity_data['activity_date_time'] = date("Ymdhis");
    $activity_data['activity_type_id']   = CRM_Householdmerge_Logic_Configuration::getCheckHouseholdActivityTypeID();
    $activity_data['status_id']          = CRM_Householdmerge_Logic_Configuration::getScheduledActivityStatusID();
    $activity_data['target_contact_id']  = [(int) $this->household_id];
    if (!empty($this->params['member_id'])) {
      $activity_data['target_contact_id'][] = (int) $this->params['member_id'];
    }
    $activity_data['source_contact_id']  = (int) $this->household_id;

    $activity = CRM_Activity_BAO_Activity::create($activity_data);
    if (empty($activity->id)) {
      throw new Exception("Couldn't create activity for household [{$this->household_id}]");
    } else {
      $this->params['activity_id'] = $activity->id;
      return $activity->id;
    }
  }

  /**
   * Generate the problem title (for activity)
   */
  protected function getTitle() {
    $problem_classes = self::getProblemClasses();
    $template = $problem_classes[$this->code]['title'];
    foreach ($this->params as $key => $value) {
      $template = str_replace('{'.$key.'}', $value, $template);
    }
    return "[{$this->code}] $template";
  }

  /**
   * Check if there alread is an (active) 'check' activity with this household
   */
  protected function hasLiveActivity() {
    $activity_type_id    = (int) CRM_Householdmerge_Logic_Configuration::getCheckHouseholdActivityTypeID();
    $household_id        = (int) $this->household_id;
    $activity_status_ids = CRM_Householdmerge_Logic_Configuration::getLiveActivityStatusIDs();
    $sentinel            = "[{$this->code}] %";
    if (empty($this->params['member_id'])) {
      $member_clause = "";
    } else {
      $member_id = (int) $this->params['member_id'];
      $member_clause = "AND EXISTS (SELECT id FROM civicrm_activity_contact WHERE activity_id = civicrm_activity.id AND contact_id = $member_id AND record_type_id = 3)";
    }

    $selector_sql = "SELECT civicrm_activity.id AS activity_id
                     FROM civicrm_activity
                     LEFT JOIN civicrm_activity_contact target ON target.activity_id = civicrm_activity.id AND target.record_type_id = 3
                     WHERE civicrm_activity.activity_type_id = $activity_type_id
                       AND civicrm_activity.status_id IN ($activity_status_ids)
                       AND civicrm_activity.subject LIKE %1
                       AND target.contact_id = $household_id
                       $member_clause ;";
    $selector_params = [1 => [$sentinel, 'String']];
    $query = CRM_Core_DAO::executeQuery($selector_sql, $selector_params);
    return $query->fetch();
  }

}
