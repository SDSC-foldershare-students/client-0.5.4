<?php

namespace Drupal\foldershare\Plugin\FolderShareCommand;

use Drupal\Core\Url;

use Drupal\foldershare\Constants;

/**
 * Defines a command plugin to open an entity.
 *
 * The command is a dummy that does not use a configuration and does not
 * execute to change the entity. Instead, this command is used solely to
 * create an entry in command menus and support a redirect to the entity's
 * view page.
 *
 * Configuration parameters:
 * - 'parentId': the parent folder, if any.
 * - 'selectionIds': selected entities to edit.
 *
 * @ingroup foldershare
 *
 * @FolderShareCommand(
 *  id              = "foldersharecommand_open",
 *  label           = @Translation("Open"),
 *  pageTitle       = @Translation("Open @operand"),
 *  menuNameDefault = @Translation("Open..."),
 *  menuName        = @Translation("Open @operand..."),
 *  tooltip         = @Translation("Open a file or folder"),
 *  description     = @Translation("Open and view the fields of a file or folder."),
 *  category        = "open",
 *  weight          = 20,
 *  parentConstraints = {
 *    "kinds"   = {
 *      "none",
 *      "folder",
 *      "rootfolder",
 *      "rootfoldergroup",
 *    },
 *    "access"  = "none",
 *  },
 *  selectionConstraints = {
 *    "types"   = {
 *      "one",
 *    },
 *    "kinds"   = {
 *      "any",
 *    },
 *    "access"  = "view",
 *  },
 * )
 */
class OpenItem extends FolderShareCommandBase {

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
    // Do nothing.
  }

  /*---------------------------------------------------------------------
   *
   * Redirects.
   *
   * These functions direct that the command must redict the user to
   * a stand-alone page.
   *
   *---------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function hasRedirect() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirect() {
    //
    // Get the selected item. If none, use the parent.
    $ids = $this->getSelectionIds();
    if (empty($ids) === TRUE) {
      $id = $this->getParentId();
    }
    else {
      $id = reset($ids);
    }

    return Url::fromRoute(
      Constants::ROUTE_FOLDERSHARE,
      [
        Constants::ROUTE_FOLDERSHARE_ID => $id,
      ]);
  }

}
