{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Household Merger Extension                             |
| Copyright (C) 2015 SYSTOPIA                            |
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


<div>
  <h3>{ts}Merge Contacts:{/ts}</h3>
  <div>
    <table>
    {foreach from=$contacts item=contact}
      <tr class="{cycle values="odd-row,even-row"}">
        <td><div class="icon crm-icon {$contact.display_name}-icon"></div></td>
        <td>{$contact.display_name}</td>
      </tr>
    {/foreach}
    </table>
  </div>
</div>
<br/>
<div>
  <h3>{ts}Into Household:{/ts}</h3>
  <table>
    <tr><td colspan=2><hr></td></tr>
    <tr>
      <td>
        <input id="hh_option_new" class="crm-form-radio" type="radio" value="new" name="hh_option">
        <label for="hh_option_new">{ts}Create <strong>new</strong> Household{/ts}</label>
      </td>
      <td>
        <div>
          <span>{$form.household_name.label}</span>
          <span>{$form.household_name.html}</span>
        </div>
        <div>
          <span>{$form.household_name_pattern.label}</span>
          <span>{$form.household_name_pattern.html}</span>
        </div>
      </td>
    </tr>
    <tr><td colspan=2><hr></td></tr>
    <tr>
      <td>
        <input id="hh_option_existing" class="crm-form-radio" type="radio" value="existing" name="hh_option">
        <label for="hh_option_existing">{ts}Chose <strong>existing</strong> Household{/ts}</label>
      </td>
      <td>
        <div>
          <span>{$form.existing_household.label}</span>
          <span>{$form.existing_household.html}</span>
        </div>
      </td>
    </tr>
    <tr><td colspan=2><hr></td></tr>
  </table>
</div>

<br/>
<h1>{ts}Warning! This cannot be undone!{/ts}</h1>
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>