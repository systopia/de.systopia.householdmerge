{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Household Merger Extension                             |
| Copyright (C) 2015-2018 SYSTOPIA                       |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*}

<div class="crm-block crm-form-block crm-activity_delete-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <div>
    <div id="help" class="description">
      {ts domain="de.systopia.householdmerge"}This will try and automatically fix the problems represented by the selected activities. If successful, the respective activity will be marked as 'completed'.{/ts}
    </div>
    <div>
      <h2>{ts domain="de.systopia.householdmerge"}Overview{/ts}</h2>
      <table style="width:auto;">
        <tr>
          <td>{ts domain="de.systopia.householdmerge"}Total activities{/ts}</td>
          <td>{$total_activities}</td>
        </tr>
        <tr>
          <td>{ts domain="de.systopia.householdmerge"}Household problem activities{/ts}</td>
          <td>{$relevant_activities}</td>
        </tr>
        <tr>
          <td>{ts domain="de.systopia.householdmerge"}Number of problem classes{/ts}</td>
          <td>{$class_count}</td>
        </tr>
      </table>
    </div>
  </div>
  <br/>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
