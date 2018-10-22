<?php

namespace Drupal\foldershare\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\Html;

use Drupal\foldershare\Entity\FolderShare;
use Drupal\foldershare\Entity\FolderShareAccessControlHandler;

/**
 * Formats a FolderShare entity name with a link, icon, & attributes.
 *
 * This formatter is applicable to any name string that is for a FolderShare
 * entity's name. The entity name may be formatted to include:
 * - A file/folder name.
 * - An anchor around the name that links to the entity's page.
 * - Classes that themes use to add a file/folder MIME type icon.
 * - Attributes that a script-driven user interface may use.
 *
 * @FieldFormatter(
 *   id    = "foldershare_name",
 *   label = @Translation("FolderShare entity name, link, icon, & attributes"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class FolderShareName extends FormatterBase {

  /*---------------------------------------------------------------------
   *
   * Configuration.
   *
   * These functions set up formatter features and default settings.
   *
   *---------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'showIcon'      => TRUE,
      'linkToEntity'  => TRUE,
      'addAttributes' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // Get the initial settings summary. The summary needs to include brief
    // text that summarizes the current plugin settings.
    $summary = parent::settingsSummary();

    // Get current settings.
    $doIcon       = $this->getSetting('showIcon') !== 0;
    $doLink       = $this->getSetting('linkToEntity') !== 0;
    $doAttributes = $this->getSetting('addAttributes') !== 0;

    // Add text.
    if ($doLink === TRUE) {
      $summary[] = t('Name linked to referenced entity.');
    }
    else {
      $summary[] = t('No entity link on name.');
    }

    if ($doIcon === TRUE) {
      $summary[] = t('Name with MIME-type icon.');
    }
    else {
      $summary[] = t('No MIME-type icon on name.');
    }

    if ($doAttributes === TRUE) {
      $summary[] = t('Entity attributes included for UI scripts.');
    }
    else {
      $summary[] = t('No entity attributes for UI scripts.');
    }

    return $summary;
  }

  /*---------------------------------------------------------------------
   *
   * Settings form.
   *
   * These functions handle settings on the formatter for a particular use.
   *
   *---------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $fieldDef) {
    // The entity containing the field to be formatted must be
    // a FolderShare entity, and the field must be the 'name' field.
    return $fieldDef->getTargetEntityTypeId() === FolderShare::ENTITY_TYPE_ID &&
      $fieldDef->getName() === 'name';
  }

  /**
   * Returns a brief description of the formatter.
   *
   * @return string
   *   Returns a brief translated description of the formatter.
   */
  protected function getDescription() {
    return $this->t("Show the name of the file or folder. Optionally preceed the name with a MIME-type icon and link the name to the entity's page. When using browser scripting for a user interface (such as for a Views table), optionally include attributes on the name, such as its kind and the user's access to it.");
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    //
    // Start with the parent form.
    $form = parent::settingsForm($form, $formState);

    // Add a description.
    $form['description'] = [
      '#type'   => 'item',
      '#markup' => $this->getDescription(),
    ];

    // Add a checkbox to enable/disable linking the name to the entity.
    $form['linkToEntity'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t("Link the file/folder name to the entity's page"),
      '#default_value' => $this->getSetting('linkToEntity'),
      '#description'   => $this->t("Add an anchor to the name so that clicking on the name loads the entity's page. (Recommended)"),
    ];

    // Add a checkbox to enable/disable including an icon before the name.
    $form['showIcon'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show a MIME-type icon before the file/folder name'),
      '#default_value' => $this->getSetting('showIcon'),
      '#description'   => $this->t('Add classes to the name that default styling and specific themes use to include a MIME-type icon, such as a small folder image for folders, and small file images for different types of files. (Recommended)'),
    ];

    // Add a checkbox to enable/disable UI attributes.
    $form['addAttributes'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Include entity attributes used by UI scripting'),
      '#default_value' => $this->getSetting('addAttributes'),
      '#description'   => $this->t('Add data attributes to the anchor to enable browser scripting to build and maintain a user interface that recognizes the type of entity being shown. (Recommended)'),
    ];

    return $form;
  }

  /*---------------------------------------------------------------------
   *
   * View.
   *
   * These functions present a field's value.
   *
   *---------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langCode) {
    if (empty($items) === TRUE) {
      return [];
    }

    // The $items array has a list of string items to format, but we
    // need entities.
    $entities = [];
    foreach ($items as $delta => $item) {
      $entities[$delta] = $item->getEntity();
    }

    // At this point, the $entities array has a list of items to format.
    // We need to return an array with identical indexing and corresponding
    // render elements for those items.
    //
    // Get settings.
    $doIcon       = $this->getSetting('showIcon') !== 0;
    $doLink       = $this->getSetting('linkToEntity') !== 0;
    $doAttributes = $this->getSetting('addAttributes') !== 0;

    // Loop through items.
    $build = [];
    foreach ($entities as $delta => $entity) {
      // Create a link for the entity and add it to the returned array.
      $build[$delta] = $this->format(
        $entity,
        $langCode,
        $doIcon,
        $doLink,
        $doAttributes);
    }

    return $build;
  }

  /**
   * Builds and returns a formatted field for icon-linked values.
   *
   * When given a field, language code, and booleans to enable/disable
   * MIME-type based icons and links, the function returns a render
   * element array to present that field's values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A entity to return a value for.
   * @param string $langCode
   *   The target language.
   * @param bool $doIcon
   *   When TRUE, the entity will be marked with classes so that themes can
   *   add a MIME-type icon.
   * @param bool $doLink
   *   When TRUE, the entity name will be enclosed within an anchor to the
   *   entity's view page.
   * @param bool $doAttributes
   *   When TRUE, the entity name will be marked with entity attributes that
   *   a scripting user interface may use to recognize the entity.
   *
   * @return array
   *   The render element to present the field.
   */
  protected function format(
    EntityInterface $entity,
    $langCode,
    $doIcon = TRUE,
    $doLink = TRUE,
    $doAttributes = TRUE) {

    //
    // Setup
    // -----
    // Get the entity's text, kind, MIME type, and URL.
    $name = $entity->getName();
    $kind = $entity->getKind();
    $mime = $entity->getMimeType();
    $url  = $entity->toUrl();
    $attr = [];

    if ($doAttributes === TRUE) {
      // When including attributes, get the user's access to the entity.
      $access = [];
      $summary = FolderShareAccessControlHandler::getAccessSummary($entity);
      foreach ($summary as $op => $tf) {
        if ($tf === TRUE) {
          $access[] = $op;
        }
      }

      // Create an attributes array for use below.
      $prefix = 'data-foldershare-';
      $attr = [
        $prefix . 'id'     => $entity->id(),
        $prefix . 'kind'   => $kind,
        $prefix . 'access' => implode(',', $access),
      ];
    }

    $classes = [];
    $attached = [];
    if ($doIcon === TRUE) {
      // When including an icon, get the classes needed so that themes can
      // mark the item with an icon.
      //
      // The Drupal Core File module defines conventional classes that mark
      // an item as a file of varying type. While Drupal Core does not provide
      // icons, some Core themes do. This module automatically includes those
      // icons and adds icons for folders.
      $classes = [
        'file',
      ];

      switch ($kind) {
        case FolderShare::FOLDER_KIND:
          $classes[] = 'file--mime-folder-directory';
          $classes[] = 'file--folder';
          break;

        case FolderShare::ROOT_FOLDER_KIND:
          $classes[] = 'file--mime-rootfolder-directory';
          $classes[] = 'file--folder';
          break;

        default:
          $classes[] = 'file--mime-' . strtr(
            $mime,
            [
              '/'   => '-',
              '.'   => '-',
            ]);
          $classes[] = 'file--' . file_icon_class($mime);
          break;
      }

      $attached = [
        'library'   => ['file/drupal.file'],
      ];
    }

    //
    // Format
    // ------
    // Add link, or non-link text, along with above configured classes
    // and attributes.
    if ($doIcon === TRUE) {
      $render = [
        '#type'       => 'link',
        '#title'      => $name,
        '#url'        => $url,
        '#cache'      => [
          'contexts'  => ['url.site'],
        ],
        '#attached'   => $attached,
        '#attributes' => array_merge($attr, [
          'class'     => $classes,
        ]),
      ];
    }
    else {
      $render = [
        '#markup'     => Html::escape($name),
        '#attached'   => $attached,
        '#attributes' => array_merge($attr, [
          'class'     => $classes,
        ]),
      ];
    }

    return $render;
  }

}
