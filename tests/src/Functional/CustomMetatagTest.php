<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\helfi_api_base\Functional\BrowserTestBase;

class CustomMetatagTest extends BrowserTestBase  {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'node',
    'language',
    'helfi_proxy',
    'content_translation',
  ];

  public function setUp(): void {
    parent::setUp();
    FilterFormat::load('full_html');
    $this->drupalCreateContentType(['type' => 'page']);
    $this->node = $this->drupalCreateNode([
      'title' => 'en title',
      'body' => 'Content here.',
      'langcode' => 'en',
    ]);
  }

  /**
   * Test that custom header metatag is set on page
   */
  public function testMetatag() : void {
    $this->drupalGet($this->node->toUrl('canonical'));
    $this->assertSession()
      ->elementAttributeContains('css', 'meta[name="helfi_content_type"]', 'content', 'page');
  }

}
