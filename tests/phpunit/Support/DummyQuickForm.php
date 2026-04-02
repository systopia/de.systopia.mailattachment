<?php declare(strict_types = 1);

namespace Civi\MailAttachment\Tests\Support;

/**
 * Minimal QuickForm stub for ContributionInvoice::buildAttachmentForm/processAttachmentForm.
 */
final class DummyQuickForm extends \CRM_Core_Form {
  /**
   * @var array<string, mixed>
   */
  public array $defaults = [];

  /**
   * @var array<string, mixed>
   */
  public array $exportValues = [];

  /**
   * @var array<int, array{type:string,name:string,label:string,attrs:array<string,mixed>}>
   */
  public array $added = [];

  /**
   * @phpstan-param array<mixed> $attributes
   * @phpstan-param array<mixed> $extra
   */
  public function &add(
    $type,
    $name,
    $label = '',
    $attributes = [],
    $required = FALSE,
    $extra = NULL
  ): \HTML_QuickForm_element {
    $this->added[] = [
      'type' => $type,
      'name' => $name,
      'label' => $label,
      'attrs' => $attributes,
    ];
    $element = new \HTML_QuickForm_element('Test', 'test');
    return $element;
  }

  /**
   * @param array<mixed> $defaultValues
   */
  public function setDefaults($defaultValues = NULL, $filter = NULL): void {
    if ($defaultValues !== NULL) {
      foreach ($defaultValues as $k => $v) {
        $this->defaults[$k] = $v;
      }
    }
  }

  /**
   * @param mixed $elementList
   * @param mixed $filterInternal
   * @return array<string, mixed>
   */
  public function exportValues($elementList = NULL, $filterInternal = FALSE): array {
    return $this->exportValues;
  }

  public function hasAdded(string $name): bool {
    foreach ($this->added as $a) {
      if ($a['name'] === $name) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
