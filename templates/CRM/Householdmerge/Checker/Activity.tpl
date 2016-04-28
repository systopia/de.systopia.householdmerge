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

<h3>{ts domain='de.systopia.householdmerge'}Check Household{/ts}</h3>

<p>
  {ts domain='de.systopia.householdmerge' 1=$change_count}%1 address was changed with address cleanup.{/ts}
</p>
<p>
  {ts domain='de.systopia.householdmerge' 1=$metadata.uri}Cleanup ID was <code>%1</code>).{/ts}
</p>
<br/>

<h3>{ts domain='de.systopia.householdmerge'}Changes{/ts}</h3>
<table>
{foreach from=$changes item=change}
  <tr>
    <td>{$change.label}</td>
    <td>{$change.old}</td>
    <td>{$change.new}</td>
  </tr>
{/foreach}
</table>
<br/>


<h3>{ts domain='de.systopia.householdmerge'}Status Codes{/ts}</h3>
<ul>
{foreach from=$record.status item=status_text key=status_key}
  <li>{$status_key}: {$status_text}</li>
{/foreach}
</ul>
<br/>


