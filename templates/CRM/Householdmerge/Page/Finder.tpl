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

<h3>Finder</h3>

{foreach from=$proposals item=proposal}
<h2>Proposal</h2>
<table>
{if $proposal.head_id}
  <tr>
    <td>HEAD: {$proposal.head_id}</td>
  </tr>
{/if}

{foreach from=$proposal.member_ids item=member_id}
  <tr>
    <td>MEMBER: {$member_id}</td>
  </tr>
{/foreach}
</table>

{/foreach}
