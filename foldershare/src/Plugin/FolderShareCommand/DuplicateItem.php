<?php

namespace Drupal\foldershare\Plugin\FolderShareCommand;

use Drupal\foldershare\Messages;
use Drupal\foldershare\Utilities;

/**
 * Defines a command plugin to duplicate a file or folder.
 *
 * The command copies all selected entities and adds them back into the
 * same parent folder or root folder list. Duplication recurses through
 * all folder content as well.
 *
 * Configuration parameters:
 * - 'parentId': the parent folder, if any.
 * - 'selectionIds': selected entities to duplicate.
 *
 * @ingroup foldershare
 *
 * @FolderShareCommand(
 *  id              = "foldersharecommand_duplicate",
 *  label           = @Translation("Duplicate"),
 *  pageTitle       = @Translation("Duplicate @operand"),
 *  menuNameDefault = @Translation("Duplicate"),
 *  menuName        = @Translation("Duplicate @operand"),
 *  tooltip         = @Translation("Duplicate files and folders"),
 *  description     = @Translation("Duplicate files and folders, and all of their contents."),
 *  category        = "copy & move",
 *  weight          = 20,
 *  specialHandling = {
 *    "create",
 *  },
 *  parentConstraints = {
 *    "kinds"   = {
 *      "none",
 *      "any",
 *    },
 *    "access"  = "update",
 *  },
 *  selectionConstraints = {
 *    "types"   = {
 *      "one",
 *      "many",
 *    },
 *    "kinds"   = {
 *      "any",
 *    },
 *    "access"  = "view",
 *  },
 * )
 */
class DuplicateItem extends FolderShareCommandBase {

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
    //
    // Loop through the selection, which is grouped by kind. Go through
    // all of the kinds and duplicate each item of each kind.
    $nItems = 0;
    $firstItem = NULL;
    $selection = $this->getSelection();
    foreach ($selection as $items) {
      foreach ($items as $item) {
        $item->duplicate();

        // Keep track of the number of items and the first item.
        ++$nItems;
        if ($firstItem === NULL) {
          $firstItem = $item;
        }
      }
    }

    // Return a status message. Be specific if practical.
    if ($nItems === 1) {
      // One item. Refer to it by name.
      $this->addExecuteMessage(t(
        Messages::DUPLICATE_DONE_ONE_ITEM,
        [
          '@kind' => Utilities::mapKindToTerm($firstItem->getKind()),
          '@name' => $firstItem->getName(),
        ]),
        'status');
    }
    elseif (count($selection) === 1) {
      // One kind of items. Refer to them by kind.
      $this->addExecuteMessage(t(
        Messages::DUPLICATE_DONE_MULTIPLE_ITEMS,
        [
          '@number' => $nItems,
          '@kinds'  => Utilities::mapKindToTerms($firstItem->getKind()),
        ]),
        'status');
    }
    else {
      // Multiple kinds of items.
      $this->addExecuteMessage(t(
        Messages::DUPLICATE_DONE_MULTIPLE_ITEMS,
        [
          '@number' => $nItems,
          '@kinds'  => t('items'),
        ]),
        'status');
    }
  }

}
