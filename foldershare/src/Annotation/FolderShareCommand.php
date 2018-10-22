<?php

namespace Drupal\foldershare\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the annotation for command plugins.
 *
 * A command is similar to a Drupal action. It has a configuration containing
 * operands for the command, and an execute() function to apply the command
 * to the configuration. Some commands also have configuration forms to
 * prompt for operands.
 *
 * Commands differ from Drupal actions by including extensive annotation
 * that governs how that command appears in menus and under what circumstances
 * the command may be applied. For instance, annotation may indicate if
 * the command applies to files or folders or both, whether it requires a
 * selection and if that selection can include more than one item, and
 * what access permissions the user must have on the parent folder and on
 * the selection.
 *
 * Annotation for a command can be broken down into groups of information:
 *
 * - Identification:
 *   - the unique machine name for the plugin.
 *
 * - User interface:
 *   - the labels for the plugin.
 *   - a description for the plugin.
 *   - the user interface category and weight for presenting the command.
 *
 * - Constraints and access controls:
 *   - the parent folder constraints and access controls required, if needed.
 *   - the selection constraints and access controls required, if needed.
 *   - the destination constraints and access controls required, if needed.
 *   - any special handling needed.
 *
 * There are several plugin labels that may be specified. Each is used in
 * a different context:
 *
 * - "label" is a generic name for the command, such as "Edit" or "Delete".
 *   This label may be used in error messages, such as "The Delete command
 *   requires a selection."
 *
 * - "pageTitle" is the title used for the command's configuration form or
 *   page, if it has such a form or page. The text may include the "@operand"
 *   marker, which will be replaced with the name of an item or a kind,
 *   such as "Delete @operand". If not given, this defaults to the value
 *   of "label".
 *
 * - "menuNameDefault" is the command name used in menus when no better text
 *   is available in "menuName". The default text is primarily used when the
 *   menu item is disabled. If not given, this defaults to the value of "label".
 *
 * - "menuName" is the command name to use in menus. This is primarily used
 *   for selectable menu items and the text may include the "@operand" marker,
 *   which will be replaced with the name of an item or a kind, such as
 *   "Edit @operand...". If not given, this defaults to the value of
 *   "menuNameDefault".
 *
 * "label", "pageTitle", "menuNameDefault", and "menuName" should all use
 * title-case except for small connecting words like "of", "in", and "and".
 *
 * The "@operand" in "pageTitle" and "menuName" is replaced with text that
 * varies depending upon use.  Possibilities include:
 *
 * - Replaced with a singular name of the kind of operand, such as
 *   "Delete file" or "Delete folder". If the context is the current item,
 *   the word "this" may be inserted, such as "Delete this file".
 *
 * - Replaced with a plural name of the kind for all operands, such as
 *   "Delete files" or "Delete folders".
 *
 * - Replaced with a plural "items" if the kinds of the operands is mixed,
 *   such as "Delete items".
 *
 * - Replaced with the quoted singular name of the operand when there is
 *   just one item (typically for form titles), such as "Delete "Bob's folder"".
 *
 * The plugin namespace for commands is "Plugin\FolderShareCommand".
 *
 * @ingroup foldershare
 *
 * @Annotation
 */
class FolderShareCommand extends Plugin {

  /*--------------------------------------------------------------------
   * Identification
   *--------------------------------------------------------------------*/

  /**
   * The unique machine-name plugin ID.
   *
   * The value is taken from the plugin's 'id' annotation.
   *
   * The ID must be a unique string used to identify the plugin. Often it
   * is closely related to a module namespace and class name.
   *
   * @var string
   */
  public $id = '';

  /*--------------------------------------------------------------------
   * User interface
   *--------------------------------------------------------------------*/

  /**
   * The generic command label for user interfaces.
   *
   * The label is used by user interface code for buttons and error messages.
   * The value is required.
   *
   * The text should be translated and use title-case where each word is
   * capitalized, except for small connecting words like "of" or "in".
   *
   * Examples:
   * - "Change Owner".
   * - "Delete".
   * - "Edit".
   * - "Rename".
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label = '';

  /**
   * The title for user interface pages, dialogs, and forms.
   *
   * The title is used by user interface code to create a title for any
   * pages, forms, or dialogs needed by the command. Some commands do not
   * have any of these and will not need a title. If no title is given,
   * the value defaults to the value of the 'label' field.
   *
   * The text should be translated and use title-case where each word is
   * capitalized, except for small connecting words like "of" or "in".
   *
   * The text may include "@operand" to mark where the name of an item or
   * item kind should be inserted.
   *
   * Examples:
   * - "Change Owner of @operand".
   * - "Delete @operand".
   * - "Edit @operand".
   * - "Rename @operand".
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $pageTitle = '';

  /**
   * The name for disabled user interface menus.
   *
   * The menu name is used for user interface code to create a menu item for
   * the command when the command is disabled and not available to the user.
   * If no menu name is given, the value defaults to the value of the
   * 'label' field.
   *
   * The text should be translated and use title-case where each word is
   * capitalized, except for small connecting words like "of" or "in".
   *
   * Examples:
   * - "Change Owner...".
   * - "Delete...".
   * - "Edit...".
   * - "Rename...".
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $menuNameDefault = '';

  /**
   * The name for enabled user interface menus.
   *
   * The menu name is used for user interface code to create a menu item for
   * the command when the command is enabled and available to the user. If
   * no menu name is given, the value defaults to the value of the
   * 'menuNameDefault' field.
   *
   * The text should be translated and use title-case where each word is
   * capitalized, except for small connecting words like "of" or "in".
   *
   * The text may include "@operand" to mark where the name of an item or
   * item kind should be inserted.
   *
   * Examples:
   * - "Change Owner of @operand...".
   * - "Delete @operand...".
   * - "Edit @operand...".
   * - "Rename @operand...".
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $menuName = '';

  /**
   * The command tooltip for user interfaces.
   *
   * The tool tip is used for user interface code that presents a very brief
   * note about what a command does. The text should be just a few words,
   * translated, and without punctuation.
   *
   * Examples:
   * - "Change owner of item".
   * - "Delete item".
   * - "Edit item".
   * - "Change name of item".
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $tooltip = '';

  /**
   * The command description for user interfaces.
   *
   * The description is used for user interface code that presents an
   * explanation of a command. This is typically at the top of a page, form,
   * or dialog dedicated to the command. The text should be a sentence or
   * two, translated, and ending with punctuation.
   *
   * Examples:
   * - "Change the owner of a file or folder, and all items within the folder."
   * - "Delete the item and all of its contents. This cannot be undone."
   * - "Edit the item's description, fields, and settings."
   * - "Change the name of the item."
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * The command's category within user interfaces.
   *
   * The category gives the name of a group into which the command should be
   * sorted when it is presented in a menu. Category names must be lower case.
   *
   * The module defines a set of well-known categories that may be used.
   * Any category name not on this list is appended to the end of menus
   * based upon these categories.
   *
   * Well-known categories are (in order):
   * - "open".
   * - "import".
   * - "export".
   * - "close".
   * - "edit".
   * - "delete".
   * - "copy & move".
   * - "save".
   * - "archive".
   * - "settings".
   * - "administer".
   *
   * @var string
   *
   * @see \Drupal\Core\Plugin\CategorizingPluginManagerTrait
   * @see \Drupal\Component\Plugin\CategorizingPluginManagerInterface
   */
  public $category = '';

  /**
   * The commands weight among other commands in the same category.
   *
   * The value is taken from the plugin's 'weight' annotation and should
   * be a positive or negative integer.
   *
   * Weights are used to sort commands within a category before they
   * are presented within a menu, toobar of buttons, etc. Higher weights
   * are listed later in the category.
   *
   * @var int
   */
  public $weight = 0;

  /*--------------------------------------------------------------------
   * Constraints and access controls
   *--------------------------------------------------------------------*/

  /**
   * The command's parent constraints, if any.
   *
   * Defined by the 'parentConstraints' annotation, this optional value
   * is an associative array with keys:
   * - 'kinds': an optional list of the kinds of parents supported.
   * - 'access': an optional access operation that the parent must support.
   *
   * User interfaces may build different menus for different kinds of parent
   * (such as for folders vs. root folders) and place commands into those
   * menus based upon their parent constraints.
   *
   * <b>Kinds:</b>
   * The optional 'kinds' field lists the kinds of parent supported.
   * Valid values are:
   * - Any kind supported by FolderShare (e.g. 'file', 'folder').
   * - 'rootfoldergroup' for use with root folder groups (e.g. 'personal').
   * - 'none': command does not use a parent.
   * - 'any': command is used with any item.
   *
   * If omitted or empty, 'kinds' defaults to 'none'.
   *
   * <b>Access:</b>
   * The optional 'access' field names an access control operation that
   * must be supported by the parent. Valid values are:
   * - Any access control operation supported by FolderShare (e.g. 'view').
   * - 'none' to indicate no parent access is required.
   *
   * If omitted or empty, 'access' defaults to 'none' if 'kinds' is only
   * 'none', and 'view' otherwise.
   *
   * @var array
   */
  public $parentConstraints = NULL;

  /**
   * The command's selection constraints, if any.
   *
   * Defined by the 'selectionConstraints' annotation, this optional
   * value is an associative array with keys:
   * - 'types': an optional indicator of the selection size.
   * - 'kinds': an optional list of the kinds of selected items supported.
   * - 'access': an optional access operation that the selection must support.
   *
   * User interfaces must provide a way for a user to specify the selection
   * of files and folders.
   *
   * <b>Types:</b>
   * The optional 'types' field lists the types of selection allowed.
   * Valid values are:
   * - 'none': the command supports having no selection.
   * - 'parent': the command supports use of the parent instead of a selection.
   * - 'one': the command supports having a single item selection.
   * - 'many': the command supports having a multiple item selection.
   *
   * If omitted or empty, 'types' defaults to 'none'.
   *
   * <b>Kinds:</b>
   * The optional 'kinds' field lists the kinds of selected items supported.
   * Valid values are:
   * - Any kind supported by FolderShare (e.g. 'file', 'folder', 'rootfolder').
   * - 'rootfoldergroup' for use with root folder groups (e.g. 'personal').
   * - 'any' for use with any item.
   *
   * If omitted or empty, 'kinds' defaults to 'none' if 'types' is only
   * 'none', and 'any' otherwise.
   *
   * <b>Access:</b>
   * The optional 'access' field names an access control operation that
   * must be supported by the selection. The field must be present if
   * the access 'types' is anything but 'none'.  Valid values are:
   * - Any access control operation supported by FolderShare (e.g. 'view').
   *
   * If omitted or empty, 'access' defaults to 'none' if 'kinds' is only
   * 'none', and 'view' otherwise.
   *
   * @var array
   */
  public $selectionConstraints = NULL;

  /**
   * The command's destination constraints, if any.
   *
   * Defined by the 'destinationConstraints' annotation, this optional
   * value is an associative array with keys:
   * - 'kinds': an optional list of the kinds of selected items supported.
   * - 'access': an optional access operation that the selection must support.
   *
   * User interfaces must provide a way for a user to specify the destination
   * file or folder.
   *
   * <b>Kinds:</b>
   * The optional 'kinds' field lists the kinds of destination items supported.
   * Valid values are:
   * - Any kind supported by FolderShare (e.g. 'file', 'folder').
   * - 'rootfoldergroup' for use with root folder groups (e.g. 'personal').
   * - 'any' for use with any item.
   *
   * If omitted or empty, 'kinds' defaults to 'any'.
   *
   * <b>Access:</b>
   * The optional 'access' field names an access control operation that
   * must be supported by the destination. Valid values are:
   * - Any access control operation supported by FolderShare (e.g. 'view').
   *
   * If omitted or empty, 'access' defaults to 'none' if 'kinds' is only
   * 'none', and 'update' otherwise.
   *
   * @var array
   */
  public $destinationConstraints = NULL;

  /**
   * The command's special needs, if any.
   *
   * Defined by the 'specialHandling' annotation, this optional value
   * lists special case handling required by the command. Valid values are:
   * - 'create': the command creates FolderShare objects, so create access
   *   is required by the user.
   * - 'upload': the command uploads files, so special file processing is
   *   required.
   *
   * If omitted or empty, 'special' defaults to no special handling.
   *
   * @var array
   */
  public $specialHandling = NULL;

}
