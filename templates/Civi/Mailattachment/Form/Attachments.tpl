{*-------------------------------------------------------+
| SYSTOPIA Mail Attachments Extension                    |
| Copyright (C) 2022 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*}

{crmScope extensionKey='de.systopia.mailattachment'}
    {* $prefix is being handed in as an include parameter. *}
    {* TODO: Make sure $prefix is an empty string when it isn't provided. *}
  <div id="crm-mailattachment-{$prefix}attachments-wrapper">

      <table class="crm-mailattachment-attachments-table row-highlight">
          <tbody>
          {* TODO: Fix variable variables access *}
          {foreach from=${$prefix}attachment_forms item="attachment" key="attachment_id"}
            <tr class="crm-mailattachment-attachment">

              <td style="width: 100%;">

                  <h3>{$attachment.title}</h3>

                  {if $attachment.form_template}
                      {include file=$attachment.form_template}
                  {else}
                      {foreach from=$attachment.elements key="attachment_element" item="attachment_element_type"}
                        <div class="crm-section">
                          <div class="label">
                              {$form.$attachment_element.label}
                              {capture assign="help_id"}id-{$attachment_element_type}{/capture}
                              {if $attachment.help_template}
                                  {capture assign="help_file"}{$attachment.help_template}{/capture}
                                  {help id=$help_id title=$form.$attachment_element.label file=$help_file}
                              {else}
                                  {help id=$help_id title=$form.$attachment_element.label}
                              {/if}
                          </div>
                          <div class="content">{$form.$attachment_element.html}</div>
                          <div class="clear"></div>
                        </div>
                      {/foreach}
                  {/if}

              </td>

              <td>
                  {capture assign="attachment_remove_button_name"}{$prefix}attachments--{$attachment_id}_remove{/capture}
                  {$form.$attachment_remove_button_name.html}
              </td>

            </tr>
          {/foreach}
          </tbody>
      </table>

      {$form.attachments_more_type.html}
      {$form.attachments_more.html}
  </div>
{/crmScope}
