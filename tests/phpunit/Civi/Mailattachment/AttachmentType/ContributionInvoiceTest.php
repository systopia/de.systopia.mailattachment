<?php

declare(strict_types = 1);

namespace Civi\Mailattachment\AttachmentType;

use Civi\Mailattachment\Tests\HeadlessSetup;
use Civi\Test\TransactionalInterface;
use Civi\Mailattachment\Tests\Support\DummyQuickForm;
use Systopia\TestFixtures\Fixtures\Scenarios\ContributionScenario;

/**
 * @covers \Civi\Mailattachment\AttachmentType\ContributionInvoice
 * @group headless
 */
final class ContributionInvoiceTest extends HeadlessSetup implements TransactionalInterface {

  public function testGetAttachmentFormTemplate(): void {
    self::assertNull(ContributionInvoice::getAttachmentFormTemplate());
    self::assertSame(
      'Civi/Mailattachment/AttachmentType/ContributionInvoice.hlp',
      ContributionInvoice::getAttachmentFormTemplate('hlp')
    );
  }

  public function testBuildAndProcessAttachmentForm(): void {
    $form = new DummyQuickForm();

    $prefix = 'p_';
    $attachmentId = 7;

    /** @var array<string, mixed> $elements */
    $elements = ContributionInvoice::buildAttachmentForm($form, $attachmentId, $prefix, [
      'name' => 'My Invoice Name.pdf',
    ]);

    self::assertInstanceOf(DummyQuickForm::class, $form);

    $fieldName = $prefix . 'attachments--' . $attachmentId . '--name';

    self::assertTrue($form->hasAdded($fieldName), 'Expected QuickForm add() for invoice name field.');
    self::assertSame('My Invoice Name.pdf', $form->defaults[$fieldName] ?? NULL);
    self::assertArrayHasKey($fieldName, $elements);

    $form->exportValues = [
      $fieldName => 'Processed Name.pdf',
    ];

    $values = ContributionInvoice::processAttachmentForm($form, $attachmentId, $prefix);
    self::assertSame(['name' => 'Processed Name.pdf'], $values);
  }

  public function testBuildAttachment_CreatesPdfFileAndReturnsMetadata(): void {
    $bag = ContributionScenario::contactWithMembershipAndOpenContribution();
    $result = $bag->toArray();

    self::assertNotNull($result['contactId']);
    self::assertNotNull($result['contributionId']);

    $context = [
      'entity_type' => 'contribution',
      'entity_id' => (string) $result['contributionId'],
      'entity_ids' => [$result['contributionId']],
      'entity' => NULL,
      'extra' => ['contact_id' => $result['contactId']],
    ];

    $attachment = ContributionInvoice::buildAttachment($context, [
      'path' => '',
      'name' => 'Invoice-Test.pdf',
      'template_id' => 0,
    ]);
    /** @var array{fullPath: string, mime_type: string, cleanName: string} $attachment */

    self::assertIsArray($attachment);
    self::assertArrayHasKey('fullPath', $attachment);
    self::assertArrayHasKey('mime_type', $attachment);
    self::assertArrayHasKey('cleanName', $attachment);

    self::assertIsString($attachment['fullPath']);
    self::assertFileExists($attachment['fullPath']);
    self::assertIsString($attachment['mime_type']);
    self::assertNotSame('', $attachment['mime_type']);
    self::assertSame('Invoice-Test.pdf', $attachment['cleanName']);

    unlink($attachment['fullPath']);
  }

  public function testBuildAttachment_WithEmptyName_UsesBasename(): void {
    $bag = ContributionScenario::contactWithMembershipAndOpenContribution();
    $result = $bag->toArray();

    self::assertNotNull($result['contactId']);
    self::assertNotNull($result['contributionId']);

    $context = [
      'entity_type' => 'contribution',
      'entity_id' => (string) $result['contributionId'],
      'entity_ids' => [$result['contributionId']],
      'entity' => NULL,
      'extra' => ['contact_id' => $result['contactId']],
    ];

    $attachment = ContributionInvoice::buildAttachment($context, [
      'path' => '',
      'name' => '',
      'template_id' => 0,
    ]);

    /** @var array{fullPath: string, mime_type: string, cleanName: string} $attachment */
    self::assertFileExists($attachment['fullPath']);
    self::assertSame(basename($attachment['fullPath']), $attachment['cleanName']);
    unlink($attachment['fullPath']);
  }

}
