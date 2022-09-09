/*-------------------------------------------------------+
| SYSTOPIA Mail Attachments Extension                    |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/
(function($) {

  let mailattachmentBehavior = function() {
    let $forms;
    if ($(this).is('form.crm-mailattachment-attachments-form')) {
      $forms = $(this);
    }
    else if (this === document) {
      $forms = $(this).find('form.crm-mailattachment-attachments-form');
    }
    else {
      $forms = $(this).closest('form.crm-mailattachment-attachments-form');
    }
    $forms.each(function() {
      let $form = $(this);
      let $attachmentsWrappers = $form.find('.crm-mailattachment-attachments-wrapper');
      $attachmentsWrappers
          .css('position', 'relative')
          .append(
              $('<div>')
                  .hide()
                  .addClass('loading-overlay')
                  .css({
                    backgroundColor: 'rgba(255, 255, 255, 0.5)',
                    position: 'absolute',
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0
                  })
                  .append(
                      $('<div>')
                          .addClass('crm-loading-element')
                          .css({
                            position: 'absolute',
                            left: '50%',
                            top: '50%',
                            marginLeft: '-15px',
                            marginTop: '-15px'
                          })
                  )
          );

      function handleAttachments(event) {
        let $button = $(event.target);
        let $currentAttachmentsWrapper = $button.closest('.crm-mailattachment-attachments-wrapper');
        let currentPrefix = $currentAttachmentsWrapper.data('mailattachment-prefix');
        let urlParams = ((new URL(document.location)).searchParams);
        urlParams.delete('reset');
        let postValues = Object.assign(
            {
              qfKey: $form.find('[name="qfKey"]').val(),
              ajax_context: 'attachments',
              ajax_action: $button.data('ajax_action'),
              ajax_attachment_type: $currentAttachmentsWrapper.find('.crm-mailattachment-attachment-more-type').val(),
              ajax_attachment_id: $button.data('attachment_id'),
              ajax_attachments_prefix: $currentAttachmentsWrapper.data('mailattachment-prefix'),
              snippet: 6
            },
            Object.fromEntries(urlParams)
        );
        let $currentAttachments = $currentAttachmentsWrapper.find('[name^="' + currentPrefix + 'attachments--"]');
        $currentAttachments.each(function() {
          postValues[$button.attr('name')] = $button.val();
        });

        $currentAttachmentsWrapper.find('.loading-overlay').show();

        // Retrieve the form with another attachment field.
        $.post(
            $form.attr('action'),
            postValues,
            function(data) {
              $currentAttachmentsWrapper
                  .replaceWith($(data.content)
                      .find('#crm-mailattachment-' + currentPrefix + 'attachments-wrapper')
                      .each(mailattachmentBehavior)
                  );
            }
        );
      }

      $('.crm-mailattachment-attachment-more', $attachmentsWrappers.not('.crm-mailattachment-processed'))
          .data('ajax_action', 'add_attachment')
          .on('click', handleAttachments);

      $('.crm-mailattachment-attachment-remove', $attachmentsWrappers.not('.crm-mailattachment-processed'))
          .data('ajax_action', 'remove_attachment')
          .on('click', handleAttachments);

      $attachmentsWrappers.not('.crm-mailattachment-processed').addClass('crm-mailattachment-processed');
    });
  };

  $(document).ready(mailattachmentBehavior);
  $(document).on('crmLoad', mailattachmentBehavior);

})(CRM.$ || cj);
