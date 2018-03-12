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

{assign var="household_id" value=$household.id}


<div>
  <h3>{ts domain="de.systopia.householdmerge"}Merge Contacts:{/ts}</h3>
  <div>
    <table>
    {foreach from=$other item=contact}
      <tr class="{cycle values="odd-row,even-row"}">
        <td width="22em">[{$contact.id}]</td>
        <td><div class="icon crm-icon {$contact.contact_type}-icon"></div>&nbsp;{$contact.display_name}</td>
        <td>{if !$contact.was_merged}{$contact.conflict_count} {ts domain="de.systopia.householdmerge"}Conflicts{/ts}{/if}</td>
        <td>
          {if $contact.was_merged}
          <img width="14" src="{$config->resourceBase}i/check.gif"/>&nbsp;{ts domain="de.systopia.householdmerge"}merged{/ts}
          {else}
          <img width="14" src="{$config->resourceBase}i/Error.gif"/>&nbsp;{ts domain="de.systopia.householdmerge"}not merged{/ts}
          {/if}
        </td>
        <td>
          {if !$contact.was_merged}
            {assign var="contact_id" value=$contact.id}
            <a class="button" target="_blank" href="{crmURL p='civicrm/contact/merge' q="reset=1&cid=$household_id&oid=$contact_id"}">
              <span>{ts domain="de.systopia.householdmerge"}Merge Now{/ts}</span>
            </a>
          {/if}
        </td>
      </tr>
    {/foreach}
    </table>
  </div>
</div>
<br/>
<div>
  <h3>{ts domain="de.systopia.householdmerge"}Into Household:{/ts}</h3>
  <div>
    <table>
      <tr class="odd-row">
        <td width="22em">[{$household_id}]</td>
        <td><div class="icon crm-icon {$household.contact_type}-icon"></div>&nbsp;{$household.display_name}</td>
      </tr>
    </table>
  </div>
</div>



<div class="crm-actions-ribbon">
  <ul id="actions">
      <li class="crm-hhmerge-reload">
        <a class="button" href="">
          <span><div class="icon refresh-icon"></div>{ts domain="de.systopia.householdmerge"}Reload{/ts}</span>
        </a>
      </li>
      {if $merge_complete}
      <li class="crm-hhmerge-done">
        <a class="button" href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$household_id"}">
          <span><div class="icon inform-icon"></div>{ts domain="de.systopia.householdmerge"}Done{/ts}</span>
        </a>
      </li>
      {/if}
  </ul>
  <div class="clear"></div>
</div>


