<?php

namespace Drupal\foldershare\Plugin\FolderShareCommand;

use Drupal\Core\Form\FormStateInterface;

use Drupal\foldershare\Constants;
use Drupal\foldershare\Messages;
use Drupal\foldershare\Utilities;
use Drupal\foldershare\FileUtilities;
use Drupal\foldershare\Entity\FolderShare;
use Drupal\foldershare\Entity\Exception\ValidationException;

/**
 * Defines a command plugin to rename a file or folder.
 *
 * The command sets the name of a single selected entity.
 *
 * Configuration parameters:
 * - 'parentId': the parent folder, if any.
 * - 'selectionIds': selected entity to rename.
 * - 'name': the new name.
 *
 * @ingroup foldershare
 *
 * @FolderShareCommand(
 *  id              = "foldersharecommand_rename",
 *  label           = @Translation("Rename"),
 *  pageTitle       = @Translation("Rename @operand"),
 *  menuNameDefault = @Translation("Rename..."),
 *  menuName        = @Translation("Rename @operand..."),
 *  tooltip         = @Translation("Change name of file or folder"),
 *  description     = @Translation("Change the name of the file or folder."),
 *  category        = "edit",
 *  weight          = 20,
 *  parentConstraints = {
 *    "kinds"   = {
 *      "none",
 *      "any",
 *    },
 *    "access"  = "none",
 *  },
 *  selectionConstraints = {
 *    "types"   = {
 *      "parent",
 *      "one",
 *    },
 *    "kinds"   = {
 *      "any",
 *    },
 *    "access"  = "update",
 *  },
 * )
 */
class RenameItem extends FolderShareCommandBase {

  /*--------------------------------------------------------------------
   *
   * Configuration.
   *
   * These functions initialize a default configuration for the command,
   * and validate command-specific parameters.
   *
   *--------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Include room for the new name in the configuration.
    $config = parent::defaultConfiguration();
    $config['name'] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function validateParameters() {
    if ($this->parametersValidated === TRUE) {
      // Already validated.
      return;
    }

    // Get the new name from the configuration.
    $newName = $this->configuration['name'];

    // Check if the name is legal.
    if (FileUtilities::isNameLegal($newName) === FALSE) {
      throw new ValidationException(t(
        Messages::NAME_INVALID,
        [
          '@name' => $newName,
        ]));
    }

    // Get the parent folder, if any.
    $parent = $this->getParent();

    // Get the selected item. If none, use the parent instead.
    $items = $this->getSelection();
    if (empty($items) === TRUE) {
      $item = $parent;
    }
    else {
      $item = reset($items)[0];
    }

    $itemId = (int) $item->id();

    // Check if the new name is unique within the parent folder or
    // root list.
    if ($parent !== NULL) {
      if ($parent->isNameUnique($newName, $itemId) === FALSE) {
        throw new ValidationException(t(
          Messages::NAME_IN_USE,
          [
            '@name' => $newName,
          ]));
      }
    }
    else {
      if (FolderShare::isRootNameUnique($newName, $itemId) === FALSE) {
        throw new ValidationException(t(
          Messages::NAME_IN_USE,
          [
            '@name' => $newName,
          ]));
      }
    }

    $this->parametersValidated = TRUE;
  }

  /*--------------------------------------------------------------------
   *
   * Execute.
   *
   * These functions execute the command using the current configuration.
   *
   *--------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $items = $this->getSelection();
    if (empty($items) === TRUE) {
      $item = $this->getParent();
    }
    else {
      $item = reset($items)[0];
    }

    $item->rename($this->configuration['name']);

    $this->addExecuteMessage(t(
      Messages::RENAME_DONE,
      [
        '@kind' => Utilities::mapKindToTerm($item->getKind()),
        '@name' => $item->getName(),
      ]),
      'status');
  }

  /*--------------------------------------------------------------------
   *
   * Configuration form.
   *
   * These functions build and respond to the command's configuration
   * form to collect additional command operands.
   *
   *--------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function hasConfigurationForm() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmitButtonName() {
    return 'Rename';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $formState) {
    //
    // Build a form to prompt for the new name. Start by getting the
    // current item name.
    $items = $this->getSelection();
    if (empty($items) === TRUE) {
      $item = $this->getParent();
    }
    else {
      $item = reset($items)[0];
    }

    $this->configuration['name'] = $item->getName();

    if ($item->isRootFolder() === TRUE) {
      $description = t(
        Messages::RENAME_DESCRIPTION_WITHIN_ROOT,
        []);
    }
    else {
      $description = t(
        Messages::RENAME_DESCRIPTION_WITHIN_FOLDER,
        []);
    }

    // Create the form.
    $form[Constants::MODULE . '-rename'] = [
      '#type'          => 'textfield',
      '#title'         => t('New name:'),
      '#size'          => 0,
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#default_value' => $this->configuration['name'],
      '#description'   => $description,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(
    array &$form,
    FormStateInterface $formState) {
    //
    // Validate that the new name is usable.
    $nameField = Constants::MODULE . '-rename';

    $this->configuration['name'] = $formState->getValue($nameField);

    try {
      $this->validateParameters();
    }
    catch (\Exception $e) {
      $formState->setErrorByName($nameField, $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(
    array &$form,
    FormStateInterface $formState) {
    //
    // Execute the command.
    try {
      $this->execute();

      // Get its messages, if any, and transfer them to the page.
      foreach ($this->getExecuteMessages() as $type => $list) {
        switch ($type) {
          case 'error':
          case 'warning':
          case 'status':
            break;

          default:
            $type = 'status';
            break;
        }

        foreach ($list as $message) {
          drupal_set_message($message, $type);
        }
      }
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
  }

}
