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

  function removeURLParameter(url, parameter) {
    //prefer to use l.search if you have a location/link object
    var urlparts = url.split('?');
    if (urlparts.length >= 2) {

      var prefix = encodeURIComponent(parameter) + '=';
      var pars = urlparts[1].split(/[&;]/g);

      //reverse iteration as may be destructive
      for (var i = pars.length; i-- > 0;) {
        //idiom for string.startsWith
        if (pars[i].lastIndexOf(prefix, 0) !== -1) {
          pars.splice(i, 1);
        }
      }

      return urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : '');
    }
    return url;
  }

  var mailattachmentBehavior = function() {
    var $forms;
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
      var $form = $(this);
      var $attachmentsWrappers = $form.find('.crm-mailattachment-attachments-wrapper');
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

      $('.crm-mailattachment-attachment-more', $attachmentsWrappers)
          .on('click', function() {
            var $currentAttachmentsWrapper = $(this).closest('.crm-mailattachment-attachments-wrapper');
            var currentPrefix = $currentAttachmentsWrapper.data('mailattachment-prefix');
            var urlSearchparams = new URLSearchParams(window.location.search);
            urlSearchparams.append('ajax_action', 'add_attachment');
            var postValues = {
              qfKey: $form.find('[name="qfKey"]').val(),
              ajax_context: 'attachments',
              ajax_action: 'add_attachment',
              ajax_attachment_type: $currentAttachmentsWrapper.find('.crm-mailattachment-attachment-more-type').val(),
              ajax_attachments_prefix: $currentAttachmentsWrapper.data('mailattachment-prefix'),
              snippet: 6
            };
            var $currentAttachments = $currentAttachmentsWrapper.find('[name^="' + currentPrefix + 'attachments--"]');
            $currentAttachments.each(function() {
              postValues[$(this).attr('name')] = $(this).val();
            });

            $currentAttachmentsWrapper.find('.loading-overlay').show();

            // Retrieve the form with another attachment field.
            $.post(
                removeURLParameter(location.href, 'reset'),
                postValues,
                function(data) {
                  $currentAttachmentsWrapper
                      .replaceWith($(data.content)
                          .find('#crm-mailattachment-' + currentPrefix + 'attachments-wrapper')
                          .each(mailattachmentBehavior)
                      );
                }
            );
          });

      $('.crm-mailattachment-attachment-remove', $attachmentsWrappers)
          .on('click', function() {
            var $currentAttachmentsWrapper = $(this).closest('.crm-mailattachment-attachments-wrapper');
            var currentPrefix = $currentAttachmentsWrapper.data('mailattachment-prefix');
            var urlSearchparams = new URLSearchParams(window.location.search);
            urlSearchparams.append('ajax_action', 'remove_attachment');
            var postValues = {
              qfKey: $form.find('[name="qfKey"]').val(),
              ajax_context: 'attachments',
              ajax_action: 'remove_attachment',
              ajax_attachment_id: $(this).data('attachment_id'),
              ajax_attachments_prefix: $currentAttachmentsWrapper.data('mailattachment-prefix'),
              snippet: 6
            };
            var $currentAttachments = $currentAttachmentsWrapper.find('[name^="' + currentPrefix + 'attachments--"]');
            $currentAttachments.each(function() {
              postValues[$(this).attr('name')] = $(this).val();
            });

            $currentAttachmentsWrapper.find('.loading-overlay').show();

            // Retrieve the form with another attachment field.
            $.post(
                removeURLParameter(location.href, 'reset'),
                postValues,
                function(data) {
                  $currentAttachmentsWrapper
                      .replaceWith($(data.content)
                          .find('#crm-mailattachment-' + currentPrefix + 'attachments-wrapper')
                          .each(mailattachmentBehavior)
                      );
                }
            );
          });
    });
  };

  $(document).ready(mailattachmentBehavior);
  $(document).on('crmLoad', mailattachmentBehavior);

})(CRM.$ || cj);
