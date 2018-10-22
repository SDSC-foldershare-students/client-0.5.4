<?php

namespace Drupal\foldershare\Plugin\FolderShareCommand;

use Drupal\foldershare\Messages;
use Drupal\foldershare\Utilities;
use Drupal\foldershare\Entity\FolderShare;

/**
 * Defines a command plugin to create a new folder.
 *
 * The command creates a new folder in the current parent folder, if any.
 * If there is no parent folder, the command creates a new root folder.
 * The new folder is empty and has a default name.
 *
 * Configuration parameters:
 * - 'parentId': the parent folder, if any.
 *
 * @ingroup foldershare
 *
 * @FolderShareCommand(
 *  id              = "foldersharecommand_new_folder",
 *  label           = @Translation("New Folder"),
 *  pageTitle       = @Translation("New Folder"),
 *  menuNameDefault = @Translation("New Folder"),
 *  menuName        = @Translation("New Folder"),
 *  tooltip         = @Translation("Create a new folder"),
 *  description     = @Translation("Create a new empty folder with a default name."),
 *  category        = "open",
 *  weight          = 10,
 *  specialHandling = {
 *    "create",
 *  },
 *  parentConstraints = {
 *    "kinds"   = {
 *      "none",
 *      "folder",
 *      "rootfolder",
 *    },
 *    "access"  = "update",
 *  },
 *  selectionConstraints = {
 *    "types"   = {
 *      "none",
 *    },
 *  },
 * )
 */
class NewFolderItem extends FolderShareCommandBase {

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
    // Get the parent folder, if any. When there is none, create a root
    // folder. Otherwise create a folder within the parent.
    $parent = $this->getParent();
    if ($parent === NULL) {
      $newFolder = FolderShare::createRootFolder('');
    }
    else {
      $newFolder = $parent->createFolder('');
    }

    $this->addExecuteMessage(t(
      Messages::NEWFOLDER_DONE,
      [
        '@kind' => Utilities::mapKindToTerm($newFolder->getKind()),
        '@name' => $newFolder->getName(),
      ]),
      'status');
  }

}
