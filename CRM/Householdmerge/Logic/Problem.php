<?php
/*-------------------------------------------------------+
| Household Merger Extension                             |
| Copyright (C) 2016 SYSTOPIA                            |
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
      self::$_problem_classes = array(
      'HOM0' => array(
                  'code'  => 'HOM0',
                  'title' => ts("Household has no members any more", array('domain' => 'de.systopia.householdmerge')),
                  ),
      'HOMX' => array(
                  'code'  => 'HOMX',
                  'title' => ts("Household has only {count} member(s) left", array('domain' => 'de.systopia.householdmerge')),
                  ),
      'HHN0' => array(
                  'code'  => 'HHN0',
                  'title' => ts("Household has no head any more", array('domain' => 'de.systopia.householdmerge')),
                  ),
      'HHN2' => array(
                  'code'  => 'HHN2',
                  'title' => ts("Household has multiple heads", array('domain' => 'de.systopia.householdmerge')),
                  ),
      'HHNC' => array(
                  'code'  => 'HHNC',
                  'title' => ts("Household head has one of the 'do not contact' attributes set", array('domain' => 'de.systopia.householdmerge')),
                  ),
      'HHTG' => array(
                  'code'  => 'HHTG',
                  'title' => ts("Household head has tag '{tag}'", array('domain' => 'de.systopia.householdmerge')),
                  ),
      'HHMM' => array(
                  'code'  => 'HHMM',
                  'title' => ts("Household head is head of multiple households", array('domain' => 'de.systopia.householdmerge')),
                  ),
      'HMBA' => array(
                  'code'  => 'HMBA',
                  'title' => ts("Household member does not share the household's address any more", array('domain' => 'de.systopia.householdmerge')),
                  ),
      'HMNW' => array(
                  'code'  => 'HMNW',
                  'title' => ts("New household member detected", array('domain' => 'de.systopia.householdmerge')),
                  ),
      );
    }
    return self::$_problem_classes;
  }
    
  /**
   * create a problem instance defined by the given code
   */
  public static function createProblem($code, $household_id, $params = array()) {
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
    $activity = civicrm_api3('Activity', 'getsingle', array('id' => $activity_id));
    if ($activity['activity_type_id'] != CRM_Householdmerge_Logic_Configuration::getCheckHouseholdActivityTypeID()) {
      return NULL;
    }

    $fixable_status_ids = explode(',', CRM_Householdmerge_Logic_Configuration::getFixableActivityStatusIDs());
    if (!in_array($activity['status_id'], $fixable_status_ids)) {
      return NULL;
    }

    $code = substr($activity['subject'], 1, 4);

    // TODO: load member at this point?
    return self::createProblem($code, $activity['source_contact_id'], array('activity_id' => $activity_id));
  } 



  protected $code;
  protected $household_id;
  protected $params;

  protected static $live_activity_status_ids = NULL;
  protected static $activity_type_id = NULL;

  protected function __construct($code, $household_id, $params = array()) {
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

    if ($fixed && $close_activity && !empty($this->params['activity_id'])) {
      // mark activity as completed
      civicrm_api3('Activity','create', array(
        'id'        => $this->params['activity_id'],
        'status_id' => CRM_Householdmerge_Logic_Configuration::getCompletedActivityStatusID()));
    }
  }


  /** 
   *
   * @return activity_id if a new activity was created
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
    $activity_data = array();
    $activity_data['subject']            = $this->getTitle();
    // $activity_data['details']            = $activity_content;
    $activity_data['activity_date_time'] = date("Ymdhis");
    $activity_data['activity_type_id']   = CRM_Householdmerge_Logic_Configuration::getCheckHouseholdActivityTypeID();
    $activity_data['status_id']          = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name');
    $activity_data['target_contact_id']  = array((int) $this->household_id);
    if (!empty($this->params['member_id'])) {
      $activity_data['target_contact_id'][] = (int) $this->params['member_id'];
    }
    $activity_data['source_contact_id']  = (int) $this->household_id;
    
    $activity = CRM_Activity_BAO_Activity::create($activity_data);
    if (empty($activity->id)) {
      throw new Exception("Couldn't create activity for household [{$household['id']}]");
    } else {
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
    $selector_params = array(1 => array($sentinel, 'String'));
    $query = CRM_Core_DAO::executeQuery($selector_sql, $selector_params);
    return $query->fetch();
  }

}
