<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\openid_connect\Entity\OpenIDConnectClientEntity;
use Drupal\user\Entity\User;

/**
 * Tests Tunnistamo redirect url subscriber.
 *
 * @coversDefaultClass \Drupal\helfi_proxy\EventSubscriber\TunnistamoRedirectUrlSubscriber
 * @group helfi_proxy
 */
class TunnistamoRedirectUrlSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'language',
    'openid_connect',
    'externalauth',
    'user',
    'file',
    'helfi_api_base',
    'path_alias',
    'helfi_tunnistamo',
    'helfi_proxy',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    // Core's KernelTestBase removes service_collector tags from
    // path_alias.path_processor service. We need to add them back
    // to test them.
    // @see \Drupal\KernelTests\KernelTestBase::register().
    $container->getDefinition('path_alias.path_processor')
      ->addTag('path_processor_inbound')
      ->addTag('path_processor_outbound');
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('path_alias');
    User::create([
      'name' => '',
      'uid' => 0,
    ])->save();
    $this->installConfig(['language', 'helfi_tunnistamo']);

    foreach (['fi', 'sv'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    $this->config('helfi_proxy.settings')
      ->set('prefixes', [
        'sv' => 'prefix-sv',
        'en' => 'prefix-en',
        'fi' => 'prefix-fi',
      ])
      ->save();

    $this->config('language.negotiation')
      ->set('url.prefixes', ['en' => 'en', 'fi' => 'fi', 'sv' => 'sv'])
      ->save();

    \Drupal::service('kernel')->rebuildContainer();
  }

  /**
   * Gets the Tunnistamo redirect url.
   *
   * @return string
   *   The redirect url.
   */
  private function getRedirectUri() : string {
    $client = OpenIDConnectClientEntity::load('tunnistamo')->getPlugin();
    $url = $client->authorize()->getTargetUrl();
    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    return $query['redirect_uri'];
  }

  /**
   * Make sure manually configured URL is preferred over automatic detection.
   */
  public function testReturnUrl() : void {
    $this->config('helfi_proxy.settings')
      ->set(ProxyManagerInterface::TUNNISTAMO_RETURN_URL, '/fi/jotain/openid-connect/tunnistamo')
      ->save();
    $this->assertEquals('http://localhost/fi/jotain/openid-connect/tunnistamo', $this->getRedirectUri());
  }

  /**
   * Make sure automatically determine return URL is used as fallback.
   */
  public function testFallbackReturnUrl() : void {
    $this->assertEquals('http://localhost/fi/prefix-fi/openid-connect/tunnistamo', $this->getRedirectUri());
  }

}
