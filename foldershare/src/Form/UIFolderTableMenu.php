<?php

namespace Drupal\foldershare\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\File;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\foldershare\Constants;
use Drupal\foldershare\Settings;
use Drupal\foldershare\Messages;
use Drupal\foldershare\Utilities;
use Drupal\foldershare\Entity\FolderShare;
use Drupal\foldershare\Entity\FolderShareAccessControlHandler;
use Drupal\foldershare\Plugin\FolderShareCommandManager;
use Drupal\foldershare\Plugin\FolderShareCommand\FolderShareCommandInterface;
use Drupal\foldershare\Ajax\OpenErrorDialogCommand;
use Drupal\foldershare\Ajax\FormDialog;
use Drupal\foldershare\Form\EditCommand;

/**
 * Creates a form for the file and folder table menu.
 *
 * This form manages a menu of commands (e.g. new, delete, copy) and their
 * operands. The available commands are defined by command plugins whose
 * attributes sort commands into categories and define the circumstances
 * under which a command may be envoked (e.g. for one item or many, on files,
 * on folders, etc.).
 *
 * This form includes:
 * - A field to specify the command.
 * - A field containing the current selection, if any.
 * - A set of fields with command operands, such as the parent and destination.
 * - A file field used to specify uploaded files.
 * - A submit button.
 *
 * This form is hidden and none of its fields are intended to be directly
 * set by a user. Instead, Javascript fills in the form based upon the
 * current row selection, the results of a drag-and-drop, or the file choices
 * from a browser-provided file dialog.
 *
 * Javascript creates a menu button and pull-down menu of commands.
 * Javascript also creates a context menu of commands for table rows.
 * Scripting handles row selection, multi-row selection, double-click to
 * open, drag-and-drop of rows, and drag-and-drop of files from the desktop
 * into the table for uploading.
 *
 * This form *requires* that a view nearby on the page contain a base UI
 * view field plugin that adds selection checkboxes and entity attributes
 * to all rows in the view.
 *
 * <B>Warning:</B> This class is strictly internal to the FolderShare
 * module. The class's existance, name, and content may change from
 * release to release without any promise of backwards compatability.
 *
 * @ingroup foldershare
 */
class UIFolderTableMenu extends FormBase {

  use RedirectDestinationTrait;

  /*--------------------------------------------------------------------
   *
   * Constants.
   *
   *--------------------------------------------------------------------*/

  /**
   * Indicate whether to enable AJAX support in the form.
   *
   * When TRUE, additional flags are set in the form in order to enable
   * an AJAX-based submit and dialogs.
   *
   * When FALSE, the AJAX features are disabled.
   *
   * This constant is normally TRUE, but it can be set to FALSE to help
   * debug the user interface.
   *
   * TODO AJAX support is currently incomplete. There are quirks when
   * dealing with an embedded view and whether it supports AJAX paging.
   *
   * @var bool
   */
  const ENABLE_AJAX = FALSE;

  /**
   * The names of well-known command categories, in menu order.
   *
   * The order here reflects the preferred ordering of categories in the UI.
   * Categories that have no commands are skipped in the user interface.
   *
   * @var string[]
   */
  static private $MENU_WELL_KNOWN_COMMAND_CATEGORIES = [
    'open',
    'import',
    'export',
    'close',
    'edit',
    'delete',
    'copy & move',
    'save',
    'archive',
    'settings',
    'administer',
  ];

  /*--------------------------------------------------------------------
   *
   * Fields.
   *
   *--------------------------------------------------------------------*/

  /**
   * The plugin command manager.
   *
   * @var \Drupal\foldershare\FolderShareCommand\FolderShareCommandManager
   */
  protected $commandPluginManager;

  /**
   * The current validated command prior to its execution.
   *
   * The command already has its configuration set. It has been
   * validated, but has not yet had access controls checked and it
   * has not been executed.
   *
   * @var \Drupal\foldershare\FolderShareCommand\FolderShareCommandInterface
   */
  protected $command;

  /*--------------------------------------------------------------------
   *
   * Construction.
   *
   *--------------------------------------------------------------------*/

  /**
   * Constructs a new form.
   *
   * @param \Drupal\foldershare\FolderShareCommand\FolderShareCommandManager $pm
   *   The command plugin manager.
   */
  public function __construct(FolderShareCommandManager $pm) {
    $this->commandPluginManager = $pm;
    $this->command = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('foldershare.plugin.manager.foldersharecommand')
    );
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
    //
    // Get context attributes
    // ----------------------
    // Get attributes of the current context, including:
    // - the ID of the page entity, if any
    // - the page entity's kind.
    // - the user's access permissions.
    //
    // The page entity ID comes from the form build arguments.
    $args = $formState->getBuildInfo()['args'];

    if (empty($args) === TRUE || (int) $args[0] < (0)) {
      // There is no page entity ID in the build arguments, or that
      // ID is a (-1). This indicates this form is being built for
      // a root folder group page where there is no page entity.
      $pageEntityId = (int) (-1);
      $pageEntity   = NULL;
      $kind         = 'none';
      $perm         = FolderShareAccessControlHandler::getAccessSummary(NULL);
    }
    else {
      // There is a parent entity ID in the build arguments. Load that
      // entity. If loading the entity fails, revert to the root folder
      // list case.
      $pageEntityId = (int) $args[0];
      $pageEntity   = FolderShare::load($pageEntityId);
      if ($pageEntity !== NULL) {
        $kind = $pageEntity->getKind();
      }
      else {
        $kind = 'none';
      }

      $perm = FolderShareAccessControlHandler::getAccessSummary($pageEntity);
    }

    // Get the user.
    $user = \Drupal::currentUser();

    // Create an array that lists the names of access control operators
    // (e.g. "view", "update", "delete") if the user has permission for
    // those operators on this parent entity (if there is one).
    $access = [];
    foreach ($perm as $op => $allowed) {
      if ($allowed === TRUE) {
        $access[] = $op;
      }
    }

    //
    // Form setup
    // ----------
    // Add the form's ID as a class for styling.
    //
    // The 'drupalSettings' attribute defines arbitrary data that can be
    // attached to the page. Here we use it to:
    // - Flag whether AJAX is enabled.
    // - Give the ID and human-readable name of this module.
    // - Give translations for various terms.
    // - Give singular and plural translations of entity kinds.
    // - List all installed commands and their attributes.
    $kinds = [
      FolderShare::ROOT_FOLDER_KIND,
      FolderShare::FOLDER_KIND,
      FolderShare::FILE_KIND,
      FolderShare::IMAGE_KIND,
      FolderShare::MEDIA_KIND,
      'rootfoldergroup',
      'item',
    ];

    $kindTerms = [];
    foreach ($kinds as $k) {
      $kindTerms[$k] = [
        'singular' => Utilities::mapKindToTerm($k),
        'plural'   => Utilities::mapKindToTerms($k),
      ];
    }

    $categoryTerms = [];
    foreach (self::$MENU_WELL_KNOWN_COMMAND_CATEGORIES as $cat) {
      $categoryTerms[$cat] = t($cat);
    }

    $form['#attached']['drupalSettings']['foldershare'] = [
      'ajaxEnabled'   => self::ENABLE_AJAX,
      'module'        => [
        'id'          => Constants::MODULE,
        'title'       => \Drupal::service('module_handler')->getName(Constants::MODULE),
      ],
      'page'          => [
        'id'          => $pageEntityId,
        'kind'        => $kind,
      ],
      'user'          => [
        'id'          => $user->id(),
        'accountName' => $user->getAccountName(),
        'displayName' => $user->getDisplayName(),
        'pageAccess'  => $access,
      ],
      'terminology'   => [
        'kinds'       => $kindTerms,
        'text'        => [
          'this'      => t('this'),
          'menu'      => t('menu'),
          'upload_dnd_not_supported' => t(Messages::UPLOAD_DND_NOT_SUPPORTED),
          'upload_dnd_invalid_singular' => t(Messages::UPLOAD_DND_INVALID_SINGULAR),
          'upload_dnd_invalid_plural' => t(Messages::UPLOAD_DND_INVALID_PLURAL),
        ],
        'categories'  => $categoryTerms,
      ],
      'categories'    => self::$MENU_WELL_KNOWN_COMMAND_CATEGORIES,
      'commands'      => Settings::getAllowedCommandDefinitions(),
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
    $uiClass            = 'foldershare-folder-table-menu';
    $submitClass        = $uiClass . '-submit';
    $uploadClass        = $uiClass . '-upload';
    $commandClass       = $uiClass . '-commandname';
    $selectionClass     = $uiClass . '-selection';
    $parentIdClass      = $uiClass . '-parentId';
    $destinationIdClass = $uiClass . '-destinationId';

    // When AJAX is enabled, add an AJAX callback to the submit button.
    $submitAjax = '';
    if (self::ENABLE_AJAX === TRUE) {
      $submitAjax = [
        'callback'   => '::submitFormAjax',
        'event'      => 'submit',
        'trigger_as' => $submitClass,
      ];
    }

    $form['#attributes']['class'][] = 'foldershare-folder-table-menu-form';

    $form[$uiClass] = [
      '#type'              => 'container',
      '#weight'            => -100,
      '#attributes'        => [
        'class'            => [$uiClass],
      ],

      // The form acts as a container for Javascript-generated menus and
      // menu buttons. Those items need to be visible, but the form's
      // inputs for sending a command back to the server never need to
      // be visible. These are grouped into a container that is marked hidden.
      'hiddenGroup'        => [
        '#type'            => 'container',
        '#attributes'      => [
          'class'          => ['hidden'],
        ],

        // Add the command plugin ID field to indicate the selected command.
        // Later, when a user selects a command, Javascript sets this field
        // to the command plugin's unique ID.
        //
        // The command is a required input. There's no point in submitting
        // a form without one.
        //
        // Implementation note: There is no documented maximum plugin ID
        // length, but most Drupal IDs are limited to 256 characters. So
        // we use that limit.
        $commandClass        => [
          '#type'            => 'textfield',
          '#maxlength'       => 256,
          '#size'            => 1,
          '#default_value'   => '',
          '#required'        => TRUE,
        ],

        // Add the selection field that lists the IDs of selected entities.
        // Later, when a user selects a command, Javascript sets this field
        // to a list of entity IDs for zero or more items currently selected
        // from the view.
        //
        // The selection is optional. Some commands have no selection (such
        // as "New folder" and "Upload files").
        //
        // Implementation note: The textfield will be set with a JSON-encoded
        // string containing the list of numeric entity IDs for selected
        // entities in the view. There is no maximum view length if the site
        // disables paging. Drupal's default field maximum is 128 characters,
        // which is probably sufficient, but it is conceivable it could be
        // exceeded for a large selection. The HTML default maximum is
        // 524288 characters, so we use that.
        $selectionClass      => [
          '#type'            => 'textfield',
          '#maxlength'       => 524288,
          '#size'            => 1,
          '#default_value'   => $formState->getValue($selectionClass),
        ],

        // Add the parent field that gives the parent ID of a file or folder.
        //
        // The parent ID is not required, though Javascript always sets it.
        // If not set, the entity ID of the current page is used. This is
        // typical for most command use. Drag-and-drop operations, however,
        // may move/copy/upload items into a selected subfolder, and that
        // subfolder's parent ID is set in this field.
        //
        // Implementation note: The textfield may be left empty or set to
        // a single numeric entity ID. Since entity IDs are 64 bits, the
        // maximum number of characters here is 20 digits.
        $parentIdClass    => [
          '#type'            => 'textfield',
          '#maxlength'       => 24,
          '#size'            => 1,
          '#default_value'   => $formState->getValue($parentIdClass),
        ],

        // Add the destination field that gives the entity ID of a
        // destination folder for move/copy operations. Later, when a user
        // selects a command, Javascript may set this field if the destination
        // is known. If the field is left empty, the command may prompt for
        // the move/copy destination.
        //
        // The destination ID field is optional. Most commands don't use it.
        //
        // Implementation note: The textfield may be left empty or set to
        // a single numeric entity ID. Since entity IDs are 64 bits, the
        // maximum number of characters here is 20 digits.
        $destinationIdClass    => [
          '#type'            => 'textfield',
          '#maxlength'       => 24,
          '#size'            => 1,
          '#default_value'   => $formState->getValue($destinationIdClass),
        ],

        // Add the file field for uploading files. Later, when a user
        // selects a command that needs to upload a file, Javascript invokes
        // the browser's file dialog to set this field.
        //
        // The field needs to have a processing callback to set up file
        // extension filtering, if file extension limitations are enabled
        // for the module.
        //
        // The upload field is optional and it is only used by file upload
        // commands.
        $uploadClass         => [
          '#type'            => 'file',
          '#multiple'        => TRUE,
          '#process'         => [
            [
              get_class($this),
              'processFileField',
            ],
          ],
        ],

        // Add the submit button for the form. Javascript triggers the
        // submit when a command is selected from the menu.
        $submitClass         => [
          '#type'            => 'submit',
          '#value'           => '',
          '#name'            => $submitClass,
          '#ajax'            => $submitAjax,
        ],
      ],
    ];

    return $form;
  }

  /**
   * Process the file field in the view UI form to add extension handling.
   *
   * The 'file' field directs the browser to prompt the user for one or
   * more files to upload. This prompt is done using the browser's own
   * file dialog. When this module's list of allowed file extensions has
   * been set, and this function is added as a processing function for
   * the 'file' field, it adds the extensions to the list of allowed
   * values used by the browser's file dialog.
   *
   * @param mixed $element
   *   The form element to process.
   * @param Drupal\Core\Form\FormStateInterface $formState
   *   The current form state.
   * @param mixed $completeForm
   *   The full form.
   */
  public static function processFileField(
    &$element,
    FormStateInterface $formState,
    &$completeForm) {

    // Let the file field handle the '#multiple' flag, etc.
    File::processFile($element, $formState, $completeForm);

    // Get the list of allowed file extensions for FolderShare files.
    $extensions = FolderShare::getFileAllowedExtensions();

    // If there are extensions, add them to the form element.
    if (empty($extensions) === FALSE) {
      // The extensions list is space separated without leading dots. But
      // we need comma separated with dots. Map one to the other.
      $list = [];
      foreach (mb_split(' ', $extensions) as $ext) {
        $list[] = '.' . $ext;
      }

      $element['#attributes']['accept'] = implode(',', $list);
    }

    return $element;
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
    //
    // Setup
    // -----
    // Set up classes.
    $uiClass            = 'foldershare-folder-table-menu';
    $commandClass       = $uiClass . '-commandname';
    $selectionClass     = $uiClass . '-selection';
    $parentIdClass      = $uiClass . '-parentId';
    $destinationIdClass = $uiClass . '-destinationId';
    $uploadClass        = $uiClass . '-upload';

    //
    // Get parent ID (if any)
    // ----------------------
    // The parent entity ID, if present, is the sole URL argument.
    $args = $formState->getBuildInfo()['args'];
    if (empty($args) === TRUE) {
      // No parent entity. Default to showing a root folder group's list.
      $parentId = (int) (-1);
    }
    else {
      // Parent entity ID should be the sole argument. Load it.
      // Loading could fail and return a NULL if the ID is bad.
      $parentId = (int) $args[0];
    }

    // If a parent ID is in the form, it overrides the URL, which describes
    // the page the operation began on, and not necessary the context in
    // which it should take place.
    $formParentId = $formState->getValue($parentIdClass);
    if (empty($formParentId) === FALSE) {
      $parentId = intval($formParentId);
    }

    //
    // Get command
    // -----------
    // The command's plugin ID is set in the command field. Get it and
    // the command definition.
    $commandId = $formState->getValue($commandClass);
    if (empty($commandId) === TRUE) {
      // Fail. This should never happen. The field is required, so the form
      // should not be submittable without a command.
      $formState->setErrorByName(
        $commandClass,
        $this->t('Please select a command from the menu.'));
      return;
    }

    //
    // Get selection (if any)
    // ----------------------
    // The selection field contains a JSON encoded array of entity IDs
    // in the selection. The list could be empty.
    $selectionIds = json_decode($formState->getValue($selectionClass), TRUE);

    //
    // Get destination (if any)
    // ------------------------
    // The destination field contains a single numeric entity ID for the
    // destination of a move/copy. The value could be empty.
    $destinationId = $formState->getValue($destinationIdClass);
    if (empty($destinationId) === TRUE) {
      $destinationId = '';
    }
    else {
      $destinationId = intval($destinationId);
    }

    //
    // Create configuration
    // --------------------
    // Create an initial command configuration.
    $configuration = [
      'parentId'      => $parentId,
      'destinationId' => $destinationId,
      'selectionIds'  => $selectionIds,
      'uploadClass'   => $uploadClass,
    ];

    //
    // Prevalidate
    // -----------
    // Create a command instance and pre-validate.
    $command = $this->prevalidateCommand($commandId, $configuration);
    if (is_string($command) === TRUE) {
      $formState->setErrorByName($commandClass, $command);
      return;
    }

    $this->command = $command;
  }

  /*--------------------------------------------------------------------
   *
   * Form submit (no-AJAX)
   *
   *--------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    //
    // Setup
    // -----
    // If AJAX is in use, let the AJAX callback handle the command.
    // It is automatically called after calling this function.
    if (self::ENABLE_AJAX === TRUE) {
      return;
    }

    // If there is no command, previous validation failed. Validation
    // errors should already have been reported.
    if ($this->command === NULL) {
      return;
    }

    //
    // Redirect to page
    // ----------------
    // If the command needs to redirect to a special page (such as a form),
    // go there.
    if ($this->command->hasRedirect() === TRUE) {
      $url = $this->command->getRedirect();
      if (empty($url) === TRUE) {
        $url = Url::fromRoute('<current>');
      }

      $formState->setRedirectUrl($url);
      return;
    }

    //
    // Redirect to form
    // ----------------
    // If the command needs a configuration form, redirect to a page that
    // hosts the form.
    try {
      if ($this->command->hasConfigurationForm() === TRUE) {
        $parameters = [
          'pluginId'      => $this->command->getPluginId(),
          'configuration' => $this->command->getConfiguration(),
          'url'           => \Drupal::request()->getRequestUri(),
        ];

        $encoded = base64_encode(json_encode($parameters));

        $formState->setRedirect(
          'entity.foldersharecommand.plugin',
          ['encoded' => $encoded]);
        return;
      }
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return FALSE;
    }

    //
    // Execute
    // -------
    // The command doesn't need more operands. Validate, check permissions,
    // then execute. Failures either set form element errors or set page
    // errors with drupal_set_message().
    try {
      if ($this->validateCommand($this->command) === TRUE ||
          $this->validateCommandAccess($this->command) === TRUE) {
        $this->executeCommand($this->command);
      }
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return FALSE;
    }

    $this->command = NULL;
  }

  /*--------------------------------------------------------------------
   *
   * Form submit (AJAX)
   *
   *--------------------------------------------------------------------*/

  /**
   * Handles form submission via AJAX.
   *
   * @param array $form
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The input values for the form's elements.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Returns an AJAX response.
   */
  public function submitFormAjax(array &$form, FormStateInterface $formState) {
    //
    // Report form errors
    // ------------------
    // If prevalidation failed, there will be form errors to report.
    if ($formState->hasAnyErrors() === TRUE) {
      $response = new AjaxResponse();

      $dialog = new OpenErrorDialogCommand(t('Errors'));
      $dialog->setFromFormErrors($formState);
      $response->addCommand($dialog);

      return $response;
    }

    //
    // Redirect to page
    // ----------------
    // If the command needs to redirect to a special page (such as a form),
    // go there.
    if ($this->command->hasRedirect() === TRUE) {
      $url = $this->command->getRedirect();
      if (empty($url) === TRUE) {
        // For some reason, the command claimed it has a redirect, but then
        // it didn't provide a redirect URL. So, default to redirecting
        // back to the current page.
        $url = Url::fromRoute('<current>');
      }

      // Return a redirect response.
      $response = new AjaxResponse();
      $response->addCommand(new RedirectCommand($url->toString()));
      return $response;
    }

    //
    // Embed a form in a dialog
    // ------------------------
    // If the command needs a configuration form, add that form to a dialog.
    if ($this->command->hasConfigurationForm() === TRUE) {
      $parameters = [
        'pluginId'      => $this->command->getPluginId(),
        'configuration' => $this->command->getConfiguration(),
        'url'           => \Drupal::request()->getRequestUri(),
        'enableAjax'    => self::ENABLE_AJAX,
      ];
      $encoded = base64_encode(json_encode($parameters));

      // Build a form for the command.
      $form = \Drupal::formBuilder()->getForm(
        EditCommand::class,
        $encoded);

      $form['#attached']['library'][] = Constants::LIBRARY_MAINUI;

      $response = new AjaxResponse();
      $response->setAttachments($form['#attached']);

      $title = $this->command->getPluginDefinition()['label'];
      $dialog = new FormDialog(
        $title,
        $form,
        [
          'width' => '75%',
        ]);
      $response->addCommand($dialog);

      return $response;
    }

    //
    // Execute
    // -------
    // The command doesn't need more operands. Validate, check permissions,
    // then execute. Failures either set form element errors or set page
    // errors with drupal_set_message(). Pull out those errors and report them.
    if ($this->validateCommand($this->command) === FALSE ||
        $this->validateCommandAccess($this->command) === FALSE) {
      $this->command = NULL;

      $response = new AjaxResponse();

      if ($formState->hasAnyErrors() === TRUE) {
        $cmd = new OpenErrorDialogCommand(t('Errors'));
        $cmd->setFromFormErrors($formState);
        $response->addCommand($cmd);
        return $response;
      }

      if (drupal_set_message() !== NULL) {
        $cmd = new OpenErrorDialogCommand(t('Errors'));
        $cmd->setFromPageMessages();
        $response->addCommand($cmd);
        return $response;
      }

      // Otherwise validation or access checks failed, but didn't
      // report errors? Unlikely.
      $cmd = new OpenErrorDialogCommand(t('Confused!'), t('Unknown failure!'));
      $response->addCommand($cmd);
      return $response;
    }

    $this->executeCommand($this->command);
    $this->command = NULL;

    // If there were any error or warning messages, report them.
    $msgs      = drupal_get_messages(NULL, FALSE);
    $nErrors   = count($msgs['error']);
    $nWarnings = count($msgs['warning']);
    $nStatus   = count($msgs['status']);
    if (($nErrors + $nWarnings + $nStatus) !== 0) {
      $response = new AjaxResponse();
      $cmd = new OpenErrorDialogCommand(t('Notice'));
      $cmd->setFromPageMessages();
      $response->addCommand($cmd);
      return $response;
    }

    // Otherwise the command executed and did not provide any error,
    // warning, or status messages. Just refresh the page.
    $response = new AjaxResponse();
    $url = Url::fromRoute('<current>');
    $response->addCommand(new RedirectCommand($url->toString()));
    return $response;
  }

  /*--------------------------------------------------------------------
   *
   * Command handling
   *
   *--------------------------------------------------------------------*/

  /**
   * Creates an instance of a command and pre-validates it.
   *
   * @param string $commandId
   *   The command plugin ID.
   * @param array $configuration
   *   The initial command configuration.
   *
   * @return string|\Drupal\foldershare\Plugin\FolderShareCommand\FolderShareCommandInterface
   *   Returns an initialized and pre-validated command, or an error
   *   message if the command is not recognized or cannot be validated.
   */
  protected function prevalidateCommand(
    string $commandId,
    array $configuration) {

    if ($this->commandPluginManager === NULL) {
      return (string) t('Missing command plugin manager');
    }

    // Get the command definition.
    $commandDef = $this->commandPluginManager->getDefinition($commandId, FALSE);
    if ($commandDef === NULL) {
      return (string) t(
        'Unrecognized command ID "@id".',
        [
          '@id' => $commandId,
        ]);
    }

    // Create a command instance.
    $command = $this->commandPluginManager->createInstance(
      $commandDef['id'],
      $configuration);

    // Validate with parent constraints.
    try {
      $command->validateParentConstraints();
    }
    catch (\Exception $e) {
      // There are only two errors possible:
      // - Bad parent ID.
      // - Bad parent type.
      //
      // The parent ID comes from the view argument and could be invalid.
      //
      // The parent type comes from the parent, and it may be inappropriate
      // for the command.
      return (string) t(
          'The "@command" command is not available here. @message',
          [
            '@command' => $commandDef['label'],
            '@message' => $e->getMessage(),
          ]);
    }

    // Validate the selection constraints.
    try {
      $command->validateSelectionConstraints();
    }
    catch (\Exception $e) {
      // There are a variety of errors possible:
      // - Selection provided for a command that doesn't want one.
      // - No selection provided for a command that does want one.
      // - Selection has too many items.
      // - Selection has the wrong type of items.
      return $e->getMessage();
    }

    return $command;
  }

  /**
   * Validates a command and reports errors.
   *
   * The command is validated with its current configuration. On an error,
   * a message is posted to drupal_set_message().
   *
   * @param \Drupal\foldershare\Plugin\FolderShareCommand\FolderShareCommandInterface $command
   *   The command to validate. The configuration should already be set.
   *
   * @return bool
   *   Returns FALSE on failure, TRUE on success.
   */
  protected function validateCommand(FolderShareCommandInterface $command) {
    try {
      $command->validateConfiguration();
      return TRUE;
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return FALSE;
    }
  }

  /**
   * Validates a command's access permissions and reports errors.
   *
   * @param \Drupal\foldershare\Plugin\FolderShareCommand\FolderShareCommandInterface $command
   *   The command to validate. The configuration should already be set.
   *
   * @return bool
   *   Returns FALSE on failure, TRUE on success.
   */
  protected function validateCommandAccess(FolderShareCommandInterface $command) {
    $user = \Drupal::currentUser();
    if ($command->access($user, FALSE) === FALSE) {
      $label = $command->getPluginDefinition()['label'];
      drupal_set_message(t(
        'You do not have sufficient permissions to perform the "@command" command on the selected items.',
        [
          '@command' => $label,
        ]),
        'error');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Executes a command and reports errors.
   *
   * @param \Drupal\foldershare\Plugin\FolderShareCommand\FolderShareCommandInterface $command
   *   The command to execute. The configuration should already be set.
   *
   * @return bool
   *   Returns FALSE on failure, TRUE on success.
   */
  protected function executeCommand(FolderShareCommandInterface $command) {
    try {
      // Execute!
      $command->clearExecuteMessages();
      $command->execute();

      // Get its messages, if any, and transfer them to the page.
      foreach ($command->getExecuteMessages() as $type => $list) {
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

      return TRUE;
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return FALSE;
    }
  }

}
