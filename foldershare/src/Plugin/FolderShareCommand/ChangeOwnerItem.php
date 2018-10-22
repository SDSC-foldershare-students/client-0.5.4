<?php

namespace Drupal\foldershare\Plugin\FolderShareCommand;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

use Drupal\foldershare\Messages;
use Drupal\foldershare\Utilities;
use Drupal\foldershare\Entity\FolderShare;
use Drupal\foldershare\Entity\Exception\ValidationException;

/**
 * Defines a command plugin to change ownership of files or folders.
 *
 * The command sets the UID for the owner of all selected entities.
 * Owenrship changes recurse through all folder content as well.
 *
 * Configuration parameters:
 * - 'parentId': the parent folder, if any.
 * - 'selectionIds': selected entities to change ownership on.
 * - 'uid': the UID of the new owner.
 *
 * @ingroup foldershare
 *
 * @FolderShareCommand(
 *  id              = "foldersharecommand_change_owner",
 *  label           = @Translation("Change Owner"),
 *  pageTitle       = @Translation("Change Owner of @operand"),
 *  menuNameDefault = @Translation("Change Owner..."),
 *  menuName        = @Translation("Change Owner of @operand..."),
 *  tooltip         = @Translation("Change the owner of files and folders"),
 *  description     = @Translation("Change the owner of files and folders, including the content of subfolders."),
 *  category        = "administer",
 *  weight          = 10,
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
 *      "many",
 *    },
 *    "kinds"   = {
 *      "any",
 *    },
 *    "access"  = "chown",
 *  },
 * )
 */
class ChangeOwnerItem extends FolderShareCommandBase {

  /*--------------------------------------------------------------------
   *
   * Configuration.
   *
   * These functions initialize a default configuration for the command,
   * and validate parameters particular to this command.
   *
   *--------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Add room for the UID.
    $config = parent::defaultConfiguration();
    $config['uid'] = '';
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

    // Get the new UID from the configuration and check if it is valid.
    $uid = $this->configuration['uid'];
    $user = User::load($uid);
    if ($user === NULL) {
      throw new ValidationException(t(
        Messages::USER_INVALID_ID,
        [
          '@id' => $uid,
        ]));
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
    // Get the user ID for the new owner of the items.
    $uid = $this->configuration['uid'];
    $user = User::load($uid);

    // Get the selection. If empty, operate upon the parent instead.
    $selection = $this->getSelection();
    if (empty($selection) === FALSE) {
      // Change ownership on the selection.
      $nItems = 0;
      $firstItem = NULL;
      foreach ($this->getSelection() as $items) {
        foreach ($items as $item) {
          $item->changeAllOwnerId($uid);

          $nItems++;
          if ($firstItem === NULL) {
            $firstItem = $item;
          }
        }
      }

      // Report on the change.
      if ($nItems === 1) {
        $this->addExecuteMessage(t(
          Messages::CHOWN_DONE_ONE_ITEM,
          [
            '@kind'    => Utilities::mapKindToTerm($firstItem->getKind()),
            '@name'    => $firstItem->getName(),
            '@account' => $user->getDisplayName(),
          ]),
          'status');
      }
      elseif (count($selection) === 1) {
        $this->addExecuteMessage(t(
          Messages::CHOWN_DONE_MULTIPLE_ITEMS,
          [
            '@number'  => $nItems,
            '@kinds'   => Utilities::mapKindToTerms($firstItem->getKind()),
            '@account' => $user->getDisplayName(),
          ]),
          'status');
      }
      else {
        $this->addExecuteMessage(t(
          Messages::CHOWN_DONE_MULTIPLE_ITEMS,
          [
            '@number'  => $nItems,
            '@kinds'   => t('items'),
            '@account' => $user->getDisplayName(),
          ]),
          'status');
      }
    }
    else {
      // Change ownership on the parent.
      $parent = $this->getParent();
      $parent->changeAllOwnerId($uid);

      // Report on the change.
      $this->addExecuteMessage(t(
        Messages::CHOWN_DONE_ONE_ITEM,
        [
          '@kind'    => Utilities::mapKindToTerm($parent->getKind()),
          '@name'    => $parent->getName(),
          '@account' => $user->getDisplayName(),
        ]),
        'status');
    }
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
    return 'Change';
  }

  /**
   * Return description text based upon the current selection.
   *
   * @return string
   *   Returns the description text.
   */
  private function getDescription() {
    // Set the description based upon what was selected.
    $selection = $this->getSelection();
    $nKinds = 0;
    $nItems = 0;
    $firstKind = '';
    $firstItem = NULL;
    $includesFolders = FALSE;

    foreach ($selection as $kind => $entities) {
      $nEntities = count($entities);
      if ($nEntities !== 0) {
        $nItems += $nEntities;
        $nKinds++;
        if ($firstKind === '') {
          $firstKind = $kind;
        }

        if ($firstItem === NULL) {
          $firstItem = $entities[0];
        }

        if ($kind === FolderShare::FOLDER_KIND ||
            $kind === FolderShare::ROOT_FOLDER_KIND) {
          $includesFolders = TRUE;
        }
      }
    }

    if ($nItems === 0) {
      $firstItem = $this->getParent();
      $firstKind = $firstItem->getKind();
      $nKinds = 1;
      $nItems = 1;
      $includesFolders = TRUE;
    }

    if ($nKinds === 1) {
      // There is only one kind of item in the selection (such as all
      // files or all folders). Messages can use the name of this kind
      // to make the message more specific.
      if ($nItems === 1) {
        // One kind, one item.
        $operandName = Utilities::mapKindToTerm($firstKind, MB_CASE_LOWER);

        if ($firstKind === FolderShare::FOLDER_KIND ||
            $firstKind === FolderShare::ROOT_FOLDER_KIND) {
          $description = t(
            Messages::CHOWN_DESCRIPTION_ONE_FOLDER,
            [
              '@kind' => $operandName,
            ]);
        }
        else {
          $description = t(
            Messages::CHOWN_DESCRIPTION_ONE_FILE,
            [
              '@kind' => $operandName,
            ]);
        }
      }
      else {
        // One kind, multiple items.
        $operandName = Utilities::mapKindToTerms($firstKind, MB_CASE_LOWER);
        if ($firstKind === FolderShare::FOLDER_KIND ||
            $firstKind === FolderShare::ROOT_FOLDER_KIND) {
          // Multiple folders.
          $description = t(
            Messages::CHOWN_DESCRIPTION_MULTIPLE_ITEMS,
            [
              '@kinds' => $operandName,
            ]);
        }
        else {
          // Multiple files.
          $description = t(
            Messages::CHOWN_DESCRIPTION_MULTIPLE_FILES,
            [
              '@kinds' => $operandName,
            ]);
        }
      }
    }
    else {
      // There are multiple kinds, such as a mix of files and folders.
      // This can only occur if there are also multiple items, so use
      // messages that are plural.
      if ($includesFolders === TRUE) {
        $description = t(
          Messages::CHOWN_DESCRIPTION_MULTIPLE_ITEMS,
          [
            '@kinds' => t('items'),
          ]);
      }
      else {
        $description = t(
          Messages::CHOWN_DESCRIPTION_MULTIPLE_FILES,
          [
            '@kinds' => t('items'),
          ]);
      }
    }

    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $formState) {
    //
    // Build a form to prompt for the new owner.
    //
    // Use the UID of the current user as the default value.
    $account = \Drupal::currentUser();
    $this->configuration['uid'] = $account->id();

    // Get a list of all user IDs and their display names.
    $uids = \Drupal::entityQuery('user')->execute();
    $users = User::loadMultiple($uids);

    // Set the description based upon what was selected.
    $form['description'] = [
      '#type'   => 'html_tag',
      '#tag'    => 'p',
      '#value'  => $this->getDescription(),
      '#weight' => (-100),
    ];

    // Use a dropdown menu for fewer than 200 users, and an auto-complete
    // text field for more than that.
    if (count($uids) < 200) {
      // Use a dropdown menu.
      $options = [];
      foreach ($users as $user) {
        $options[$user->id()] = $user->getDisplayName();
      }

      asort($options, SORT_NATURAL);

      // Create the form.
      $form['owner'] = [
        '#type'          => 'select',
        '#title'         => t('New owner:'),
        '#required'      => TRUE,
        '#default_value' => $this->configuration['uid'],
        '#options'       => $options,
      ];
    }
    else {
      // Use autocomplete.
      // Create the form.
      $form['owner'] = [
        '#type'          => 'entity_autocomplete',
        '#title'         => t('New owner'),
        '#required'      => TRUE,
        '#target_type'   => 'user',
        '#selection_settings' => [
          'include_anonymous' => TRUE,
        ],
        '#default_value' => User::load($this->configuration['uid']),
        '#validate_reference' => FALSE,
        '#size'          => 20,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(
    array &$form,
    FormStateInterface $formState) {
    //
    // Validate that the new UID is usable.
    $this->configuration['uid'] = $formState->getValue('owner');

    try {
      $this->validateParameters();
    }
    catch (\Exception $e) {
      $formState->setErrorByName('owner', $e->getMessage());
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(
    array &$form,
    FormStateInterface $formState) {
    //
    // Run the command.
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
      $formState->setErrorByName('owner', $e->getMessage());
      return;
    }
  }

}
