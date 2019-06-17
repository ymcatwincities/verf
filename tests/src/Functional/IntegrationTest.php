<?php

namespace Drupal\Tests\verf\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * Tests the integration between Views Entity Reference Filter and Views.
 *
 * @group verf
 */
class IntegrationTest extends BrowserTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['verf_test_views'];

  /**
   * The node type used in the tests.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  private $nodeType;

  /**
   * The view we're using.
   *
   * @var \Drupal\views\Entity\View
   */
  private $view;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->nodeType = $this->drupalCreateContentType();
    $this->createEntityReferenceField('node', $this->nodeType->id(), 'field_refs', 'Refs', 'node');

  }

  /**
   * Tests that the Views Entity Reference Filter works.
   */
  public function testFilterWorks() {
    $referencedNode = $this->drupalCreateNode(['type' => $this->nodeType->id()]);
    $referencingNode = $this->drupalCreateNode([
      'type' => $this->nodeType->id(),
      'field_refs' => [['target_id' => $referencedNode->id()]],
    ]);

    $this->drupalGet('verftest');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertPageContainsNodeTeaserWithText($referencedNode->getTitle());
    $this->assertPageContainsNodeTeaserWithText($referencingNode->getTitle());

    $this->getSession()->getPage()->selectFieldOption('Refs (VERF selector)', $referencedNode->getTitle());
    $this->getSession()->getPage()->pressButton('Apply');

    $this->assertPageContainsNodeTeaserWithText($referencingNode->getTitle());
    $this->assertPageNotContainsNodeTeaserWithText($referencedNode->getTitle());
  }

  /**
   * Asserts that a node teaser containing the given text is present.
   *
   * @param string $text
   *   The text to look for.
   *
   * @throws \Exception
   */
  protected function assertPageContainsNodeTeaserWithText($text) {
    try {
      $this->assertPageNotContainsNodeTeaserWithText($text);

    }
    catch (\Exception $e) {
      // Text was found, we're good.
      return;
    }

    throw new \Exception("No teaser could be found with the text: $text");
  }

  /**
   * Asserts that no node teaser containing the given text is present.
   *
   * @param string $text
   *   The text that must not be present.
   */
  protected function assertPageNotContainsNodeTeaserWithText($text) {
    $teasers = $this->getSession()->getPage()->findAll('css', '.node--view-mode-teaser');

    foreach ($teasers as $teaser) {
      $this->assertNotContains($text, $teaser->getText());
    }
  }

}
