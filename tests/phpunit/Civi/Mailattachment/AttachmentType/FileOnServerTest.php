<?php
declare(strict_types = 1);

namespace Civi\Mailattachment\AttachmentType;

use Civi\Mailattachment\Tests\Support\DummyQuickForm;
use Civi\Test\TransactionalInterface;
use Civi\Mailattachment\Tests\HeadlessSetup;
use Systopia\TestFixtures\Fixtures\Scenarios\ContributionScenario;

/**
 * @covers \Civi\Mailattachment\AttachmentType\FileOnServer
 * @group headless
 */
final class FileOnServerTest extends HeadlessSetup implements TransactionalInterface {

  public function testGetAttachmentFormTemplate_ReturnsTemplate(): void {
    self::assertNull(FileOnServer::getAttachmentFormTemplate('tpl'));
    self::assertSame(
      'Civi/Mailattachment/AttachmentType/FileOnServer.hlp',
      FileOnServer::getAttachmentFormTemplate('hlp')
    );
  }

  public function testBuildAttachmentForm_ProcessAttachmentForm_CreatesDocument(): void {
    $form = new DummyQuickForm();

    $prefix = 'p_';
    $attachmentId = 3;

    $coreForm = $form;

    /** @var array<mixed> $elements */
    $elements = FileOnServer::buildAttachmentForm($coreForm, $attachmentId, $prefix, [
      'path' => '/tmp/example.pdf',
      'name' => 'MyName.pdf',
    ]);

    $pathField = $prefix . 'attachments--' . $attachmentId . '--path';
    $nameField = $prefix . 'attachments--' . $attachmentId . '--name';

    self::assertTrue($form->hasAdded($pathField));
    self::assertTrue($form->hasAdded($nameField));
    self::assertSame('/tmp/example.pdf', $form->defaults[$pathField] ?? NULL);
    self::assertSame('MyName.pdf', $form->defaults[$nameField] ?? NULL);

    self::assertArrayHasKey($pathField, $elements);
    self::assertArrayHasKey($nameField, $elements);

    $form->exportValues = [
      $pathField => '/tmp/real.pdf',
      $nameField => 'ChosenName',
    ];

    $values = FileOnServer::processAttachmentForm($coreForm, $attachmentId, $prefix);
    self::assertSame(
      ['path' => '/tmp/real.pdf', 'name' => 'ChosenName'],
      $values
    );
  }

  public function testBuildAttachment_ReturnsAttachmentArray(): void {
    $tmpBase = tempnam(sys_get_temp_dir(), 'mailatt-');
    self::assertIsString($tmpBase);
    $tmp = $tmpBase . '.pdf';
    rename($tmpBase, $tmp);
    file_put_contents($tmp, "%PDF-1.4\n% test\n");

    $pathTemplate = str_replace('123', '{contact_id}', $tmp);

    $context = [
      'entity_type' => 'contact',
      'entity_id' => '123',
      'entity_ids' => [123],
      'entity' => NULL,
      'extra' => NULL,
    ];

    $attachment = FileOnServer::buildAttachment($context, [
      'path' => $pathTemplate,
      'name' => 'InvoiceName',
      'template_id' => 0,
    ]);

    self::assertIsArray($attachment);
    self::assertSame($tmp, $attachment['fullPath']);
    self::assertIsString($attachment['mime_type']);
    self::assertNotSame('', $attachment['mime_type']);
    self::assertSame('InvoiceName.pdf', $attachment['cleanName']);

    unlink($tmp);
  }

  public function testBuildAttachment_WithInvalidFile_ReturnsNull(): void {
    $context = [
      'entity_type' => 'contact',
      'entity_id' => '999',
      'entity_ids' => [999],
      'entity' => NULL,
      'extra' => NULL,
    ];

    $attachment = FileOnServer::buildAttachment($context, [
      'path' => '/this/does/not/exist/{contact_id}.pdf',
      'name' => 'Whatever',
      'template_id' => 0,
    ]);

    self::assertNull($attachment);
  }

  public function testBuildAttachment_WithContribution_ResolvesDocument(): void {
    $bag = ContributionScenario::contactWithMembershipAndOpenContribution();
    $result = $bag->toArray();

    self::assertNotNull($result['contactId']);
    self::assertNotNull($result['contributionId']);

    $contactId = $result['contactId'];
    $contributionId = $result['contributionId'];

    $tmp = sys_get_temp_dir()
      . '/mailatt-'
      . $contactId
      . '-'
      . $contributionId
      . '.pdf';
    file_put_contents($tmp, "%PDF-1.4\n% test\n");

    $pathTemplate = str_replace(
      [(string) $contactId, (string) $contributionId],
      ['{contact_id}', '{contribution_id}'],
      $tmp
    );

    $context = [
      'entity_type' => 'contribution',
      'entity_id' => (string) $contributionId,
      'entity_ids' => [$contributionId],
      'entity' => NULL,
      'extra' => NULL,
    ];

    $attachment = FileOnServer::buildAttachment($context, [
      'path' => $pathTemplate,
      'name' => 'MyAttachment',
      'template_id' => 0,
    ]);

    self::assertIsArray($attachment);
    self::assertSame($tmp, $attachment['fullPath']);
    self::assertSame('MyAttachment.pdf', $attachment['cleanName']);

    unlink($tmp);
  }

  public function testBuildAttachment_WithParticipant_ResolvesDocument(): void {
    $contact = \Civi\Api4\Contact::create(FALSE)
      ->setValues([
        'contact_type' => 'Individual',
        'first_name' => 'Test',
        'last_name' => 'ParticipantContact',
      ])
      ->execute()
      ->single();

    $contactId = $contact['id'];
    self::assertGreaterThan(0, $contactId);

    $event = \Civi\Api4\Event::create(FALSE)
      ->setValues([
        'title' => 'PHPUnit Event',
        'event_type_id' => 1,
        'start_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'end_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
        'is_active' => TRUE,
      ])
      ->execute()
      ->single();

    $eventId = $event['id'];
    self::assertGreaterThan(0, $eventId);

    $participant = \Civi\Api4\Participant::create(FALSE)
      ->setValues([
        'contact_id' => $contactId,
        'event_id' => $eventId,
        'status_id' => 1,
        'role_id' => 1,
        'register_date' => date('Y-m-d H:i:s'),
      ])
      ->execute()
      ->single();

    $participantId = $participant['id'];
    self::assertGreaterThan(0, $participantId);

    $tmp = sys_get_temp_dir()
      . '/mailatt-'
      . $contactId
      . '-'
      . $participantId
      . '.pdf';
    file_put_contents($tmp, "%PDF-1.4\n% test\n");

    $pathTemplate = str_replace(
      [(string) $contactId, (string) $participantId],
      ['{contact_id}', '{participant_id}'],
      $tmp
    );

    $context = [
      'entity_type' => 'participant',
      'entity_id' => (string) $participantId,
      'entity_ids' => [$participantId],
      'entity' => NULL,
      'extra' => NULL,
    ];

    $attachment = FileOnServer::buildAttachment($context, [
      'path' => $pathTemplate,
      'name' => 'MyAttachment',
      'template_id' => 0,
    ]);

    self::assertIsArray($attachment);
    self::assertSame($tmp, $attachment['fullPath']);
    self::assertSame('MyAttachment.pdf', $attachment['cleanName']);

    unlink($tmp);
  }

}
