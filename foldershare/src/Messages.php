<?php

namespace Drupal\foldershare;

/**
 * Defines confirmation and error messages used throughout the module.
 *
 * Messages are user-readable text in English that are expected to be
 * processed through Drupal's t() function with appropriate arguments.
 * The arguments needed vary with the message. Common arguments are:
 *
 * - "@name" = the name of a file or folder.
 *
 * - "@path" = the path of a file or folder.
 *
 * - "@destination" = the name of a destination folder.
 *
 * - "@operation" = the operation in progress (e.g. "move", "copy").
 *
 * - "@method" = the name of the PHP method issuing the error.
 *
 * - "@id" = an entity ID.
 *
 * Error messages are expected to have a brief 1st line that summarizes
 * the error. The remainder of the message, if any, may include multiple
 * paragraphs of unwrapped text that provides more detail.
 */
class Messages {

  /*---------------------------------------------------------------------
   *
   * Operation confirmations.
   *
   * These messages are used by various specific operations to report
   * to the user the completion of an operation. These messages may be
   * shown on a web page, in Drupal's log, or on the command-line,
   * depending upon the operation.
   *
   *---------------------------------------------------------------------*/

  const ARCHIVE_DONE_ONE_ITEM = <<<'EOS'
The @kind "@name" has been compressed and saved into the new "@archive" file.
EOS;

  const ARCHIVE_DONE_MULTIPLE_ITEMS = <<<'EOS'
@number @kinds have been compressed and saved into the new "@archive" file.
EOS;

  const CHOWN_DONE_ONE_ITEM = <<<'EOS'
The @kind "@name" has been changed and is now owned by "@account".
EOS;

  const CHOWN_DONE_MULTIPLE_ITEMS = <<<'EOS'
@number @kinds have been changed and are now owned by "@account".
EOS;

  const COPY_DONE_ONE_ITEM = <<<'EOS'
The @kind "@name" has been copied.
EOS;

  const COPY_DONE_MULTIPLE_ITEMS = <<<'EOS'
@number @kinds have been copied.
EOS;

  const DELETE_DONE_ONE_ITEM = <<<'EOS'
The @kind "@name" has been deleted.
EOS;

  const DELETE_DONE_MULTIPLE_ITEMS = <<<'EOS'
@number @kinds have been deleted.
EOS;

  const DUPLICATE_DONE_ONE_ITEM = <<<'EOS'
The @kind "@name" has been duplicated.
EOS;

  const DUPLICATE_DONE_MULTIPLE_ITEMS = <<<'EOS'
@number @kinds have been duplicated.
EOS;

  const MOVE_DONE_ONE_ITEM = <<<'EOS'
The @kind "@name" has been moved.
EOS;

  const MOVE_DONE_MULTIPLE_ITEMS = <<<'EOS'
@number @kinds have been moved.
EOS;

  const NEWFOLDER_DONE = <<<'EOS'
A @kind named "@name" has been created.
EOS;

  const RENAME_DONE = <<<'EOS'
The @kind has been renamed to "@name".
EOS;

  const SHARE_DONE = <<<'EOS'
Share choices have been updated for the @kind "@name".
EOS;

  const UNARCHIVE_DONE = <<<'EOS'
The @kind "@name" has been uncompressed.
EOS;

  const UPDATE_DONE_ONE_ITEM = <<<'EOS'
The @kind "@name" has been updated.
EOS;

  const UPLOAD_DONE_ONE_ITEM = <<<'EOS'
The @kind "@name" has been uploaded.
EOS;

  const UPLOAD_DONE_MULTIPLE_ITEMS = <<<'EOS'
@number @kinds have been uploaded.
EOS;

  /*---------------------------------------------------------------------
   *
   * Operation prompt.
   *
   * These messages are used by various specific operations to prompt
   * or explain forms or form fields.
   *
   * It is not practical to put every bit of user-visible text here, so
   * these are only the most important or repeatedly used items.
   *
   *---------------------------------------------------------------------*/

  const CHOWN_DESCRIPTION_ONE_FOLDER = <<<'EOS'
Change the owner of this @kind, including all of its contents.
EOS;

  const CHOWN_DESCRIPTION_ONE_FILE = <<<'EOS'
Change the owner of this @kind.
EOS;

  const CHOWN_DESCRIPTION_MULTIPLE_FILES = <<<'EOS'
Change the owner of these @kinds.
EOS;

  const CHOWN_DESCRIPTION_MULTIPLE_ITEMS = <<<'EOS'
Change the owner of these @kinds, including all of their contents.
EOS;

  const COPY_DESCRIPTION_ONE_FOLDER = <<<'EOS'
Copy this @kind, including all of its contents.
EOS;

  const COPY_DESCRIPTION_ONE_FILE = <<<'EOS'
Copy this @kind.
EOS;

  const COPY_DESCRIPTION_MULTIPLE_FILES = <<<'EOS'
Copy these @kinds.
EOS;

  const COPY_DESCRIPTION_MULTIPLE_ITEMS = <<<'EOS'
Copy these @kinds, including all of their contents.
EOS;

  const DELETE_PROMPT_ONE_FOLDER = <<<'EOS'
Delete this @kind, including all of its contents? This cannot be undone.
EOS;

  const DELETE_PROMPT_ONE_FILE = <<<'EOS'
Delete this @kind? This cannot be undone.
EOS;

  const DELETE_PROMPT_MULTIPLE_FILES = <<<'EOS'
Delete these @kinds? This cannot be undone.
EOS;

  const DELETE_PROMPT_MULTIPLE_ITEMS = <<<'EOS'
Delete these @kinds, including all of their contents? This cannot be undone.
EOS;

  const MOVE_DESCRIPTION_ONE_FOLDER = <<<'EOS'
Move this @kind, including all of its contents.
EOS;

  const MOVE_DESCRIPTION_ONE_FILE = <<<'EOS'
Move this @kind.
EOS;

  const MOVE_DESCRIPTION_MULTIPLE_FILES = <<<'EOS'
Move these @kinds.
EOS;

  const MOVE_DESCRIPTION_MULTIPLE_ITEMS = <<<'EOS'
Move these @kinds, including all of their contents.
EOS;

  const RENAME_DESCRIPTION_WITHIN_FOLDER = <<<'EOS'
Names must be unique within this folder. They may use any mix of characters, but avoid punctuation marks like ":", "/", and "\".
EOS;

  const RENAME_DESCRIPTION_WITHIN_ROOT = <<<'EOS'
Names must be unique among all of your top-level folders. They may use any mix of characters, but avoid punctuation marks like ":", "/", and "\".
EOS;

  const SHARE_DESCRIPTION_THIS = <<<'EOS'
Adjust share choices for all files and folders in this folder tree.
EOS;

  const SHARE_DESCRIPTION_THIS_ROOT = <<<'EOS'
Adjust share choices for all files and folders in the folder tree that contains this @kind "@name".
EOS;

  const SHARE_USER_IS_ADMIN = <<<'EOS'
This user has content administration authority, so they always have access.
EOS;

  const SHARE_USER_IS_OWNER = <<<'EOS'
This user is the owner of the folder tree, so they always have access.
EOS;

  const UPLOAD_DESCRIPTION = <<<'EOS'
Select one or more @kinds to upload.
EOS;

  const UPLOAD_DND_NOT_SUPPORTED = <<<'EOS'
<p><strong>Drag-and-drop file upload is not supported.</strong></p>
<p>This feature is not supported by this web browser.</p>
EOS;

  const UPLOAD_DND_INVALID_SINGULAR = <<<'EOS'
<p><strong>Drag-and-drop item cannot be uploaded.</strong></p>
<p>You may not have access to the item, or it may be a folder. Folder upload is not supported.</p>
EOS;

  const UPLOAD_DND_INVALID_PLURAL = <<<'EOS'
<p><strong>Drag-and-drop items cannot be uploaded.</strong></p>
<p>You may not have access to these items, or one of them may be a folder. Folder upload is not supported.</p>
EOS;

  /*---------------------------------------------------------------------
   *
   * Operation errors.
   *
   * These errors are used by various specific operations to report
   * problems.
   *
   *---------------------------------------------------------------------*/

  const SHARE_DISABLED = <<<'EOS'
Sharing features have been disabled at this site.
EOS;

  const SHARE_ANONYMOUS_NO_VIEW = <<<'EOS'
@account users do not have view permission enabled for this module. They cannot be granted access.
EOS;

  const SHARE_ANONYMOUS_NO_AUTHOR = <<<'EOS'
@account users do not have author permission enabled for this module. They cannot be granted access.
EOS;

  const SHARE_USER_NO_VIEW = <<<'EOS'
This user does not have view permission enabled for this module. They cannot be granted access.
EOS;

  const SHARE_USER_NO_AUTHOR = <<<'EOS'
This user does not have author permission enabled for this module. They cannot be granted access.
EOS;

  const SHARE_USER_BLOCKED = <<<'EOS'
The site administrator has blocked this user's account. They cannot be granted access.
EOS;

  const COPY_TO_SELF = <<<'EOS'
Items cannot be copied into themselves.
EOS;

  const COPY_TO_SUBFOLDER_SELF = <<<'EOS'
Items cannot be copied into their own subfolders.
EOS;

  const MOVE_TO_SELF = <<<'EOS'
Items cannot be moved into themselves.
EOS;

  const MOVE_TO_SUBFOLDER_SELF = <<<'EOS'
Items cannot be moved into their own subfolders.
EOS;

  /*---------------------------------------------------------------------
   *
   * Name errors.
   *
   * These errors occur when a file or folder name is empty, invalid, or
   * it collides with another item with the same name.
   *
   * These are all user errors.
   *
   *---------------------------------------------------------------------*/

  const NAME_EMPTY = <<<'EOS'
The name is empty.
Please use a name with at least one character.
EOS;

  const NAME_INVALID = <<<'EOS'
The name "@name" can't be used.
Try using a name with fewer characters and avoid punctuation marks like ":", "/", and "\".
EOS;

  const NAME_IN_USE = <<<'EOS'
The name "@name" is already taken.
Please choose a different name.
EOS;

  const NAME_IN_DESTINATION_IN_USE = <<<'EOS'
The name "@name" is already in use in the "@destination" folder.
Please rename the item first or @operation it to a different folder.
EOS;

  /*---------------------------------------------------------------------
   *
   * Path errors.
   *
   * These errors occur when a file path cannot be parsed.
   *
   * These are all user errors.
   *
   *---------------------------------------------------------------------*/

  const PATH_EMPTY = <<<'EOS'
The folder path is empty.
Please enter a path with folder names separated by the "/" character.
EOS;

  const PATH_INVALID_SCHEME = <<<'EOS'
The folder path uses an invalid scheme "@name".
Paths optionally may start with "public://", "private://", or "shared://" to indicate public, private, or shared items.
EOS;

  const PATH_INVALID = <<<'EOS'
"@path" could not be found.
Please check that the path is correct.
EOS;

  const PATH_MISSING_FOLDER_NAME = <<<'EOS'
The folder path is missing a top-level folder name.
The path should start with "/" and a top-level folder name. Paths optionally may start with "public://", "private://", or "shared://" to indicate public, private, or shared items. The "//" may be followed by a ":" and a user account name or ID to indicate a specific user's item.
EOS;

  const PATH_AMBIGUOUS = <<<'EOS'
The folder path "@path" is ambiguous.
To select a specific user's public or shared item, try starting the folder path with "public://" or "shared://", followed by a ":" and a user account name or ID, and then the path for the item you want.
EOS;

  const PATH_INVALID_ROOT_PATH_FOR_FILE = <<<'EOS'
The path "@path" cannot be used for files.
Files must be placed within subfolders.
EOS;

  /*---------------------------------------------------------------------
   *
   * File type errors.
   *
   * These errors occur when a name has a file name extension that is
   * restricted on this site.
   *
   * These are all user errors because of site administrator settings.
   *
   *---------------------------------------------------------------------*/

  const FILETYPE_NOT_ALLOWED = <<<'EOS'
The file type used in "@name" is not allowed.
Please see the site's documentation for a list of approved file types.
EOS;

  const FILETYPE_IN_ARCHIVE_NOT_ALLOWED = <<<'EOS'
The file type used by "@name" in the archive is not allowed.
The archive cannot be uncompressed. Please see the site's documentation for a list of approved file types.
EOS;

  const FILETYPE_ON_UPLOAD_NOT_ALLOWED = <<<'EOS'
The file type used by the uploaded "@name" is not allowed.
The file cannot be uploaded. Please see the site's documentation for a list of approved file types.
EOS;

  /*---------------------------------------------------------------------
   *
   * User account errors.
   *
   * These errors occur when a user account name or ID is not recognized.
   *
   * These are all user errors.
   *
   *---------------------------------------------------------------------*/

  const USER_INVALID_ID = <<<'EOS'
The user ID "@id" is not recognized.
Please check that the ID is correct for an existing site account.
EOS;

  const USER_INVALID_ACCOUNT = <<<'EOS'
The user account "@name" is not recognized.
Please check that the name is correct for an existing site account.
EOS;

  /*---------------------------------------------------------------------
   *
   * Locks.
   *
   * These errors occur when a process lock could not be acquired because
   * some other process has the item locked already.
   *
   * These are all artifacts of more than one user trying to use the same
   * items at the same time.
   *
   *---------------------------------------------------------------------*/

  const LOCKED_ITEM = <<<'EOS'
The item "@name" is in use.
The @operation cannot be completed at this time. Please try again later.
EOS;

  const LOCKED_ITEMS = <<<'EOS'
Multiple needed files and folders are in use.
The @operation cannot be completed at this time. Please try again later.
EOS;

  const LOCKED_FOLDER_TREE = <<<'EOS'
The folder "@name" and its contents are in use.
The @operation cannot be completed at this time. Please try again later.
EOS;

  const LOCKED_ROOT_FOLDERS = <<<'EOS'
The system is busy updating folders.
The @operation cannot be completed at this time. Please try again later.
EOS;

  /*---------------------------------------------------------------------
   *
   * Database corruption errors.
   *
   * These errors should never occur and indicate that corrupted data
   * was found that could not be understood or corrected.
   *
   *---------------------------------------------------------------------*/

  const CORRUPTED_DATABASE = <<<'EOS'
Corrupted database. An unexpected error has occurred in "@method".
The site's database appears to have become corrupted. This may be have been caused by problems with the site's hardware, by other software, or by bugs in this module. Please contact the site administrator.
EOS;

  /*---------------------------------------------------------------------
   *
   * Generic errors.
   *
   * These errors are generic.
   *
   *---------------------------------------------------------------------*/

  const PROBLEM_WITH_PAGE = <<<'EOS'
The web site has encountered a problem with this page. Please report this to the site administrator.
EOS;

  /*---------------------------------------------------------------------
   *
   * Programming errors with the API.
   *
   * These errors occur because of improper use of the module's classes
   * and methods by code outside of this module.
   *
   * These are always programmer errors.
   *
   *---------------------------------------------------------------------*/

  const API_ARRAY_EMPTY = <<<'EOS'
Programming error. @method was called with an empty array.
The method requires at least one item in the array.
EOS;

  const API_ITEM_NOT_FILE = <<<'EOS'
Programming error. @method was called with an entity that is not a file.
The method may be used only with FolderShare entities that indicate a file, image, or media item.
EOS;

  const API_ITEM_NOT_FOLDER = <<<'EOS'
Programming error. @method was called with an entity that is not a folder.
The method may be used only when FolderShare entities that indicate a folder or root folder item.
EOS;

  const API_ITEM_NOT_IN_FOLDER = <<<'EOS'
Programming error. @method was called with an entity that is not in the folder.
The method may be used only with FolderShare entities that are children of the folder.
EOS;

  const API_INVALID_ID = <<<'EOS'
Programming error. @method was called with an invalid entity ID "@id".
The method requires a valid FolderShare entity ID.
EOS;

  const API_ITEM_ALREADY_IN_FOLDER = <<<'EOS'
Programming error. @method was called to @operation an item already in a folder.
The item is already where it should be.
EOS;

  const API_INVALID_COPY_TO_ROOT = <<<'EOS'
Programming error. @method was called to copy a non-folder to the root folder list.
Only folders may be copied to the root folder list.
EOS;

  const API_INVALID_MOVE_TO_ROOT = <<<'EOS'
Programming error. @method was called to move a non-folder to the root folder list.
Only folders may be moved to the root folder list.
EOS;

  const API_INVALID_CIRCULAR = <<<'EOS'
Programming error. @method was called to @operation "@path" into itself.
This would create an infinite recursion.
EOS;

  const API_ARCHIVE_UNSUPPORTED = <<<'EOS'
Programming error. @method was called to create an archive site doesn't support.
The ZIP file type required for new archives is not an allowed file type for the site. Archive creation is therefore not supported.
EOS;

  const API_FORM_MISSING_ENTITY_PARAMETER = <<<'EOS'
Programming error. The @form form was improperly invoked.
The FolderShare entity parameter is required but missing.
EOS;

  const API_COMMAND_UNSUPPORTED_CONTEXT = <<<'EOS'
Programming error. The "@command" command was invoked with an unsupported context.
The context should have been validated before invoking the command.
EOS;

  const API_COMMAND_UNSUPPORTED_KIND = <<<'EOS'
Programming error. The "@command" command does not support @kinds.
The selection's file types should have been validated before invoking the command.
EOS;

  const API_COMMAND_NOT_ALL_CHILDREN = <<<'EOS'
Programming error. The "@command" command requires that all selected items be children of the same parent.
The item "@id" is not a child of the parent. This should have been validated before invoking the command.
EOS;

  const API_COMMAND_INVALID_PARENT_ID = <<<'EOS'
Programming error. The "@command" command was invoked with an invalid parent entity ID "@id".
The command requires a valid FolderShare entity ID.
EOS;

  const API_COMMAND_INVALID_DESTINATION_ID = <<<'EOS'
Programming error. The "@command" command was invoked with an invalid destination entity ID "@id".
The command requires a valid FolderShare entity ID.
EOS;

  const API_COMMAND_NEED_SINGLE_SELECTION = <<<'EOS'
Programming error. The "@command" command requires a selection with just one item.
The selection's size should have been validated before invoking the command.
EOS;

  const API_COMMAND_NEED_MULTIPLE_SELECTION = <<<'EOS'
Programming error. The "@command" command requires a selection with multiple items.
The selection's size should have been validated before invoking the command.
EOS;


  /*---------------------------------------------------------------------
   *
   * System errors with the OS.
   *
   * These errors occur due to problems with the site's operating system,
   * such as missing directories, incorrect permissions, no more storage
   * space, etc. They are all likely issues for a site administrator to
   * address.
   *
   *---------------------------------------------------------------------*/

  const OS_CANNOT_CREATE_DIRECTORY = <<<'EOS'
System error. A directory at "@path" could not be created.
There may be a problem with directories or permissions. Please report this to the site administrator.
EOS;

  const OS_CANNOT_CREATE_FILE = <<<'EOS'
System error. A file at "@path" could not be created.
There may be a problem with directories or permissions. Please report this to the site administrator.
EOS;

  const OS_CANNOT_MOVE_FILE = <<<'EOS'
System error. A file at "@name" could not be moved to "@destination".
There may be a problem with directories or permissions. Please report this to the site administrator.
EOS;

  const OS_CANNOT_COPY_FILE = <<<'EOS'
System error. A file at "@name" could not be copied to "@destination".
There may be a problem with directories or permissions. Please report this to the site administrator.
EOS;

  const OS_CANNOT_CREATE_TEMP_FILE = <<<'EOS'
System error. A temporary file at "@path" could not be created.
There may be a problem with directories or permissions. Please report this to the site administrator.
EOS;

  const OS_CANNOT_READ_FILE = <<<'EOS'
System error. A file at "@path" could not be read.
There may be a problem with permissions. Please report this to the site administrator.
EOS;

  const OS_CANNOT_WRITE_FILE = <<<'EOS'
System error. A file at "@path" could not be written.
There may be a problem with permissions. Please report this to the site administrator.
EOS;

  const OS_CANNOT_ADD_TO_ARCHIVE = <<<'EOS'
System error. Cannot add "@path" to archive "@archive".
There may be a problem with the file system (such as out of storage space), with file permissions, or with the ZIP archive library. Please report this to the site administrator.
EOS;

  const OS_CANNOT_READ_INPUT_STREAM = <<<'EOS'
System error. An input stream cannot be opened or read.
EOS;

  /*---------------------------------------------------------------------
   *
   * Missing view errors.
   *
   * These errors occur if an expected view or display is missing.
   *
   * Realistically, this can only happen because a site administrator
   * deleted them.
   *
   *---------------------------------------------------------------------*/

  const VIEW_MISSING = <<<'EOS'
Misconfigured web site. The required "@viewName" view is missing.
Please check the views module configuration and, if needed, restore the view using the settings page for the @moduleName module.
EOS;

  const VIEW_DISPLAY_MISSING = <<<'EOS'
Misconfigured web site. The required "@displayName" display for the "@viewName" view is missing.
Please check the views module configuration and, if needed, restore the view using the settings page for the @moduleName module.
EOS;

  /*---------------------------------------------------------------------
   *
   * Missing search plugin errors.
   *
   * These errors occur if an expected search plugin is missing.
   *
   * Realistically, this can only happen because a developer broke the
   * module or third-party code unregistered the module's search plugin.
   *
   *---------------------------------------------------------------------*/

  const SEARCH_PLUGIN_MISSING = <<<'EOS'
Programming error. The module's "@pluginName" search plugin is missing.
Something in the "@moduleName" module's software is broken. Please check that the search plugin exists and that it has the correct ID (name).
EOS;

  /*---------------------------------------------------------------------
   *
   * Protocol errors.
   *
   * These errors report problems with HTTP requests, including missing
   * or malformed values. While some of these are for commands, the bulk
   * of them are related to web services.
   *
   * These are often programmer errors.
   *
   *---------------------------------------------------------------------*/

  const HTTP_COMMAND_MALFORMED = <<<'EOS'
Programming error. The command is malformed.
The @operation command could not be completed because it is missing one or more required parameters. Valid use of the command should have been checked before issuing the command.
EOS;

  const HTTP_UPLOAD_FOLDER_ACCESS_DENIED = <<<'EOS'
You are not authorized to upload files into "@path".
EOS;

  const HTTP_COMMAND_ACCESS_DENIED = <<<'EOS'
You do not have sufficient permissions.
The @operation operation could not be completed.
EOS;

  const HTTP_SOURCE_PATH_EMPTY = <<<'EOS'
Programming error. The path to the source item is empty.
The @operation requires a source path, but the given path is empty.
EOS;

  const HTTP_DESTINATION_PATH_EMPTY = <<<'EOS'
Programming error. The path to the destination item is empty.
The @operation requires a destination path, but the given path is empty.
EOS;

  const HTTP_REST_INVALID_OPERATION = <<<'EOS'
Client programming error. The requested "@operation" web service is not recognized.
The web services client may be out of date.
EOS;

  const HTTP_REST_INVALID_RETURN_FORMAT = <<<'EOS'
Client programming error. The requested "@name" web service return format is not recognized.
The web services client may be out of date.
EOS;

  const HTTP_REST_INVALID_SCHEME = <<<'EOS'
Client programming error. The folder path scheme "@scheme" is not recognized.
Paths and schemes for the service should have been checked before issuing the request.
EOS;

  const HTTP_REST_INVALID_ID = <<<'EOS'
The requested entity ID "@id" for web services could not be found.
EOS;

  const HTTP_REST_SOURCE_PATH_EMPTY = <<<'EOS'
Client programming error. The path to the source item is empty.
The web service requires a source path, but the given path is empty. Valid use of the service should have been checked before issuing the request.
EOS;

  const HTTP_REST_DESTINATION_PATH_EMPTY = <<<'EOS'
Client programming error. The path to the destination item is empty.
The web service requires a destination path, but the given path is empty. Valid use of the service should have been checked before issuing the request.
EOS;

  const HTTP_REST_POST_ENTITY_MISSING = <<<'EOS'
Client programming error. The entity required for a post operation is missing.
The web service requires a posted entity, but none was provided. Valid use of the service should have been checked before issuing the request.
EOS;

  const HTTP_REST_POST_ENTITY_WRONG_TYPE = <<<'EOS'
Client programming error. The entity required for a post operation has the wrong entity type.
The web service requires a posted FolderShare entity. Valid use of the service should have been checked before issuing the request.
EOS;

  const HTTP_REST_POST_ENTITY_EMPTY = <<<'EOS'
Client programming error. The entity required for a post operation is empty.
Valid use of the service should have been checked before issuing the request.
EOS;

  const HTTP_REST_POST_ENTITY_INVALID_ID = <<<'EOS'
Client programming error. The entity required for a post operation has an invalid entity ID "@id".
Valid use of the service should have been checked before issuing the request.
EOS;

  const HTTP_REST_POST_ENTITY_INVALID_PARENT_ID = <<<'EOS'
Client programming error. The entity required for a post operation has an invalid parent entity ID "@id".
Valid use of the service should have been checked before issuing the request.
EOS;

  const HTTP_REST_POST_ENTITY_INVALID_DESTINATION_ID = <<<'EOS'
Client programming error. The entity required for a post operation has an invalid destination entity ID "@id".
Valid use of the service should have been checked before issuing the request.
EOS;

  const HTTP_REST_POST_ENTITY_NAME_EMPTY = <<<'EOS'
Client programming error. The entity required for a post operation has an empty name.
The web service requires a posted entity with a valid name. Valid use of the service should have been checked before issuing the request.
EOS;

  const HTTP_REST_POST_DESTINATION_NAME_EMPTY = <<<'EOS'
The path to the destination must end with the new name for the item.
EOS;

  const HTTP_REST_ROOT_HAS_NO_PARENT = <<<'EOS'
A top-level folder does not have a parent folder.
EOS;

  const HTTP_REST_ROOT_HAS_NO_ROOT = <<<'EOS'
A top-level folder does not have an ancestor top-level folder.
EOS;

  const HTTP_REST_ITEM_NOT_DOWNLOADABLE = <<<'EOS'
The item "@name" does not support downloading.
EOS;

  const HTTP_REST_DELETE_FILE_INVALID = <<<'EOS'
Client programming error. The "@operation" web service only deletes files, but a folder was provided.
Valid use of the service should have been checked before issuing the request.
EOS;

  const HTTP_REST_DELETE_FOLDER_INVALID = <<<'EOS'
Client programming error. The "@operation" web service only deletes folders, but a file was provided.
Valid use of the service should have been checked before issuing the request.
EOS;

  const HTTP_REST_DELETE_FOLDER_NOT_EMPTY = <<<'EOS'
The "@name" folder cannot be deleted. It is not empty.
EOS;

  const HTTP_REST_COPY_TO_ANOTHER_ROOT_INVALID = <<<'EOS'
Access denied. The item "@name" cannot be copied into another user's top-level folder list.
EOS;

  const HTTP_REST_COPY_TO_PUBLIC_ROOT_INVALID = <<<'EOS'
Access denied. The item "@name" cannot be copied into the virtual public top-level folder list.
Set the item to be shared with the public instead.
EOS;

  const HTTP_REST_COPY_TO_SHARED_ROOT_INVALID = <<<'EOS'
Access denied. The item "@name" cannot be copied into the virtual shared top-level folder list.
Set the item to be shared with others instead.
EOS;

  const HTTP_REST_COPY_FILE_TO_ROOT_INVALID = <<<'EOS'
The file "@name" cannot be copied into top-level folder list.
Copy the file into a folder instead.
EOS;

  const HTTP_REST_COPY_FOLDER_TO_FILE_INVALID = <<<'EOS'
The folder "@name" cannot be copied into file.
EOS;

  const HTTP_REST_COPY_FOLDER_TO_FOLDER_INVALID = <<<'EOS'
The folder "@name" cannot be copied to overwrite an existing folder with the same name.
EOS;

  const HTTP_REST_COPY_ITEM_TO_ITEM_INVALID = <<<'EOS'
The item "@name" cannot be copied to overwrite an existing item with the same name.
EOS;

  const HTTP_REST_MOVE_TO_ANOTHER_ROOT_INVALID = <<<'EOS'
Access denied. The item "@name" cannot be moved into another user's top-level folder list.
EOS;

  const HTTP_REST_MOVE_TO_PUBLIC_ROOT_INVALID = <<<'EOS'
Access denied. The item "@name" cannot be moved into the virtual public top-level folder list.
Set the item to be shared with the public instead.
EOS;

  const HTTP_REST_MOVE_TO_SHARED_ROOT_INVALID = <<<'EOS'
Access denied. The item "@name" cannot be moved into the virtual shared top-level folder list.
Set the item to be shared with others instead.
EOS;

  const HTTP_REST_MOVE_FILE_TO_ROOT_INVALID = <<<'EOS'
The file "@name" cannot be moved into top-level folder list.
Copy the file into a folder instead.
EOS;

  const HTTP_REST_MOVE_FOLDER_TO_FILE_INVALID = <<<'EOS'
The folder "@name" cannot be moved into file.
EOS;

  const HTTP_REST_MOVE_FOLDER_TO_FOLDER_INVALID = <<<'EOS'
The folder "@name" cannot be moved to overwrite an existing folder with the same name.
EOS;

  const HTTP_REST_MOVE_ITEM_TO_ITEM_INVALID = <<<'EOS'
The item "@name" cannot be moved to overwrite an existing item with the same name.
EOS;

}
