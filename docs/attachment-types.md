# Attachment Types

Adding attachments of different types can be made available by defining
attachment type classes implementing an interface, each with implementations of
properties and processing logic:

* Form elements (e.g. configuration parameters for dynamic file generation, or
  information about where to retrieve a file from), along with an optional
  form template (for more advanced forms)
* Form processing, i.e. passing the form elements' values on to the next step
* Attachment generation, i.e. either generating the attachment file or
  retrieving information about existing files and passing those on to CiviCRM
  e-mail processing

Each attachment type defines which CiviCRM entities it is supposed to provide
attachments for, e.g. it only makes sense to generate contribution invoices for
contributions, or iCal files for events, while documents generated off of a
message template makes sense for all entities that have a contact associated.

The extension provides the following attachment types on its own:

* _File on Server_: Allows users to provide the path to an existing file,
  optionally with contact, contribution, or participant IDs being replaced for
  separate documents per entity
* _Contribution Invoice_: Generates invoices for contributions

Extensions known to provide attachment types for this framework (feel free to
submit your implementations to be listed here):

* [_Custom Event Communication_](https://github.com/systopia/de.systopia.eventmessages):
    * _iCalendar file_ for events
    * _Message Template as PDF_ for events (migrated their own implementation
      with more event-related information in additional Smarty variables)
* [_CiviOffice_](https://github.com/systopia/de.systopia.civioffice):
    * _CiviOffice Document_ for many CiviCRM entities (meant as a replacement
      for message templates using office suite integrations)

Extensions known to implement this framework, allowing to add attachments to
e-mail:

* [_Custom Event Communication_](https://github.com/systopia/de.systopia.eventmessages):
  Sending highly configurable e-mail depending on event participant status
  changes and roles, and with a participant search task
* [_MailBatch_](https://github.com/systopia/de.systopia.mailbatch):
  Sending e-mail to batches of contacts and contributions without CiviCRM's
  limit of 50 records and without having to use CiviCRM Mailings
