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

<table class="hhmerge">
  <tr> 
    <td colspan="1-10">{ts}Target:{/ts}</td>
  </tr>

  <tr class="hhmerge-target">
    <td>{* Status Icon *}</td>
    <td><div class="icon crm-icon {$household.contact_type}-icon"></div> {$household.household_name}</td>
    <td>[{$household.id}]</div></td>
    <td>{* Conflict Count *}</td>
    <td>{* Merge Link *}</td>
  </tr>

  <tr> 
    <td colspan="1-10">{ts}Contacts:{/ts}</td>
  </tr>
{foreach from=$other_contacts item=contact}
  <tr class="hhmerge-contact">
    <td><img src="{$config->resourceBase}i/check.gif"/></td>
    <td><div class="icon crm-icon {$contact.contact_type}-icon"></div> {$contact.sort_name}</td>
    <td>[{$contact.id}]</div></td>
    <td>{$contact.conflicts|@count}</td>
    <td><a href="{crmURL p='civicrm/contact/merge' q='reset=1&cid=7080&oid=4835'}">{ts}merge{/ts}</a></td>
  </tr>
{/foreach}
</table>

<a class="button new-option" href=""><span><div class="icon reload-icon"></div>{ts}Reload{/ts}</span></a>