{*-------------------------------------------------------+
| Household Merger Extension                             |
| Copyright (C) 2015-2016 SYSTOPIA                       |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*}

<h3>{ts domain='de.systopia.householdmerge' 1=$problems|@count}The scan revealed %1 problem(s):{/ts}</h3>
<ul>
{foreach from=$problems item=problem}
  <li>{$problem}</li>
{/foreach}
</ul>
<br/>


<h3>{ts domain='de.systopia.householdmerge'}Household Members{/ts}</h3>
<table>
{foreach from=$members item=member}
{assign var=member_id value=$member.id}
  <tr>
    <td>
      {if $member.hh_relation eq 'head'}
        {ts domain="de.systopia.householdmerge"}Head{/ts}
      {else}
        {ts domain="de.systopia.householdmerge"}Member{/ts}
      {/if}
    </td>
    <td>
      <div class="icon crm-icon Individual-icon"></div>
      <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$member_id"}">{$member.display_name} [{$member_id}]</a>
    </td>
  </tr>
{/foreach}
</table>
<br/>


