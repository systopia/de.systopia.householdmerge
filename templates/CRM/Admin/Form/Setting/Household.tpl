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

<h3>{ts domain="de.systopia.householdmerge"}Household-Module Settings{/ts}</h3>

<div class="crm-section">
  <div class="label">{$form.hh_mode.label}</div>
  <div class="content">{$form.hh_mode.html}</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.hh_member_relation.label}</div>
  <div class="content">{$form.hh_member_relation.html}</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.hh_head_relation.label}</div>
  <div class="content">{$form.hh_head_relation.html}</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.hh_head_mode.label}</div>
  <div class="content">{$form.hh_head_mode.html}</div>
  <div class="clear"></div>
</div>

<br/>
{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
