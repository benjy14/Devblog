<?php

/**
 * @file
 * Contains \Drupal\editor\Entity\Editor.
 */

namespace Drupal\editor\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\editor\EditorInterface;

/**
 * Defines the configured text editor entity.
 *
 * @ConfigEntityType(
 *   id = "editor",
 *   label = @Translation("Text Editor"),
 *   entity_keys = {
 *     "id" = "format"
 *   }
 * )
 */
class Editor extends ConfigEntityBase implements EditorInterface {

  /**
   * The machine name of the text format with which this configured text editor
   * is associated.
   *
   * @var string
   */
  public $format;

  /**
   * The name (plugin ID) of the text editor.
   *
   * @var string
   */
  public $editor;

  /**
   * The array of text editor plugin-specific settings for the text editor.
   *
   * @var array
   */
  public $settings = array();

  /**
   * The array of image upload settings for the text editor.
   *
   * @var array
   */
  public $image_upload = array();

  /**
   * The filter format this text editor is associated with.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $filterFormat;

  /**
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $editorPluginManager;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->format;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $plugin = $this->editorPluginManager()->createInstance($this->editor);

    // Initialize settings, merging module-provided defaults.
    $default_settings = $plugin->getDefaultSettings();
    $default_settings += \Drupal::moduleHandler()->invokeAll('editor_default_settings', array($this->editor));
    \Drupal::moduleHandler()->alter('editor_default_settings', $default_settings, $this->editor);
    $this->settings += $default_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    // Create a dependency on the associated FilterFormat.
    $this->addDependency('entity', $this->getFilterFormat()->getConfigDependencyName());
    // @todo use EntityWithPluginBagInterface so configuration between config
    //   entity and dependency on provider is managed automatically.
    $definition = $this->editorPluginManager()->createInstance($this->editor)->getPluginDefinition();
    $this->addDependency('module', $definition['provider']);
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterFormat() {
    if (!$this->filterFormat) {
      $this->filterFormat = \Drupal::entityManager()->getStorage('filter_format')->load($this->format);
    }
    return $this->filterFormat;
  }

  /**
   * Returns the editor plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected function editorPluginManager() {
    if (!$this->editorPluginManager) {
      $this->editorPluginManager = \Drupal::service('plugin.manager.editor');
    }

    return $this->editorPluginManager;
  }

}
