<?php

namespace Drupal\foldershare;

/**
 * Defines constants use through the module.
 *
 * <B>Warning:</B> This class is strictly internal to the FolderShare
 * module. The class's existance, name, and content may change from
 * release to release without any promise of backwards compatability.
 *
 * This class defines constants for often-used text, services, libraries,
 * themes, permissions, routes, and so forth. In many cases, these values
 * repeat values defined in YML files.
 *
 * @internal
 * Routes, permissions, themes, libraries, and many other names are
 * defined in the module's many ".yml" files. These same names are needed
 * at run-time to refer to the defined routes, permissions, etc.
 *
 * While many YML-defined values are available at run-time via the
 * Drupal API, they are often referenced via name. This forces these
 * name strings to be embedded in the code.
 *
 * Frequent use of important hard-coded strings invites typos that can
 * cause routes, permissions, settings, and other values to not line up
 * with their intended targets. To reduce the effect of typos, and centralize
 * common names, these are all defined here in a module-wide list of
 * constants. Module code uses these constants by name, rather than
 * hard-coding typo-risky strings. This turns these strings into syntactic
 * objects in PHP, which the PHP parser can catch and report as errors if
 * typos creep in.
 * @endinternal
 *
 * @ingroup foldershare
 */
class Constants {

  /*--------------------------------------------------------------------
   *
   * Administration.
   *
   * These constants define the primary names and resources used
   * throughout the module.
   *
   *--------------------------------------------------------------------*/

  /**
   * The module machine name.
   *
   * This must match all uses of the module name:
   *
   * - The name of the module directory.
   * - The name of the "MODULE.module" file.
   * - The machine name in "MODULE.info.yml".
   * - The name of the \Drupal\MODULE namespace for the module.
   *
   * This module name should be used as the prefix for names that
   * have global scope, such as tables, settings, and routes.
   *
   * The module name should be used as a prefix for CSS classes
   * and IDs, and as the name of the module's function group in
   * Javascript.
   *
   * @var string
   */
  const MODULE = 'foldershare';

  /**
   * The module's settings (configuration) name.
   *
   * This must match the configuration name in "MODULE.info.yml'.
   *
   * This must match the file name and settings group in
   * 'config/schema/MODULE.settings.yml'. It also must match the
   * file name in 'install/MODULE.settings.yml'.
   *
   * @var string
   */
  const SETTINGS = 'foldershare.settings';

  /**
   * The module's search index name.
   *
   * This must match the name in the "FolderSearch" plugin's annotation.
   *
   * This must match the name of the search page configuration in
   * 'config/optional/search.page.SEARCH_INDEX.yml'.
   *
   * This search index only exists if the Drupal core search module
   * is enabled.
   *
   * @var string
   */
  const SEARCH_INDEX = 'foldershare_search';

  /*--------------------------------------------------------------------
   *
   * Libraries.
   *
   * These constants define the module's libraries. All values must
   * match those in 'MODULE.libraries.yml'.
   *
   *--------------------------------------------------------------------*/

  /**
   * The module primary library.
   *
   * This must match the library in 'MODULE.libraries.yml'.
   *
   * @var string
   */
  const LIBRARY_MODULE = 'foldershare/foldershare.module';

  /**
   * The module administration pages library.
   *
   * This must match the library in 'MODULE.libraries.yml'.
   *
   * @var string
   */
  const LIBRARY_ADMIN = 'foldershare/foldershare.module.admin';

  /**
   * The module main UI library.
   *
   * This must match the library in 'MODULE.libraries.yml'.
   *
   * @var string
   */
  const LIBRARY_MAINUI = 'foldershare/foldershare.module.mainui';

  /*--------------------------------------------------------------------
   *
   * Routes and route parameters.
   *
   * These constants define well-known module routes. All values must
   * match those in 'MODULE.routing.yml'.
   *
   *--------------------------------------------------------------------*/

  /**
   * The route to a FolderShare entity view page.
   *
   * This must match the route in 'MODULE.routing.yml'.
   *
   * @var string
   * @see self::ROUTE_FILDERSHARE_ID
   */
  const ROUTE_FOLDERSHARE = 'entity.foldershare.canonical';

  /**
   * The route to a FolderShare entity edit page.
   *
   * This must match the route in 'MODULE.routing.yml'.
   *
   * @var string
   * @see self::ROUTE_FILDERSHARE_ID
   */
  const ROUTE_FOLDERSHARE_EDIT = 'entity.foldershare.edit';

  /**
   * The FolderShare view and edit route parameter for an entity ID.
   *
   * @var string
   */
  const ROUTE_FOLDERSHARE_ID = 'foldershare';

  /**
   * The UI's command route to command-specific forms.
   *
   * @var string
   */
  const ROUTE_FOLDERSHARE_COMMAND_FORM = 'entity.foldersharecommand.plugin';

  /**
   * The route to the settings page.
   *
   * This must match the route in 'MODULE.routing.yml',
   * 'foldershare.links.menu.yml', and 'MODULE.links.task.yml'.
   *
   * @var string
   */
  const ROUTE_SETTINGS = 'entity.foldershare.settings';

  /**
   * The route to the file download handler.
   *
   * An argument 'file' must contain the entity ID of a File object wrapped
   * by a FolderShare entity.
   *
   * The route may include one or more query arguments. If present, the
   * 'foldershareprefix' query argument contains a string containing the
   * URL path prefix to prepend to the incoming file URI to generate a
   * redirect URL and return to whatever URL processing another
   * module (such as Image) may require for derived paths.
   *
   * Any other query and fragment arguments are passed along on the
   * redirect.
   *
   * This must match the route in 'MODULE.routing.yml'.
   *
   * @var string
   */
  const ROUTE_DOWNLOADFILE = 'entity.foldershare.file';

  /**
   * The query name for a path prefix for the file download handler.
   *
   * @var string
   */
  const ROUTE_DOWNLOADFILE_PREFIX = 'foldershareprefix';

  /**
   * The route to the entity download handler.
   *
   * The argument 'encoded' must contain a JSON base64 array containing
   * FolderShare entity IDs to download. They must all be children of
   * the same parent.
   *
   * This must match the route in 'MODULE.routing.yml'.
   *
   * @var string
   */
  const ROUTE_DOWNLOAD = 'entity.foldershare.download';

  /**
   * The route to the usage page.
   *
   * This must match the route in 'MODULE.routing.yml'.
   *
   * @var string
   */
  const ROUTE_USAGE = 'foldershare.reports.usage';

  /**
   * The route to the delete-all form.
   *
   * The argument 'eneity_type_id' must be the entity ID.
   *
   * This must match the route in 'system.routing.yml'.
   *
   * @var string
   */
  const ROUTE_DELETEALL = 'system.prepare_modules_entity_uninstall';

  /**
   * The route to the help page.
   *
   * The value must have the form "help.page.MODULE".  To get to the
   * help page for a module, the "name" parameter must be set to
   * the module's name.
   *
   * @var string
   */
  const ROUTE_HELP = 'help.page';

  /**
   * The route to the views UI page listing views.
   *
   * @var string
   */
  const ROUTE_VIEWS_UI = 'entity.view.collection';

  /**
   * The base route to the views UI page for a specific view.
   *
   * The name of the view to edit must be in the "view" parameter.
   *
   * @var string
   */
  const ROUTE_VIEWS_UI_VIEW = 'entity.view.edit_form';

  /*--------------------------------------------------------------------
   *
   * Virtual folders.
   *
   * A "virtual folder" is a named group of root folders. These are shown
   * in the main page listing destinations.
   *
   *--------------------------------------------------------------------*/

  const ROUTE_ROOTFOLDERS_GROUPS = 'entity.foldershare.rootfolders.groups';
  const ROUTE_PERSONAL_ROOTFOLDERS = 'entity.foldershare.rootfolders.personal';
  const ROUTE_PUBLIC_ROOTFOLDERS = 'entity.foldershare.rootfolders.public';
  const ROUTE_SHARED_ROOTFOLDERS = 'entity.foldershare.rootfolders.shared';
  const ROUTE_ALL_ROOTFOLDERS = 'entity.foldershare.rootfolders.all';

  /**
   * A list of well-known virtual folders containing root folders.
   *
   * @var array
   */
  const ROOTFOLDERS_GROUPS = [
    'all' => [
      'id'          => (-100),
      'route'       => self::ROUTE_ALL_ROOTFOLDERS,
    ],
    'personal' => [
      'id'          => (-1),
      'route'       => self::ROUTE_PERSONAL_ROOTFOLDERS,
    ],
    'public' => [
      'id'          => (-2),
      'route'       => self::ROUTE_PUBLIC_ROOTFOLDERS,
    ],
    'shared' => [
      'id'          => (-3),
      'route'       => self::ROUTE_SHARED_ROOTFOLDERS,
    ],
  ];

  /*--------------------------------------------------------------------
   *
   * URLs.
   *
   *--------------------------------------------------------------------*/

  /**
   * The home page at the San Diego Supercomputer Center (SDSC).
   *
   * @var string
   */
  const URL_SDSC_HOME = 'https://www.sdsc.edu/';

  /**
   * The home page at the University of California at San Diego (UCSD).
   *
   * @var string
   */
  const URL_UCSD_HOME = 'https://www.ucsd.edu/';

  /**
   * The home page of the US National Science Foundation (NSF).
   *
   * @var string
   */
  const URL_NSF_HOME = 'https://www.nsf.gov/';

  /**
   * The base URL to the Drupal module documentation pages.
   *
   * The documentation for module 'system', for instance, is at:
   * $url = Constants::URL_DRUPAL_CORE_MODULE_DOC . 'system';
   *
   * @var string
   */
  const URL_DRUPAL_CORE_MODULE_DOC = 'https://www.drupal.org/docs/8/core/modules/';

  /*--------------------------------------------------------------------
   *
   * Services.
   *
   * These constants define well-known module services. Their values
   * must match those in 'MODULE.services.yml'.
   *
   *--------------------------------------------------------------------*/

  /**
   * The service that keeps track of folder command plugins.
   *
   * This must match the route in 'MODULE.services.yml'.
   *
   * @var string
   */
  const SERVICE_FOLDERSHARECOMMANDS = 'foldershare.plugin.manager.foldersharecommand';

  /*--------------------------------------------------------------------
   *
   * Plugins.
   *
   * These constants define well-known plugins. Their values
   * must match those in plugin annotation for the plugin's 'id'.
   *
   *--------------------------------------------------------------------*/

  /**
   * The name of the module's search plugin for Drupal core Search.
   *
   * This name must match the ID for the search plugin.
   *
   * @var string
   */
  const SEARCH_PLUGIN = 'foldershare_search';

  /*--------------------------------------------------------------------
   *
   * Queues.
   *
   * These constants define well-known module work queues.
   *
   *--------------------------------------------------------------------*/

  /**
   * The queue for workers that update folder sizes and other state.
   *
   * The name should have the form "MODULE_name".
   *
   * @var string
   */
  const QUEUE_UPDATE = 'foldershare_update';

  /*--------------------------------------------------------------------
   *
   * Views and their displays.
   *
   * These constants define the names of module-installed views.
   *
   *--------------------------------------------------------------------*/

  /**
   * The view creating lists of root folders or folder contents.
   *
   * The name must match the machine name of the "FolderShare Lists"
   * view.
   *
   * @var string
   */
  const VIEW_LISTS = 'foldershare_lists';

  /**
   * The display name listing all content.
   *
   * Used for the 'All' list.
   *
   * @var string
   */
  const VIEW_LISTS_DISPLAY_ALL = 'all_top_folders';

  /**
   * The display name listing content owned by a user.
   *
   * Used for the 'Personal' list.
   *
   * @var string
   */
  const VIEW_LISTS_DISPLAY_USER_OWNED = 'user_owned_top_folders';

  /**
   * The display name listing content owned by or shared with a user.
   *
   * Unused.
   *
   * @var string
   */
  const VIEW_LISTS_DISPLAY_USER_OWNED_OR_SHARED = 'user_owned_or_shared_top_folders';

  /**
   * The display name listing content shared with the user that they don't own.
   *
   * Used for the 'Shared' list if sharing is enabled.
   *
   * @var string
   */
  const VIEW_LISTS_DISPLAY_USER_SHARED = 'user_shared_with_top_folders';

  /**
   * The display name listing content owned by anonymous.
   *
   * Used for the 'Public' list if sharing with anonymous is disabled.
   *
   * @var string
   */
  const VIEW_LISTS_DISPLAY_ANONYMOUS_OWNED = 'anonymous_owned_top_folders';

  /**
   * The display name listing content owned by or shared with anonymous.
   *
   * Used for the 'Public' list if sharing with anonymous is enabled.
   *
   * @var string
   */
  const VIEW_LISTS_DISPLAY_ANONYMOUS_OWNED_OR_SHARED = 'anonymous_owned_or_shared_top_folders';

  /*--------------------------------------------------------------------
   *
   * Tokens.
   *
   * These constants define well-known token groups created by the module.
   *
   *--------------------------------------------------------------------*/

  /**
   * The name of the token group for folder fields.
   *
   * The name should match FolderShare::ENTITY_TYPE_ID.
   *
   * @var string
   */
  const FOLDERSHARE_TOKENS = 'foldershare';

  /**
   * The name of the token group for module terminology.
   *
   * The name should have the form "MODULE-term".
   *
   * @var string
   */
  const MODULE_TERM_TOKENS = 'foldershare-term';

  /*--------------------------------------------------------------------
   *
   * Themes.
   *
   * These constants define well-known themes used by the module.
   *
   *--------------------------------------------------------------------*/

  /**
   * The folder page theme.
   *
   * This must match a theme name in the module's templates directory.
   * The theme file must be named:
   *  THEME_FOLDER . 'html.twig'.
   *
   * This name also must match the theme template function
   * template_preprocess_THEME_ROOT_LIST in MODULE.module.
   *
   * @var string
   */
  const THEME_FOLDER = 'foldershare';

  /**
   * The view (list) page theme.
   *
   * This must match a theme name in the module's templates directory.
   * The theme file must be named:
   *  THEME_VIEW . 'html.twig'.
   *
   * This name also must match the theme template function
   * template_preprocess_THEME_ROOT_LIST in MODULE.module.
   *
   * @var string
   */
  const THEME_VIEW = 'foldershare_view';

  /*--------------------------------------------------------------------
   *
   * Role-based permissions.
   *
   * These constants define well-known module permissions names. Their
   * values must match those in 'MODULE.permissions.yml'.
   *
   *--------------------------------------------------------------------*/

  /**
   * The module's administrative permission.
   *
   * Users may create, delete, edit, and view all content by
   * any user. Administrators may change content ownership.
   *
   * This must much the administer permission in 'MODULE.permissions.yml'.
   *
   * @var string
   */
  const ADMINISTER_PERMISSION = 'administer foldershare';

  /**
   * The module's share permission.
   *
   * Users may create and manage root folders and grant shared access
   * to the public or specific users.
   *
   * This must much the author permission in 'MODULE.permissions.yml'.
   *
   * @var string
   */
  const SHARE_PERMISSION = 'share foldershare';

  /**
   * The module's author permission.
   *
   * Users may create, delete, edit, and view their own content, and
   * content owned by others if they have been granted read or write
   * access.  Users cannot create root folders or alter shared access
   * settings.
   *
   * This must much the author permission in 'MODULE.permissions.yml'.
   *
   * @var string
   */
  const AUTHOR_PERMISSION = 'author foldershare';

  /**
   * The module's view permission.
   *
   * Users may view their own content, and content owned by others if
   * granted read access by folder-based access controls. Users cannot
   * create or modify content.
   *
   * This must much the view permission in 'MODULE.permissions.yml'.
   *
   * @var string
   */
  const VIEW_PERMISSION = 'view foldershare';

  /*--------------------------------------------------------------------
   *
   * File and directory name lengths.
   *
   * These constants define length limits for file and directory names.
   *
   *--------------------------------------------------------------------*/

  /**
   * The number of base-10 digits of file ID used for directory and file names.
   *
   * Directory and file names are generated automatically based upon the
   * file entity IDs of stored files.
   *
   * The number of digits is typically 4, which supports 10,000 files
   * or subdirectories in each subdirectory.  Keeping this number of
   * items small improves performance for operations that must open and
   * read directories. But keeping it large reduces the directory
   * depth, which can slightly improve file path handling.
   *
   * Operating system file systems may be limited in the number of separate
   * files and directories that they can support. For 32-bit file systems,
   * this limit is around 2 billion total.
   *
   * The following examples illustrate URIs when varying this number from
   * 1 to 10 for a File entity 123,456 with a module file directory DIR in
   * the public file system:
   *
   * - DIGITS_PER_SERVER_DIRECTORY_NAME = 1:
   *   - public://DIR/0/0/0/0/0/0/0/0/0/0/0/0/0/0/1/2/3/4/5/6
   *
   * - DIGITS_PER_SERVER_DIRECTORY_NAME = 2:
   *   - public://DIR/00/00/00/00/00/00/00/12/34/56
   *
   * - DIGITS_PER_SERVER_DIRECTORY_NAME = 3:
   *   - public://DIR/000/000/000/000/001/234/56
   *
   * - DIGITS_PER_SERVER_DIRECTORY_NAME = 4:
   *   - public://DIR/0000/0000/0000/0012/3456
   *
   * - DIGITS_PER_SERVER_DIRECTORY_NAME = 5:
   *   - public://DIR/00000/00000/00001/23456
   *
   * - DIGITS_PER_SERVER_DIRECTORY_NAME = 6:
   *   - public://DIR/000000/000000/001234/56
   *
   * - DIGITS_PER_SERVER_DIRECTORY_NAME = 7:
   *   - public://DIR/0000000/0000000/123456
   *
   * - DIGITS_PER_SERVER_DIRECTORY_NAME = 8:
   *   - public://DIR/00000000/00000012/3456
   *
   * - DIGITS_PER_SERVER_DIRECTORY_NAME = 9:
   *   - public://DIR/000000000/000001234/56
   *
   * - DIGITS_PER_SERVER_DIRECTORY_NAME = 10:
   *   - public://DIR/0000000000/0000123456
   *
   * @var int
   */
  const DIGITS_PER_SERVER_DIRECTORY_NAME = 4;

  /**
   * The maximum number of UTF-8 characters in a file or folder name.
   *
   * The File module imposes an internal limit of 255 characters for
   * file names. Folder uses this same limit to keep some of this
   * class's name handling generically usable for either files or
   * folders.
   *
   * Note that this limit is in *characters*, not bytes. File and
   * Folder support UTF-8 names which include multi-byte characters.
   * So a 255 character name may be use more than 255 bytes.
   *
   * All name handling must be multi-byte character safe. This often
   * means the use of the 'mb_' functions in PHP, such as 'mb_strlen()'.
   *
   * @var int
   */
  const MAX_NAME_LENGTH = 255;

  /**
   * The name of the top-level folder into which the module places files.
   *
   * The folder is within the public or private file system, depending
   * upon which file system is selected by the site administrator using
   * the module's settings.
   *
   * @var string
   */
  const FILE_DIRECTORY = 'foldersharefiles';

  /*--------------------------------------------------------------------
   *
   * Feature enables.
   *
   *--------------------------------------------------------------------*/

  /**
   * Indicates whether to include the folder table user interface.
   *
   * This UI may be enabled on pages containing individual files, and on
   * pages showing a table of files and folders within a folder or root
   * folder. It provides:
   * - A command menu button.
   * - A command menu of commands appropriate for the page's entity kind.
   * - Table row selection.
   * - Drag-and-drop on table rows for copy and move.
   * - Drag-and-drop of files into a table for upload.
   * - A file dialog for selecting files to upload.
   *
   * The UI's selections depend upon a hidden table column containing
   * a checkbox per row and entity attributes on the checkbox. This hidden
   * column is added by the "base UI" view field plugin added to the table.
   * That plugin adds its own default UI that also shows a menu of commands,
   * plus a submit button. The default UI does not require Javascript. When
   * Javascript is present, though, the default UI is hidden in favor of
   * the main UI.
   *
   * When this flag is TRUE, the main UI widgets are included and the default
   * UI is hidden. When FALSE, the main UI widgets and behaviors are not
   * included and only the default UI is present.
   *
   * This flag is normally TRUE, but it can be set to FALSE to help
   * debug the user interface.
   *
   * @var bool
   */
  const ENABLE_UI_FOLDER_TABLE_MENU = TRUE;

  /**
   * Indicates whether to include the root folder groups table user interface.
   *
   * This UI may be enabled on the root folder groups table. It provides:
   * - A command menu button.
   * - A command menu of commands appropriate for this table (e.g. "Open").
   * - Table row selection.
   *
   * The UI's selections depend upon choosing rows that include a URL to
   * a page listing the root folder group's contents.
   *
   * This flag is normally TRUE, but it can be set to FALSE to help
   * debug the user interface.
   *
   * @var bool
   */
  const ENABLE_UI_ROOT_FOLDER_GROUPS_TABLE_MENU = TRUE;

  /**
   * Indicates whether to include the ancestor menu user interface.
   *
   * This UI includes a menu of ancestor folders, starting with the current
   * item and continuing up to its root folder, the list of root folders
   * in the same group (e.g. "personal"), and the list of groups.
   *
   * When this flag is TRUE, these UI widgets are included. When FALSE,
   * they are not.
   *
   * This flag is normally TRUE, but it can be set to FALSE to help
   * debug the user interface.
   *
   * @var bool
   */
  const ENABLE_UI_ANCESTOR_MENU = TRUE;

  /**
   * Indicates whether to include the search box user interface.
   *
   * The search UI includes a search box and a search submit button.
   * Both widgets are created when the form is initialized. Both are visible.
   * If the browser supports Javascript, the submit button is hidden and
   * a behavior on the search box triggers a search on a carriage return.
   *
   * When this flag is TRUE, these UI widgets are included. When FALSE,
   * they are not.
   *
   * The Drupal core Search module must be enabled, FolderShare's search
   * plugin must be available, and the current user must have the "Use search"
   * permission. If any of these are not true, the search UI widgets are
   * not included.
   *
   * This flag is normally TRUE, but it can be set to FALSE to help
   * debug the user interface.
   *
   * @var bool
   */
  const ENABLE_UI_SEARCH_BOX = TRUE;

}
