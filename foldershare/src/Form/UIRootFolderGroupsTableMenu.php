<?php

namespace Drupal\foldershare\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\foldershare\Constants;

/**
 * Creates a form for the root folder groups table menu.
 *
 * This form manages a very abbreviated menu that contains a single
 * specialized "Open folder..." command. The command simply opens a new page
 * for a selected root folder group (e.g. "personal", "public", etc.).
 *
 * Javascript creates a menu button and a pull-down menu containing the
 * "Open folder..." command. Javascript also creates a context menu of the
 * same command on table rows. Scripting handles single-item selection
 * of a root folder group to open, and double-click to open.
 *
 * Unlike a full file and folder table, the root folder groups table does
 * not support:
 * - Multi-row selection.
 * - Drag-and-drop of rows.
 * - Drag-and-drop of files.
 * - A file dialog for uploading.
 *
 * Technically, a form is not required since it just uses Javascript to
 * reload the page associated with the selected row. The form is never
 * submitted.
 *
 * <B>Warning:</B> This class is strictly internal to the FolderShare
 * module. The class's existance, name, and content may change from
 * release to release without any promise of backwards compatability.
 *
 * @ingroup foldershare
 */
class UIRootFolderGroupsTableMenu extends FormBase {

  /*--------------------------------------------------------------------
   *
   * Construction.
   *
   *--------------------------------------------------------------------*/

  /**
   * Constructs a new form.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /*--------------------------------------------------------------------
   *
   * Form setup.
   *
   *--------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $name = str_replace('\\', '_', get_class($this));
    return mb_convert_case($name, MB_CASE_LOWER);
  }

  /*--------------------------------------------------------------------
   *
   * Form build.
   *
   *--------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $formState = NULL) {
    // Form setup
    // ----------
    // Add the form's ID as a class for styling.
    //
    // The 'drupalSettings' attribute defines arbitrary data that can be
    // attached to the page. Here we use it to:
    // - Give the page type.
    // - Give the ID and human-readable name of this module.
    // - Give translations for various terms.
    $form['#attributes']['class'][] =
      'foldershare-root-folder-groups-table-menu-form';

    $user = \Drupal::currentUser();
    $moduleName = \Drupal::service('module_handler')->getName(Constants::MODULE);

    $form['#attached']['drupalSettings']['foldershare'] = [
      'module'        => [
        'id'          => Constants::MODULE,
        'title'       => $moduleName,
      ],
      'page'          => [
        'id'          => (-1),
        'kind'        => 'rootfoldergroups',
      ],
      'user'          => [
        'id'          => $user->id(),
        'accountName' => $user->getAccountName(),
        'displayName' => $user->getDisplayName(),
      ],
      'terminology'   => [
        'text'        => [
          'menu'      => t('menu'),
          'open'      => t('Open Folder...'),
        ],
      ],
    ];

    //
    // Create UI
    // ---------
    // The UI is primarily built by Javascript based upon the command list
    // and other information in the settings attached to the form above.
    // The remainder of the form contains input fields and a submit button
    // for sending a command's ID and operands back to the server.
    //
    // Set up classes.
    $uiClass = 'foldershare-root-folder-groups-table-menu';

    $form[$uiClass] = [
      '#type'       => 'container',
      '#weight'     => -100,
      '#attached'   => [
        'library'   => [
          Constants::LIBRARY_MAINUI,
        ],
      ],
      '#attributes' => [
        'class'     => [
          $uiClass,
        ],
      ],
    ];

    return $form;
  }

  /*--------------------------------------------------------------------
   *
   * Form validate
   *
   *--------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState) {
    // Do nothing.
  }

  /*--------------------------------------------------------------------
   *
   * Form submit
   *
   *--------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    // Do nothing.
  }

}
