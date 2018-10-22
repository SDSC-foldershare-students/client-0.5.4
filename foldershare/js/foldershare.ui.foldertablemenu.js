/**
 * @file
 * Implements the FolderShare folder table menu user interface.
 *
 * The folder table menu UI presents a menu button and pull-down menu that
 * lists commands that operate upon folders and files of various types.
 * Commands are plugins to the FolderShare module and implement operations
 * such as creating a new folder, uploading files, deleting, copying, moving,
 * and downloading. The server attaches command descriptions used here to
 * build commands and pre-validate them before submitting them to the server.
 *
 * Most commands use a selection, so this script supports selecting rows in
 * a file/folder table. Rows can be selected individually or in groups.
 * Double-clicking a row opens the row's file or folder by advancing to its
 * page. Right-clicking on a row shows a context menu that shows a subset of
 * the main menu. Rows can be dragged and dropped onto subfolders to move
 * and copy, and files can be dragged from the host OS into the folder to
 * initiate a file upload.
 *
 * This script requires HTML elements added by a table view that uses a name
 * field formatter that attaches attributes to name field anchors. This script
 * uses those attributes to guide prevalidation of menu items as appropriate
 * for selected rows.
 *
 * This script also requires an HTML form that provides:
 * - A field to hold the current command choice.
 * - A set of fields holding command operands, including a parent ID,
 *   destination ID, and selection.
 * - A file field holding selected files for an upload.
 * - Drupal settings that list all known commands, and sundry other attributes.
 *
 * @ingroup foldershare
 * @see \Drupal\foldershare\Plugin\Field\FieldFormatter\FolderShareName
 * @see \Drupal\foldershare\Form\UIFolderTableMenu
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  // Check pre-requisits.
  //
  // The utility library must have been loaded before this script.
  if ('foldershare' in Drupal === false ||
      'utility' in Drupal.foldershare === false) {
    console.log(
      "%cFolderShare: Javascript files included in wrong order%c\n" +
      "%cfoldershare.ui.foldertablemenu.js requires that foldershare.ui.utility.js be included first.",
      'font-weight: bold',
      'font-weight: normal',
      'padding-left: 2em',
      'padding-left: 0');
    window.stop()
  }

  Drupal.foldershare.UIFolderTableMenu = {

    /*--------------------------------------------------------------------
     *
     * Constants.
     *
     *--------------------------------------------------------------------*/

    /**
     * The name of the module's standard file upload command.
     */
    uploadCommand: 'foldersharecommand_upload_files',

    /**
     * The name of the module's standard entity copy command.
     */
    copyCommand: 'foldersharecommand_copy',

    /**
     * The name of the module's standard entity move command.
     */
    moveCommand: 'foldersharecommand_move',

    /**
     * The table attribute created to track the current drag operand.
     *
     * Expected values are:
     * - "none" when no drag operation is in progress.
     * - "rows" when rows in the current table are being dragged.
     * - "files" when files are being dragged in from off browser.
     */
    tableDragOperand: 'foldershare-drag-operand',

    /**
     * The table attribute created to track the current drag effects allowed.
     *
     * Expected values are any of those allowed for the "effectAllowed"
     * field of a drag event's data transfer. Values used here are:
     * - "none" when a drop is not allowed at this point.
     * - "copy" when only a copy is allowed at this point.
     * - "move" when only a copy is allowed at this point.
     * - "copyMove" when either a copy or a move is allowed at this point.
     */
    tableDragEffectAllowed: 'foldershare-drag-effect-allowed',

    /**
     * The table attribute created to track the current drag row.
     *
     * Expected values are numeric row indexes (1 for the 1st row) or
     * "NaN" if there is no current row. The current row is the row most
     * recently under the cursor during a drag. When there is no drag in
     * progress, the value is "NaN".
     */
    tableDragRowIndex: 'foldershare-drag-row-index',

    /**
     * The maximum number of menu items in a category before creating a
     * submenu.
     */
    maxCommandsBeforeSubmenu: 3,

    /*--------------------------------------------------------------------
     *
     * Initialize.
     *
     * The "environment" array created and updated by these functions
     * contains jQuery objects and assorted attributes gathered from the
     * page. Once gathered, these are passed among behavior functions so
     * that they can operate upon them without having to re-search for
     * them on the page.
     *
     *--------------------------------------------------------------------*/

    /**
     * Attaches the module's folder table menu UI behaviors.
     *
     * The folder table menu UI includes
     * - A command menu (e.g. open, new, upload, delete, etc.)
     * - A menu button used to present the command menu.
     * - Table row selection.
     * - Table row drag-and-drop for copy and move.
     * - File drag-and-drop for upload.
     * - A file dialog for upload.
     *
     * All UI elements and related elements are found, validated,
     * and behaviors attached.
     *
     * @param pageContext
     *   The page context for this call. Initially, this is the full document.
     *   Later, this is only portions of the document added via AJAX.
     * @param settings
     *   The top-level Drupal settings object.
     *
     * @return
     *   Always returns true.
     */
    attach: function (pageContext, settings) {
      var thisScript = Drupal.foldershare.UIFolderTableMenu;

      //
      // Test and exit
      // -------------
      // This method is called very frequently. It is called at least once
      // when the document is ready, and then again every time AJAX adds
      // anything to the page for any reason. This includes additions that
      // have nothing to do with this module.
      //
      // It is therefore important that this method decide quickly if the
      // context is not relevant for it, then return.
      //
      // Find top element
      // ----------------
      // The page structure contains a toolbar and table wrapper <div>,
      // then two child <div>s containing the toolbar and table:
      //
      // <div class="foldershare-toolbar-and-folder-table">
      //   <div class="foldershare-toolbar">...</div>
      //   <div class="foldershare-folder-table">...</div>
      // </div>
      //
      // The toolbar <div> contains zero or more other UI components. This
      // script adds a new menu button to the start of the toolbar.
      //
      // The table <div> contains a wrapper <div> from Views. It includes
      // two <div>s for the view's content and footer. The view content
      // contains a wrapper <div> around a <form>. And the form contains
      // exposed filters and other views-supplied form elements, and
      // finally it includes a <table> that contains rows of files and
      // folders.
      //
      // <div class="foldershare-toolbar-and-folder-table">   Top of UI
      //   <div class="foldershare-toolbar">...</div>         Toolbar
      //   <div class="foldershare-folder-table">             Table wrapper
      //     <div class="view">
      //       <div class="view-content">
      //         <div class="views-form">
      //           <form>                                     Base UI form
      //             <div class="foldershare_views_Field_baseui-ui">...</div>
      //             <table>...</table>                       Table
      //           </form>
      //         </div>
      //       </div>
      //       <div class="view-footer">...</div>             Table footer
      //     </div>
      //   </div>
      // </div>
      //
      // However, because the view can be themed, there may be more <div>s
      // added by some themes. Bootstrap-based themes, for instance, add
      // a <div> that nests the <table> within a view's <form>.
      //
      // If a view has AJAX and a pager enabled, then a page next/prev for
      // the view uses AJAX to replace the <div> with class "view" and all
      // of its content with a new page. This also replaces the inner <form>
      // and <table>. And both of those contain elements we need for behaviors.
      //
      var topSelector = '.foldershare-toolbar-and-folder-table';

      if (typeof pageContext.tagName === 'undefined') {
        // The page context is for the full document. Search down
        // through the document to find the top element(s).
        var $topElements = $(topSelector, pageContext);
        if ($topElements.length === 0) {
          // Fail. The document does not contain a top element.
          return true;
        }
      }
      else {
        // The page context is for a portion of the document. Search up
        // towards the document root fo find the top element.
        $topElements = $(pageContext).parents(topSelector).eq(0);
        if ($topElements.length === 0) {
          // Fail. The page context does not contain a top element.
          return true;
        }
      }

      //
      // Process top elements
      // --------------------
      // Process each top element. Usually there will be only one.
      $topElements.each(function (index, element) {
        // Create a new environment object.
        var env = {
          'settings':    settings,
          '$topElement': $(element),
        };

        //
        // Gather configuration
        // --------------------
        // Find forms, form elements, and the table of files and folders.
        if (thisScript.gather(env) === false) {
          // Fail. UI elements could not be found.
          return true;
        }

        //
        // Gather commands
        // ---------------
        // The UI requires a list of file/folder commands installed on the
        // server, and their attributes.
        //
        // Find the full set of commands, cull them down to those
        // available to this user on this page, then categorize them.
        // Build toolbar and context menu command lists.
        thisScript.gatherCommands(env);

        // Check if drag-and-drop is supported.
        thisScript.checkDnDSupport(env);

        //
        // Build UI
        // --------
        // Build the UI, including its menu, and attach its behaviors.
        if (thisScript.build(env) === false) {
          // Fail. Something didn't work
          return true;
        }

        // Show UI, now that it has been fully built and set up.
        env.gather.$subform.removeClass('hidden');
      });

      return true;
    },

    /**
     * Gathers UI elements.
     *
     * Pages that add the UI place it within top element <div>
     * for the UI. Nested within is a <form> that contains the UI's
     * elements. The principal elements are:
     * - Multiple input fields for the command and its parameters, including
     *   the IDs of the current selection, parent, and destination, and
     *   a file upload field.
     * - An <input> to submit the command form.
     *
     * This function searches for the UI's elements and saves them
     * into the environment:
     * - env.gather.$form = the <form> containing the UI.
     * - env.gather.$subform = the <div> within <form> containing the UI.
     * - env.gather.$table = the <table> containing the file/folder list.
     * - env.gather.$uploadInput = the file upload <input>.
     * - env.gather.$commandInput = the command ID <input>.
     * - env.gather.$selectionIdInput = the selection <input>.
     * - env.gather.$parentIdInput = the parent ID <input>.
     * - env.gather.$destinationIdInput = the destination ID <input>.
     * - env.gather.$commandSubmitButton = the button for submitting the form.
     * - env.gather.nameColumn = the table column name for the name & attrib.
     *
     * @param env
     *   The environment object containing saved object references for
     *   elements to operate upon. The object is updated on success.
     *
     * @return
     *   Returns TRUE on success and FALSE otherwise.
     */
    gather: function (env) {
      var utility    = Drupal.foldershare.utility;
      var base       = 'foldershare-folder-table';
      var nameColumn = 'views-field-name';

      //
      // Find form
      // ---------
      // From the top element wrapping the forms and view, search downward
      // for the <form> wrapping the main UI's elements.
      //
      //   <div class="foldershare-toolbar-and-folder-table ...">
      //     ...
      //     ... <form class="foldershare-folder-table-menu-form">
      //          ...
      //     ... </form>
      //     ...
      //   </div>
      //
      // Nesting may add intermediate <div>s throughout.
      var cls = base + '-menu-form';
      var $commandForm = $('.' + cls, env.$topElement).eq(0);
      if ($commandForm.length === 0) {
        utility.printMalformedError(
          'The required <form> with class "' + cls + '" could not be found.');
        return false;
      }

      //
      // Find main UI's subform
      // --------------------------
      // Within the <form>, the main UI wraps its form elements within
      // a <div>. The <div> is important because the same <div> has data
      // attributes attached that describe the parent entity, access
      // permissions, etc.
      cls = base + '-menu';
      var $subform = $('.' + cls, $commandForm).eq(0);
      if ($subform.length === 0) {
        utility.printMalformedError(
          'The required <div> with class "' + cls + '" could not be found');
        return false;
      }

      //
      // Find submit
      // -----------
      // Normally, Drupal creates an <input> for submit buttons,
      // but some themes (such as Bootstrap) convert these to <button>.
      // Functionally, these are similar but it means we have to look
      // for either type.
      var $commandSubmitButton = $('input[type=\"submit\"]', $commandForm).eq(0);
      if ($commandSubmitButton.length === 0) {
        $commandSubmitButton = $('button[type=\"submit\"]', $commandForm).eq(0);
        if ($commandSubmitButton.length === 0) {
          utility.printMalformedError(
            'A submit <input> or <button> could not be found.');
          return false;
        }
      }

      //
      // Find inputs
      // -----------
      // The form contains several <input> items used to hold information
      // from the UI operation:
      // - A file upload <input>.
      // - A command ID <input>.
      // - A selection IDs <input>.
      // - A destination ID <input>.
      // - A parent ID <input>.
      //
      // The upload field's name uses special [] array syntax
      // imposed by the Drupal file module.
      var $uploadInput = $('input[name="files[foldershare-folder-table-menu-upload][]"]', $commandForm).eq(0);
      if ($uploadInput.length === 0) {
        utility.printMalformedError(
          'The main UI file input field is missing.');
        return false;
      }

      var $commandInput = $('input[name="foldershare-folder-table-menu-commandname"]', $commandForm).eq(0);
      if ($commandInput.length === 0) {
        utility.printMalformedError(
          'The main UI command ID field is missing.');
        return false;
      }

      var $selectionIdInput = $('input[name="foldershare-folder-table-menu-selection"]', $commandForm).eq(0);
      if ($selectionIdInput.length === 0) {
        utility.printMalformedError(
          'The main UI selection IDs field is missing.');
        return false;
      }

      var $destinationIdInput = $('input[name="foldershare-folder-table-menu-destinationId"]', $commandForm).eq(0);
      if ($destinationIdInput.length === 0) {
        utility.printMalformedError(
          'The main UI destination ID field is missing.');
        return false;
      }

      var $parentIdInput = $('input[name="foldershare-folder-table-menu-parentId"]', $commandForm).eq(0);
      if ($parentIdInput.length === 0) {
        utility.printMalformedError(
          'The main UI parent ID field is missing.');
        return false;
      }

      //
      // Find table
      // ----------
      // Search for a views <table>.
      cls = 'views-table';

      var $table = $('table.' + cls, env.$topElement).eq(0);
      if ($table.length === 0) {
        utility.printMalformedError(
          "The required <table> with class '" + cls + "' could not be found.");
        return false;
      }

      //
      // Update environment
      // ------------------
      // Save main UI objects.
      env['gather'] = {
        '$commandForm':         $commandForm,
        '$subform':             $subform,
        '$table':               $table,
        '$tbody':               $table.find('tbody'),
        'nameColumn':           nameColumn,
        '$uploadInput':         $uploadInput,
        '$commandInput':        $commandInput,
        '$selectionIdInput':    $selectionIdInput,
        '$destinationIdInput':  $destinationIdInput,
        '$parentIdInput':       $parentIdInput,
        '$commandSubmitButton': $commandSubmitButton,
      };

      return true;
    },

    /**
     * Determines if copy, move, and/or upload drag-and-drop is supported.
     *
     * If there is no parent, or if the parent is a folder or root folder,
     * then drag-and-drop operations may be supported if the command set
     * includes commands for copy, move, and/or file upload.
     *
     * This function checks the parent kind and command list and sets
     * flags into the environment:
     * - env.dndCopyEnabled = true if enabled.
     * - env.dndMoveEnabled = true if enabled.
     * - env.dndUploadEnabled = true if enabled.
     * - env.dndUploadChecked = false.
     *
     * @param env
     *   The environment object.
     */
    checkDnDSupport: function (env) {
      var thisScript = Drupal.foldershare.UIFolderTableMenu;

      switch (env.settings.foldershare.page.kind) {
        case 'none':
        case 'folder':
        case 'rootfolder':
          env.dndCopyEnabled   = thisScript.copyCommand in env.mainCommands;
          env.dndMoveEnabled   = thisScript.moveCommand in env.mainCommands;
          env.dndUploadEnabled = thisScript.uploadCommand in env.mainCommands;
          env.dndUploadChecked = false;
          break;

        default:
          // For any other kind (e.g. file, image, or media), there are no
          // children so drag-and-drop doesn't make sense.
          env.dndCopyEnabled   = false;
          env.dndMoveEnabled   = false;
          env.dndUploadEnabled = false;
          env.dndUploadChecked = false;
          break;
      }
    },

    /**
     * Determine if file drag to file upload is supported by this browser.
     *
     * Modern browsers allow the "files" value of a file input field to
     * be set with a FileList object. The only way to get such an object
     * is from an event's dataTransfer, which is why we need to check
     * this browser ability from within an event behavior.
     *
     * If the browser throws an exception when attempting to set the
     * "files" value, then the browser is old and does not support the
     * feature. Without that feature, we cannot put the names of dragged
     * files into the file input field and therefore cannot do the upload.
     *
     * @param ev
     *   The file drag event.
     * @param env
     *   The environment object.
     *
     * @return
     *   Returns false if file drag support is not available.
     */
    checkFileDragSupport: function (ev, env) {
      // If the file upload command is not available or if a prior call to
      // this method has already determined that file drag-and-drop is not
      // supported, then return false.
      if (env.dndUploadEnabled === false) {
        return false;
      }

      // If file upload support in the browser has already been checked,
      // then return true.
      if (env.dndUploadChecked === true) {
        return env.dndUploadEnabled;
      }

      // Check if the browser is not properly supporting data transfer
      // properties.
      if (typeof ev.originalEvent.dataTransfer === 'undefined' ||
          typeof ev.originalEvent.dataTransfer.files === 'undefined') {
        // Fail. The data transfer or files properties are missing.
        // The browser does not appear to be supporting drag-and-drop.
        env.dndUploadEnabled = false;
        env.dndUploadChecked = true;
        return env.dndUploadEnabled;
      }

      // Try to set the file upload field.
      try {
        env.gather.$uploadInput[0].files =
          ev.originalEvent.dataTransfer.files;
      }
      catch (er) {
        // Fail. Old browser does not support setting the "files" value.
        // File drag not supportable.
        env.dndUploadEnabled = false;
        env.dndUploadChecked = true;

        // Tell the user the drag is not supported.
        var text = '<div>';
        var translated = env.settings.foldershare.terminology.text.upload_dnd_not_supported;
        if (typeof translated === 'undefined') {
          text += '<p><strong>Drag-and-drop file upload is not supported.</strong></p>';
          text += '<p>This feature is not supported by this web browser.</p>';
        }
        else {
          text += translated;
        }
        text += '</div>';
        Drupal.dialog(text, {}).showModal();
        return env.dndUploadEnabled;
      }

      env.dndUploadEnabled = true;
      env.dndUploadChecked = true;
      return env.dndUploadEnabled;
    },

    /**
     * Determine if file drag to file upload is valid.
     *
     * Users may select an arbitrary mix of files and folders in the
     * file browser of modern OSes, such as the Mac Finder or the Windows
     * Explorer. Those can be dragged to a browser window and into the
     * drag-and-drop area of this module in order to trigger an upload.
     *
     * HOWEVER, web browsers currently only support dragging and uploading
     * files. And yet it remains possible for a user to try to drag in a
     * folder.  This function checks the entries in the FileList being
     * dragged and verifies that they are all files, and no folders.
     * On success or failure the appropriate given function is called.
     *
     * Note that this function is ASYNCHRONOUS (because the underlying
     * file reading API is), so it will return immediately while file
     * checking continues in the background.
     *
     * @param ev
     *   The file drag event.
     * @param env
     *   The environment object.
     * @param onValid
     *   The function to call if the file drag is valid. The function
     *   is called with the original event, environment, and file list.
     * @param onInvalid
     *   The function to call if the file drag is not valid. The function
     *   is called with the original event, environment, and file list.
     */
    checkFileDragValid: function (ev, env, onValid, onInvalid) {
      // Check that the event has dataTransfer and files fields and
      // that there are actual files in the drag.
      if (typeof ev.originalEvent.dataTransfer === 'undefined' ||
          typeof ev.originalEvent.dataTransfer.files === 'undefined') {
        // Fail. Malformed event.
        onInvalid(ev, env, null);
      }

      var fileList = ev.originalEvent.dataTransfer.files;
      var nFiles = fileList.length;

      if (nFiles === 0) {
        // Fail. Empty file list.
        onInvalid(ev, env, fileList);
      }

      // Loop over the list and validate each entry.
      //
      // Each file entry has a name, size, type, and last modified date.
      // Unfortunately, NONE OF THESE can be used by themselves to reliably
      // detect a folder vs. a file.
      //
      // Files and folders both have non-empty names. Further, names are just
      // the file/folder name itself, without a leading path or trailing '/'.
      // So, names may not be used for validity checking.
      //
      // Files and folders both have sizes. While there are on-line claims
      // that folders will only have sizes that are a multiple of 4096 bytes,
      // this is entirely bogus. The size reported for a dragged folder
      // depends upon the OS and its configuration. So, sizes may not be used
      // for validity checking.
      //
      // Files and folders both have modified dates. There is no distinguishing
      // feature here between files and folders, so this is not useful for
      // validity checking.
      //
      // Finally, the type property contains the MIME type of the dragged item.
      // Folders do not have a MIME type, so at first this would seem to be an
      // indicator. BUT...
      // - A file with no extension also has no MIME type.
      // - A folder with an extension has a bogus MIME type.
      //
      // So the MIME type property can be empty for a file, or set for a
      // folder. This makes it not useful for validity checking.
      //
      // This means that NONE of the available properties are useful indicators
      // of files vs. folders. What we CAN DO is try to read bytes from the
      // item. Reading a file will succeed. Reading a folder will fail.
      //
      // FileReader() works asynchronously. If we try to start up too many
      // simultaneous reads, we'll get an error (varies by browser). So we
      // need to serialize this. We do this with:
      //   - load handler: called when the file starts loading. We immediately
      //     abort since we don't need to waste time reading the whole file.
      //
      //   - load end handler:  called when done reading a file, including
      //     when a file load has been aborted. We check if there is another
      //     file to read and start up that read. This continues until there
      //     are no more files to read.
      //
      // To catch errors, we use a:
      //   - error handler: called on an error, such as permission denied or
      //     trying to read a folder. Increment an error counter.
      //
      // If no errors occur, the process will go through the files in order,
      // trying to read each one, aborting, trying the next, etc. When they've
      // all been read, the validity handler is called.
      //
      // If an error occurs, the process will abort and call the invalidity
      // handler.
      var nChecked = 0;
      var nErrors  = 0;
      var i = 0;
      var reader = new FileReader();

      reader.onerror= function (ev) {
        ++nErrors;
      };
      reader.onload = function (ev) {
        ev.target.abort();
      }
      reader.onloadend = function (ev) {
        // If any error has occurred, stop and report invalid.
        if (nErrors > 0) {
          onInvalid(ev, env, fileList);
          return;
        }

        // If we're done checking files, report valid.
        ++nChecked;
        if (nChecked === nFiles) {
          onValid(ev, env, fileList);
          return;
        }

        // Otherwise, when there is more to read, start reading the
        // next file.
        ++i;
        ev.target.readAsArrayBuffer(fileList[i]);
      }

      // Start it up on the first file.
      reader.readAsArrayBuffer(fileList[i]);
    },

    /**
     * Gathers a list of file/folder commands for use in main & context menus.
     *
     * A full list of file/folder commands installed for the Drupal module
     * is included in the DrupalSettings saved in the given environment.
     * This list is culled here into two lists:
     * - Commands for the toolbar menu.
     * - Commands for the row context menu (e.g. via a right-click).
     *
     * Commands are culled if:
     * - The page's entity kind is not supported by the command.
     * - The command requires a selection but the page entity has no children.
     * - The user doesn't have necessary permissions on the parent.
     * - The user doesn't have necessary permissions on selections.
     * - The user doesn't have necessary permissions for special handling.
     *
     * The context menu command list also culls commands if:
     * - They never use a selection.
     * - They require upload or create special handling.
     *
     * Commands are then grouped by their named category. Category names may
     * be anything, but a set of well-known categories are defined by the
     * module and passed in as settings to this script. A typical
     * well-known category list includes:
     * - open
     * - import
     * - export
     * - close
     * - edit
     * - delete
     * - copymove
     * - save
     * - archive
     * - settings
     * - administer
     *
     * Unknown categories are added to the end. Any category may be empty.
     *
     * This function saves two command list objects to the environment.
     * Each object has one property for each supported command:
     * - env.mainCommands = the list of supported main menu commands.
     * - env.contextCommands = the list of supported context menu commands.
     *
     * For a command with ID 'abc', the definition is available at
     * env.mainCommands['abc'] or env.contextCommands['abc'] if it is
     * available for the main menu or context menu.
     *
     * Categorized main menu and context menu command lists are created and
     * added to the environment. Each list is an array, sorted by category
     * name. The value contains the name and an array of commands in the
     * category, also sorted by name. The commands are referred to by
     * command ID.
     * - env.mainCategories = the sorted list of categories and commands.
     * - env.contextCategories = the sorted list of categories and commands.
     *
     * @param env
     *   The environment object.
     *
     * @return
     *   Always returns true.
     */
    gatherCommands: function (env) {
      //
      // Cull commands
      // -------------
      // Cull the full command list into command lists for the main menu
      // and context menu. Commands are culled if:
      // - The page's entity kind is not supported by the command.
      // - The command requires a selection but the page entity has no children.
      // - The user doesn't have necessary permissions on the parent.
      // - The user doesn't have necessary permissions on selections.
      // - The user doesn't have necessary permissions for special handling.
      //
      // The context menu command list also culls commands if:
      // - They never use a selection.
      // - They require upload or create special handling.
      //
      // The mainCommands and contextCommands objects have one field per
      // command so that command information may be quickly looked up by
      // command ID.
      var mainCommands    = Object.create(null);
      var contextCommands = Object.create(null);

      var allCommands    = env.settings.foldershare.commands;
      var pageEntityId   = env.settings.foldershare.page.id;
      var pageEntityKind = env.settings.foldershare.page.kind;
      var pageAccess     = env.settings.foldershare.user.pageAccess;

      // Loop through all commands and cull them into main and context
      // menu lists.
      for (var commandId in allCommands) {
        var def = allCommands[commandId];

        //
        // Parent suitable
        // ---------------
        // The parent constraints of a command list the entity kinds it
        // supports for the parent (page) entity. Known values include
        // the FolderShare entity kinds:
        // - file
        // - image
        // - media
        // - folder
        // - rootfolder
        //
        // Two additional special values are supported:
        // - none
        // - any
        //
        // A command is valid for the current page if the page's kind
        // is in the command's parent constraints list.
        var allowedParentKinds = def.parentConstraints.kinds;

        if (pageEntityKind === 'none') {
          // The page has no kind because the page is showing a list of
          // root folders. A command is available only if the command
          // supports having no parent.
          if (allowedParentKinds.includes('none') === false) {
            // Fail. The command does not support a page with no parent entity.
            continue;
          }
        }
        else {
          // The page has a kind. A command is available if that kind is
          // in the command's list, or if the special 'any' value is in
          // the list.
          if (allowedParentKinds.includes(pageEntityKind) === false &&
            allowedParentKinds.includes('any') === false) {
            // Fail. Parent's kind is not suitable for this command.
            continue;
          }
        }

        //
        // Selection suitable
        // ------------------
        // The selection constraints of a command list styles of selection
        // supported. Known values include:
        // - none
        // - one
        // - many
        // - parent
        //
        // The special 'parent' value means that when there is no selection,
        // the command can default to the page's parent entity.
        //
        // If a command requires a selection, then the page entity kind
        // must be a folder or root folder because other kinds don't have
        // children and thus can't have a selection.
        //
        // A command is valid for the current page if the page's kind
        // can support the type of selection required.
        var allowedSelectionTypes = def.selectionConstraints.types;

        var selectable = false;
        switch (pageEntityKind) {
          case 'none':
          case 'folder':
          case 'rootfolder':
            selectable = true;
            break;
        }

        if (selectable === false &&
            allowedSelectionTypes.includes('none') === false &&
            allowedSelectionTypes.includes('parent') === false) {
          // Fail. The page's kind is not a folder, yet the command does not
          // support having no selection or reverting to the parent. The
          // command therefore always requires a selection, and yet no
          // selection is possible on this page.
          continue;
        }

        //
        // Parent and selection access
        // ---------------------------
        // The command's parent and selection access requirements provide
        // a single permission that is required. Known values include:
        // - chown
        // - delete
        // - share
        // - update
        // - view
        // - none
        //
        // A command is valid if the parent and selection access needs are
        // listed in the user's permissions for this page, or if the
        // access requirement is 'none'.
        var parentAccess = def.parentConstraints.access;

        if (parentAccess !== 'none' &&
            pageAccess.includes(parentAccess) === false) {
          // Fail. The command requires special permission for accessing
          // the parent but the user does not have it.
          continue;
        }

        var selectionAccess = def.selectionConstraints.access;
        if (selectionAccess !== 'none' &&
            pageAccess.includes(selectionAccess) === false) {
          // Fail. The command requires special permission for accessing
          // the selection but the user does not have it.
          continue;
        }

        //
        // Special handling access
        // -----------------------
        // A command may require special handling. Known values include:
        // - create
        // - upload
        //
        // For create checking, a command is valid if the user has permission
        // to create a root folder (if the page is a root list) or a
        // subfolder (if the page is not a root list).
        if (def.specialHandling.includes('create') === true) {
          // The command requires create access. If the current page is
          // a root list, the permission required is 'createroot'. Otherwise
          // it is 'create'.
          var accessNeeded = 'create';
          if (pageEntityKind === 'none') {
            accessNeeded = 'createroot';
          }

          if (pageAccess.includes(accessNeeded) === false) {
            // Fail. The command requires create permission but the user
            // does not have it.
            continue;
          }
        }

        //
        // Main menu command
        // -----------------
        // For the main menu, the above culling is sufficient. Commands
        // will operate on either the page entity (parent) or a selection
        // of one or more children (if available).
        mainCommands[commandId] = def;

        //
        // Context menu command
        // --------------------
        // For the context menu, there is always a selection (the row(s) the
        // menu is shown for). Further culling is needed to remove:
        // - Commands that do not use a selection.
        // - Commands that upload.
        var cullContext = false;
        if (def.specialHandling.includes('upload') === true) {
          cullContext = true;
        }
        if (allowedSelectionTypes.includes('one') === false &&
            allowedSelectionTypes.includes('many') === false) {
          cullContext = true;
        }

        if (cullContext === false) {
          contextCommands[commandId] = def;
        }
      }

      // Save the command lists back to the environment.
      env.mainCommands    = mainCommands;
      env.contextCommands = contextCommands;

      //
      // Get well-known categories
      // -------------------------
      // Start with a list of well-known categories and add them
      // to the category list for the main and context menus.
      var mainCategories    = new Map();
      var contextCategories = new Map();

      var categoryTerms = env.settings.foldershare.terminology.categories;

      for (var index in env.settings.foldershare.categories) {
        var cat = env.settings.foldershare.categories[index];

        // Get the translated term, if any.
        var term = cat;
        if (categoryTerms.hasOwnProperty(cat) === true) {
          term = categoryTerms[cat];
        }

        // Add the category.
        mainCategories.set(cat, {
          'name':  term,
          'commandIds': [],
        });
        contextCategories.set(cat, {
          'name':  term,
          'commandIds': [],
        });
      }

      //
      // Categorize commands
      // -------------------
      // Loop through all commands and add them to categories. If a command
      // uses an unrecognized category, add it to a separate category list.
      var extraMainCategories    = new Map();
      var extraContextCategories = new Map();

      for (var commandId in env.mainCommands) {
        var def = env.mainCommands[commandId];
        var cat = def.category;

        if (mainCategories.has(cat) === true) {
          mainCategories.get(cat).commandIds.push(commandId);
        }
        else if (extraMainCategories.has(cat) === true) {
          extraMainCategories.get(cat).commandIds.push(commandId);
        }
        else {
          // Get the translated term, if any.
          var term = cat;
          if (categoryTerms.hasOwnProperty(cat) === true) {
            term = categoryTerms[cat];
          }

          // Convert to title-case.
          term = term.charAt(0).toUpperCase() +
            term.substr(1).toLowerCase();

          extraMainCategories.set(cat, {
            'name': cat,
            'commandIds': [commandId],
          });
        }
      }

      for (var commandId in env.contextCommands) {
        var def = env.contextCommands[commandId];
        var cat = def.category;

        if (contextCategories.has(cat) === true) {
          contextCategories.get(cat).commandIds.push(commandId);
        }
        else if (extraContextCategories.has(cat) === true) {
          extraContextCategories.get(cat).commandIds.push(commandId);
        }
        else {
          // Get the translated term, if any.
          var term = cat;
          if (categoryTerms.hasOwnProperty(cat) === true) {
            term = categoryTerms[cat];
          }

          // Convert to title-case.
          term = term.charAt(0).toUpperCase() +
            term.substr(1).toLowerCase();

          extraContextCategories.set(cat, {
            'name': cat,
            'commandIds': [commandId],
          });
        }
      }

      //
      // Sort commands by weight and name
      // --------------------------------
      // Loop through all of the categories for main and context menus and:
      // - Remove empty categories.
      // - Sort category commands by their weight and name.
      var mainSortFunction = function (a, b) {
        var adef = env.mainCommands[a];
        var bdef = env.mainCommands[b];

        var diff = Number(adef.weight) - Number(bdef.weight);
        if (diff !== 0) {
          return diff;
        }
        if (adef.menuNameDefault < bdef.menuNameDefault) {
          return -1;
        }
        if (adef.menuNameDefault > bdef.menuNameDefault) {
          return 1;
        }
        return 0;
      };

      var contextSortFunction = function (a, b) {
        var adef = env.contextCommands[a];
        var bdef = env.contextCommands[b];

        var diff = Number(adef.weight) - Number(bdef.weight);
        if (diff !== 0) {
          return diff;
        }
        if (adef.menuNameDefault < bdef.menuNameDefault) {
          return -1;
        }
        if (adef.menuNameDefault > bdef.menuNameDefault) {
          return 1;
        }
        return 0;
      };

      for (var [cat, value] of mainCategories) {
        if (value.commandIds.length === 0) {
          mainCategories.delete(cat);
        }
        else {
          value.commandIds.sort(mainSortFunction);
        }
      }

      for (var [cat, value] of extraMainCategories) {
        value.commandIds.sort(mainSortFunction);
      }

      for (var [cat, value] of contextCategories) {
        if (value.commandIds.length === 0) {
          contextCategories.delete(cat);
        }
        else {
          value.commandIds.sort(contextSortFunction);
        }
      }

      for (var [cat, value] of extraContextCategories) {
        value.commandIds.sort(contextSortFunction);
      }

      //
      // Sort and add extra categories by name
      // -------------------------------------
      if (extraMainCategories.size !== 0) {
        // Collect the extra category names and sort.
        var cats = [];
        for (var [cat, value] of extraMainCategories) {
          cats.push(value.name);
        }
        cats.sort();

        // Append the extra categories to the main list, sorted by name.
        for (var cat in cats) {
          mainCategories.set(cat, extraMainCategories.get(cat));
        }
      }

      if (extraContextCategories.size !== 0) {
        // Collect the extra category names and sort.
        var cats = [];
        for (let cat of extraContextCategories) {
          cats.push(value.name);
        }
        cats.sort();

        // Append the extra categories to the context list, sorted by name.
        for (var cat in cats) {
          contextCategories.set(cat, extraContextCategories.get(cat));
        }
      }

      // Save the categorized lists back to the environment.
      env.mainCategories    = mainCategories;
      env.contextCategories = contextCategories;

      return true;
    },

    /*--------------------------------------------------------------------
     *
     * Build the folder table menu UI.
     *
     *--------------------------------------------------------------------*/

    /**
     * Builds the UI.
     *
     * The main UI has several features:
     * - A hierarchical main menu that pops up from a menu button.
     * - A context menu that pops up from a right-click on a row.
     * - Selectable rows in the view table.
     * - Drag-and-drop of selected rows to a subfolder.
     * - Drag-and-drop from the host into the table to do an upload.
     *
     * @param env
     *   The environment object.
     *
     * @return
     *   Returns TRUE on success and FALSE otherwise.
     */
    build: function (env) {
      var thisScript = Drupal.foldershare.UIFolderTableMenu;

      //
      // Create main menu button
      // -----------------------
      // Create the main menu button and append it to the command subform.
      // If there is a button already there, remove it first.
      $('.foldershare-folder-table-mainmenu-button', env.gather.$subform).remove();
      env.gather.$subform.prepend(
        '<button type="button" class="foldershare-folder-table-mainmenu-button">' +
        '<span>' +
        Drupal.foldershare.utility.getTerm(
          env.settings.foldershare.terminology, 'menu') +
        '</span>' +
        '</button>');
      var $menuButton = $('.foldershare-folder-table-mainmenu-button', env.gather.$subform);
      $menuButton.button().show();

      //
      // Create main menu
      // ----------------
      // Create the main menu HTML and append it to the command subform.
      // If there is a menu already there, remove it first.
      $('.foldershare-folder-table-mainmenu', env.gather.$subform).remove();
      env.gather.$subform.append(thisScript.buildMainMenu(env));
      var $menu = $('.foldershare-folder-table-mainmenu', env.gather.$subform);
      $menu.menu().hide();
      $menu.removeClass('hidden');

      //
      // Create context menu
      // -------------------
      // Create the context menu HTML and append it to the command subform.
      // If there is a menu already there, remove it first.
      $('.foldershare-folder-table-contextmenu', env.gather.$subform).remove();
      env.gather.$subform.append(thisScript.buildContextMenu(env));
      var $contextMenu = $('.foldershare-folder-table-contextmenu', env.gather.$subform);
      $contextMenu.menu().hide();
      $contextMenu.removeClass('hidden');

      //
      // Attach main menu button behavior
      // --------------------------------
      // When the main menu button is pressed, show the main menu.
      // When the menu is about to be shown, update all menu items to
      // enable/disable and adjust the text to reflect the selection.
      $menuButton.off('click.foldershare');
      $menuButton.on('click.foldershare', function (ev) {
        if ($menu.menu().is(":visible")) {
          // When the menu is already visible, hide it.
          $menu.menu().hide();
        }
        else {
          // Update the menu's text based on the selection.
          thisScript.menuUpdate(env, $menu);

          // Position the menu and show it.
          $menu.show().position({
            my:        "left top",
            at:        "left bottom",
            of:        ev.target,
            collision: "fit"
          });

          // Register a handler to catch an off-menu click to hide it.
          $(document).on('click.foldershare', function (ev) {
            $menu.menu().hide();
          } );
        }

        return false;
      });

      //
      // Attach main menu item behavior
      // ------------------------------
      // When a menu item is selected, trigger the command.
      $menu.off('menuselect.foldershare');
      $menu.on('menuselect.foldershare', function (ev, ui) {
        // Insure the menu is hidden.
        $menu.menu().hide();

        // Fill the server form.
        var command = $(ui.item).attr('data-foldershare-command');
        thisScript.serverCommandSetup(
          env,
          command,
          null,
          null,
          thisScript.tableGetSelectionIds(env),
          null);

        var specialHandling = env.mainCommands[command].specialHandling;
        if ($.inArray('upload', specialHandling) !== (-1)) {
          // Show file dialog.
          env.gather.$uploadInput.click();
        }
        else {
          // Submit form.
          thisScript.serverCommandSubmit(env);
        }

        return true;
      });

      //
      // Attach context menu item behavior
      // ---------------------------------
      // When a menu item is selected, trigger the command.
      $contextMenu.off('menuselect.foldershare');
      $contextMenu.on('menuselect.foldershare', function (ev, ui) {
        // Insure the menu is hidden.
        $contextMenu.menu().hide();

        // Fill the server form.
        var command = $(ui.item).attr('data-foldershare-command');
        thisScript.serverCommandSetup(
          env,
          command,
          null,
          null,
          thisScript.tableGetSelectionIds(env),
          null);

        var specialHandling = env.contextCommands[command].specialHandling;
        if ($.inArray('upload', specialHandling) !== (-1)) {
          // Show file dialog.
          env.gather.$uploadInput.click();
        }
        else {
          // Submit form.
          thisScript.serverCommandSubmit(env);
        }

        return true;
      });

      //
      // Attach upload behavior
      // ----------------------
      // When a file dialog is closed, and there is a file selection,
      // trigger an upload command.
      env.gather.$uploadInput.off('change.foldershare');
      env.gather.$uploadInput.on(
        'change.foldershare',
        function (ev, ui) {
          // When called, the upload field's file list has already been
          // set via the browser's file dialog. The other fields of the
          // command form were set up when the menu command was selected.
          thisScript.serverCommandSubmit(env);
        });

      //
      // Add table behaviors
      // -------------------
      // Add table and table row behaviors, such as for row selection,
      // drag-and-drop, and the context menu.
      thisScript.tableAttachBehaviors(env);

      return true;
    },

    /**
     * Builds the <ul> for the main menu.
     *
     * The list of commands suitable for the user and page is used to
     * create HTML containing a nested <ul> list. Each <li> in the list
     * is either an available command or the name of a submenu.
     *
     * @param env
     *   The environment object.
     *
     * @return
     *   Returns HTML for the main menu.
     */
    buildMainMenu: function (env) {
      var thisScript = Drupal.foldershare.UIFolderTableMenu;

      // Start the <ul>.
      var html = '<ul class="hidden foldershare-folder-table-mainmenu">';

      // Loop through all main menu categories.
      var maxBeforeSub = thisScript.maxCommandsBeforeSubmenu;
      var addSeparator = false;
      for (var [cat, value] of env.mainCategories) {
        // Add a separator before the next category of commands.
        if (addSeparator === true) {
          html += '<li>-</li>';
        }
        addSeparator = true;

        // Create a submenu if the category is large enough.
        var addSubmenu = false;
        if (value.commandIds.length > maxBeforeSub) {
          html += '<li><div>' + value.name + '</div><ul>';
          addSubmenu = true;
        }

        // Add the category's commands.
        for (var index in value.commandIds) {
          var commandId = value.commandIds[index];
          var label = env.mainCommands[commandId].menuNameDefault;
          html += '<li data-foldershare-command="' + commandId + '"><div>' + label + '</div></li>';
        }

        if (addSubmenu === true) {
          html += '</ul></li>';
        }
      }

      html += '</ul>';

      return html;
    },

    /**
     * Builds the <ul> for the context menu.
     *
     * The list of commands suitable for the user and page is used to
     * create HTML containing a nested <ul> list. Each <li> in the list
     * is either an available command or the name of a submenu.
     *
     * @param env
     *   The environment object.
     *
     * @return
     *   Returns HTML for the context menu.
     */
    buildContextMenu: function (env) {
      var thisScript = Drupal.foldershare.UIFolderTableMenu;

      // Start the <ul>.
      var html = '<ul class="hidden foldershare-folder-table-contextmenu">';

      // Loop through all context menu categories.
      var maxBeforeSub = thisScript.maxCommandsBeforeSubmenu;
      var addSeparator = false;
      for (var [cat, value] of env.contextCategories) {
        // Add a separator before the next category of commands.
        if (addSeparator === true) {
          html += '<li>-</li>';
        }
        addSeparator = true;

        // Create a submenu if the category is large enough.
        var addSubmenu = false;
        if (value.commandIds.length > maxBeforeSub) {
          html += '<li><div>' + value.name + '</div><ul>';
          addSubmenu = true;
        }

        // Add the category's commands.
        for (var index in value.commandIds) {
          var commandId = value.commandIds[index];
          var label = env.contextCommands[commandId].menuNameDefault;
          html += '<li data-foldershare-command="' + commandId + '"><div>' + label + '</div></li>';
        }

        if (addSubmenu === true) {
          html += '</ul></li>';
        }
      }

      html += '</ul>';

      return html;
    },

    /*--------------------------------------------------------------------
     *
     * Menu.
     *
     *--------------------------------------------------------------------*/

    /**
     * Updates a menu to enable/disable commands based on the selection.
     *
     * For each menu item, the selection constraints of the associated
     * command are checked against the given selection. If the constraints
     * are not met, the menu item is disabled and its text is set to generic
     * menu item text (e.g. "Delete"). If the constraints are met, the item
     * is enabled and its text is set appropriate for the selection (e.g.
     * "Delete Folder").
     *
     * @param env
     *   The environment object.
     * @param $menu
     *   The menu.
     */
    menuUpdate: function (env, $menu) {
      var thisScript = Drupal.foldershare.UIFolderTableMenu;

      // Count the number of selected items. There could be zero.
      var selection = thisScript.tableGetSelectionIdsByKind(env);
      var nSelected = 0;
      for (var k in selection) {
        nSelected += selection[k].length;
      }

      // Get operand text describing the selection. This text may be
      // inserted into menu item labels.
      var operand = thisScript.menuGetOperandText(env, selection);

      // Loop through the menu and enable items that are suitable for the
      // current selection, and disable those that are not.
      $('.ui-menu-item', $menu).each( function() {
        var $item = $(this);

        // Skip irrelevant menu items.
        if ($item.hasClass('ui-menu-divider') === true) {
          // Skip. Ignore separators.
          return true;
        }

        if ($item.hasClass('ui-state-broken') === true) {
          // Skip. Ignore broken menu items.
          return true;
        }

        // Get the menu item's command ID.
        var commandId = $item.attr('data-foldershare-command');
        if (typeof commandId === 'undefined') {
          // Fail. Malformed menu item!
          return true;
        }

        if (commandId in env.settings.foldershare.commands === false) {
          // Fail. Unknown command. Mark it broken.
          $item.removeClass('ui-state-enabled');
          $item.addClass('ui-state-disabled');
          $item.addClass('ui-state-broken');
          return true;
        }

        // Validate the selection against the command's constraints.
        var text = '';
        if (thisScript.checkSelectionConstraints(
          env,
          nSelected,
          selection,
          commandId) === false) {
          // The command is not enabled in this context. Perhaps the
          // selection doesn't match what the command needs, or the
          // access permissions aren't right.
          //
          // In any case, we need to:
          // - Mark the menu item as disabled.
          // - Make its menu text generic.
          $item.removeClass('ui-state-enabled');
          $item.addClass('ui-state-disabled');

          // Generic text is encoded as an attribute on the menu item.
          // Get it and replace the user-visible text with that generic text.
          text = env.settings.foldershare.commands[commandId].menuNameDefault;
        }
        else {
          // The command is enabled in this context. The selection must
          // have satisfied the command's constraints, or perhaps it doesn't
          // need a selection.
          //
          // In any case, we need to:
          // - Mark the command as enabled.
          // - Make its menu text specific to the selection.
          $item.removeClass('ui-state-disabled');
          $item.addClass('ui-state-enabled');

          // Specific menu text, with a '@operand' placeholder, is encoded
          // as an attribute on the men item. Get it, substitute '@operand'
          // with a suitable comment on the selection, then replace the
          // user-visible text of the menu item with the new text.
          text = env.settings.foldershare.commands[commandId].menuName;
          text = text.replace('@operand', operand);
        }

        var $innerDiv = $('div', $item).text(text);
      });
    },

    /**
     * Returns operand text based on the current selection.
     *
     * Operand text briefly describes the current selection. The text is
     * suitable for embedding within a menu item's name.
     *
     * @param env
     *   The environment object.
     * @param selection
     *   The current selection.
     *
     * @return
     *   Returns text describing the current selection.
     */
    menuGetOperandText: function (env, selection) {
      //
      // Scan selection
      // --------------
      // Get the selection, which may be empty, then scan it to collect
      // information characterizing it:
      // - The total number of selected items.
      // - The total number of different kinds of selected items.
      // - The kind, if there is only one in use.
      var nSelected = 0;
      var nKinds = 0;
      var kind = '';

      for (var k in selection) {
        var len = selection[k].length;
        nSelected += len;
        if (len > 0) {
          nKinds++;
          kind = k;
        }
      }

      var terminology = env.settings.foldershare.terminology;

      //
      // No selection case
      // -----------------
      // When there is no selection, use the kind of the page entity.
      if (nSelected === 0) {
        kind = env.settings.foldershare.page.kind;
        if (kind === 'rootfolder') {
          kind = 'folder';
        }
        return Drupal.foldershare.utility.getTerm(terminology, 'this', false) + ' ' +
          Drupal.foldershare.utility.getKindSingular(terminology, kind);
      }

      // Simplify the folder kind.
      if (kind === 'rootfolder') {
        kind = 'folder';
      }

      //
      // Single selection
      // ----------------
      // When there is just one item selected, use the kind of the selection.
      if (nSelected === 1) {
        return Drupal.foldershare.utility.getKindSingular(terminology, kind);
      }

      //
      // Multiple selection, one kind
      // ----------------------------
      // When there are multiple items selected, but all of the same kind,
      // use the kind of the selection.
      if (nKinds === 1) {
        return Drupal.foldershare.utility.getKindPlural(terminology, kind);
      }

      //
      // Multiple selection, multiple kinds
      // ----------------------------------
      // Otherwise there are multiple items selected and they are a mix of
      // multiple kinds. Returns return 'Items'.
      return Drupal.foldershare.utility.getKindPlural(terminology, 'item');
    },

    /*--------------------------------------------------------------------
     *
     * Server form.
     *
     *--------------------------------------------------------------------*/

    /**
     * Sets up a server command.
     *
     * @param env
     *   The environment object.
     * @param command
     *   The id/name of the command.
     * @param parentId
     *   (optional, default = null = current page) The parent entity ID.
     *   If not given, the current page's parent ID is used.
     * @param destinationId
     *   (optional, default = null = none) The destination entity ID.
     *   If not given, the value is left empty.
     * @param selectionIdList
     *   (optional, default = null = none) The list of selection IDs.
     *   If not given, the value is left empty.
     * @param fileList
     *   (optional, default = null = none) The file list. If not given,
     *   the value is left empty.
     */
    serverCommandSetup: function(
      env,
      command,
      parentId = null,
      destinationId = null,
      selectionIdList = null,
      fileList = null) {

      env.gather.$commandForm[0].reset();

      env.gather.$commandInput.val(command);

      if (parentId === null) {
        env.gather.$parentIdInput.val(env.settings.foldershare.page.id);
      }
      else {
        env.gather.$parentIdInput.val(parentId);
      }

      if (destinationId !== null) {
        env.gather.$destinationIdInput.val(destinationId);
      }

      if (selectionIdList !== null) {
        env.gather.$selectionIdInput.val(JSON.stringify(selectionIdList));
      }

      if (fileList !== null) {
        // Setting the file list triggers a behavior which does
        // a form submit.
        env.gather.$uploadInput[0].files = fileList;
        this.serverCommandSubmit(env);
      }
    },

    /**
     * Submits a previously set up server command.
     *
     * @param env
     *   The environment object.
     */
    serverCommandSubmit: function (env) {
      if (env.settings.foldershare.ajaxEnabled === true) {
        env.gather.$commandSubmitButton.submit();
      }
      else {
        env.gather.$commandForm.submit();
      }
    },


    /*--------------------------------------------------------------------
     *
     * Table.
     *
     * These functions manage the table of files and folders.
     *
     * Table management adds a behavior to every row, and responds to that
     * behavior to select or unselect rows based upon user mouse or touch
     * events. A row is selected if it (1) has the 'selected' class and
     * (2) has its hidden checkbox checked.
     *
     *--------------------------------------------------------------------*/

    /**
     * Attaches behaviors to the table.
     *
     * This method sets attaches row behaviors to support row selection
     * using mouse and touch events, row drag-and-drop, and file drag-and-drop.
     * Some or all of these features may be disabled based upon permissions
     * on the current folder (if any), subfolders (if any), and for copy,
     * move, and file uploads. Drag-and-drop features also may be disabled
     * if copy, move, and file upload commands are not available.
     *
     * @param env
     *   The environment object.
     */
    tableAttachBehaviors: function (env) {
      var $table     = env.gather.$table;
      var $tbody     = env.gather.$tbody;
      var thisScript = Drupal.foldershare.UIFolderTableMenu;

      //
      // Context menu right-click
      // ------------------------
      // Attach a row behavior to present the context menu. Typically this
      // event is generated by a right-click, but it also may be presented
      // by a special context menu keyboard key.
      var $contextMenu = $('.foldershare-folder-table-contextmenu', env.gather.$subform);
      $('tr', $tbody).off('contextmenu.foldershare');
      $('tr', $tbody).on(
        'contextmenu.foldershare',
        function (ev) {
          if ($contextMenu.menu().is(":visible")) {
            // When the menu is already visible, hide it.
            $contextMenu.menu().hide();
          }
          else {
            // If the current row is NOT selected, select it (clearing any
            // prior selection). Otherwise get the current selection.
            var $thisTr = $(this);
            if ($thisTr.hasClass("selected") === false) {
              // Not selected. Select it now.
              thisScript.tableSelectRow($thisTr, env);
            }

            // Update the menu's text based on the selection.
            thisScript.menuUpdate(env, $contextMenu);

            // Position the menu and show it.
            $contextMenu.show().position({
              my:        "left top",
              at:        "left bottom",
              of:        ev,
              collision: "fit"
            });

            // Register a handler to catch an off-menu click to hide it.
            $(document).on('click.foldershare', function (ev) {
              $contextMenu.menu().hide();
            } );
          }

          return false;
        });

      //
      // Add open behavior
      // -----------------
      // For each body row, add a double-click behavior that opens the
      // view page of the row's entity.
      $('tr', $tbody).off('dblclick.foldershare');
      $('tr', $tbody).on(
        'dblclick.foldershare',
        function (ev) {
          $('td.' + env.gather.nameColumn + ' a', $(this))[0].click();
        });

      //
      // Add selection behavior
      // ----------------------
      // For each body row, add behaviors that respond to mouse clicks and
      // touch screen touches.
      $('tr', $tbody).once('row-click').on(
        'click.foldershare',
        function (ev) {
          thisScript.tableClickSelect.call(this, ev, env);
        });

      $('tr', $tbody).once('row-touch').on(
        'touchend.foldershare',
        function (ev) {
          thisScript.tableTouchSelect.call(this, ev, env);
        });

      //
      // Prepare for drag behaviors
      // --------------------------
      // If copy, move, and/or file upload commands are enabled for this page,
      // then prepare for drag operations.
      if (env.dndCopyEnabled === true ||
          env.dndMoveEnabled === true) {
        // Mark all rows as draggable for copy and/or move.
        $('tr', $tbody).attr('draggable', 'true');
      }

      if (env.dndCopyEnabled === true ||
          env.dndMoveEnabled === true ||
          env.dndUploadEnabled === true) {
        // Initialize drag-related attributes.
        $table.attr(thisScript.tableDragOperand, 'none');
        $table.attr(thisScript.tableDragEffectAllowed, 'none');
        $table.attr(thisScript.tableDragRowIndex, 'NaN');
      }

      //
      // Add row drag start/end behaviors
      // --------------------------------
      // When copy and/or move drag-and-drop are enabled, rows may be
      // dragged into subfolders.
      //
      // - A "dragstart" event is sent when the user first starts dragging
      //   a row. The event indicates the current row.
      //
      // - A "dragend" event is sent after the user drops the dragged rows,
      //   or canceles the drag (such as with the ESC key). The event
      //   indicates the row the drag started on.
      if (env.dndCopyEnabled === true ||
          env.dndMoveEnabled === true) {
        // Respond to drag starts. The response uses either the current row,
        // or the entire current selection, then builds a ghost image to
        // drag and initializes the data transfer and table attributes to
        // track the drag.
        $('tr', $tbody).off('dragstart.foldershare');
        $('tr', $tbody).on(
          'dragstart.foldershare',
          function (ev) {
            thisScript.tableRowDragStart.call(this, ev, env);
          });

        // Respond to drag ends.
        $('tr', $tbody).off('dragend.foldershare');
        $('tr', $tbody).on(
          'dragend.foldershare',
          function (ev) {
            thisScript.tableRowDragEnd.call(this, ev, env);
          });
      }

      //
      // Add file and row over/leave/drop behaviors
      // ------------------------------------------
      // When copy and/or move are enabled, rows may be dragged and dropped
      // onto folders. When file uploads are enabled, files may be dragged
      // from off browser and dropped onto the table (if the table is for
      // a folder) or onto folders in the table.
      //
      // The "dragenter", "dragover", and "dragleave" events available to
      // track the drag are problematic:
      //
      // - For row drags, a prior "dragstart" event lets us set up the data
      //   transfer. But for file drags, there is no start event. Instead,
      //   we have to watch for the first "dragenter" or "dragover" event.
      //
      // - For row drags, a "dragend" event lets us know when the drag is
      //   over, either after a drop or if the user cancels the drag. But
      //   for file drags, a drop sends the "drop" event, but a cancel does
      //   not send a unique end event. All it sends is a final "dragleave".
      //
      // - "dragover" events are generated continuously as the drag moves,
      //   and even when the drag stops but hasn't dropped yet. The event
      //   rate is browser-specific. To keep the event's response quick,
      //   we need to do as little as possible during a "dragover".
      //
      // - "dragenter" and "dragleave" are generated every time the drag
      //   crosses any element boundary. This is not just crossing from one
      //   row to the next. It also includes cross into and out of every
      //   element within a row, including <td>, <div>, <a>, etc. Since we
      //   do not know the exact structure of rows (that's up to the views
      //   module configuration), we don't know how many enter/leave events
      //   will occur during a drag. In practice, there are LOTS.
      //
      // Highlighting is needed to show drop targets (folder rows and
      // the entire table during a file drag). In principal, row highlighting
      // could highlight on a "dragenter" and unhighlight on a "dragleave".
      // But since these two events are generated in abundance as the cursor
      // moves across elements within a row, we need to track when these
      // events occur because of a ROW boundary crossing. This is done by
      // saving the current row and noting when it changes.
      //
      // Even with row tracking, table styling affects the order of enter
      // and leave events:
      // - If a table has no row spacing (which is typical), then "dragenter"
      //   for the new row occurs BEFORE "dragleave" for the old row.
      //
      // - If there is row spacing (which defaulting HTML styling does), then
      //   "dragenter" for the new row occurs AFTER "dragleave" for the old
      //   row.
      //
      // This means we cannot reliably use "dragenter" to highlight and
      // "dragleave" to unhighlight because the event order may change based
      // on the theme.
      //
      // Since a "dragleave" on a canceled file drag is the only way to
      // unhighlight and clean up, we must clean up everything on a file
      // "dragleave" even if there might be a "dragenter" or "dragover" event
      // coming next. Those events can restart the file drag on a new row,
      // but this will cause styling flicker. There's nothing we can do.
      //
      // But for row drags, we can avoid styling flicker by doing all
      // highlight changes in "dragenter" and use "dragend" to clean up.
      //
      // Because of the asymmetric event characteristics for row drags and
      // file drags, we need separate responses:
      //
      // - For row drags:
      //   - "dragstart" starts a drag and sets up the data transfer.
      //   - "dragenter" updates row highlighting.
      //   - "dragover" updates the drag effect based on the current row kind.
      //   - "dragleave" does nothing.
      //   - "drop" drops the rows.
      //   - "dragend" cleans up after a row "drop" or cancel.
      //
      // - For file drags:
      //   - "dragenter" does nothing.
      //   - "dragover" starts a file drag and highlights the row.
      //   - "drop" drops the files.
      //   - "dragleave" cleans up as if the file drag was canceled.
      if (env.dndCopyEnabled === true ||
          env.dndMoveEnabled === true ||
          env.dndUploadEnabled === true) {
        $('tr', $tbody).off('dragenter.foldershare');
        $('tr', $tbody).on(
          'dragenter.foldershare',
          function (ev) {
            thisScript.tableRowDragEnter.call(this, ev, env);
          });

        $('tr', $tbody).off('dragover.foldershare');
        $('tr', $tbody).on(
          'dragover.foldershare',
          function (ev) {
            thisScript.tableRowDragOver.call(this, ev, env);
          });

        $('tr', $tbody).off('dragleave.foldershare');
        $('tr', $tbody).on(
          'dragleave.foldershare',
          function (ev) {
            thisScript.tableRowDragLeave.call(this, ev, env);
          });

        $('tr', $tbody).off('drop.foldershare');
        $('tr', $tbody).on(
          'drop.foldershare',
          function (ev) {
            thisScript.tableRowDragDrop.call(this, ev, env);
          });
      }
    },

    /**
     * Handles a touch selection event on a table row.
     *
     * Touch selection toggles the selected item on/off. It ignores keyboard
     * modifiers and therefore does not support range selection.
     *
     * @param ev
     *   The row event to handle.
     * @param env
     *   The environment object.
     */
    tableTouchSelect: function (ev, env) {
      var $tr    = $(this);
      var $table = env.gather.$table;
      var $tbody = env.gather.$tbody;

      // If the touched row does not have a name column, then ignore.
      // This can happen for an empty table with a generic empty message
      // and no name column.
      var $tdName = $('td.' + env.gather.nameColumn, $tr);
      if ($tdName.length === 0) {
        return;
      }

      // For out of range row indexes (<1), clear the selection.
      // Otherwise toggle the row selection.
      if (this.rowIndex <= 0) {
        // Header/footer click. Clear the selection.
        $('tr', $tbody).each( function() {
          $(this).toggleClass('selected', false);
        });
        $table.attr('selectionFirstRowIndex', '');
        $table.attr('selectionLastRowIndex', '');
      }
      else {
        var newState = !$tr.hasClass('selected');
        $tr.toggleClass('selected', newState);
        if (newState === false) {
          $table.attr('selectionFirstRowIndex', '');
          $table.attr('selectionLastRowIndex', '');
        }
        else {
          $table.attr('selectionFirstRowIndex', this.rowIndex);
          $table.attr('selectionLastRowIndex', this.rowIndex);
        }
      }

      // Some browsers will also send mouse events after a touch event.
      // Such a "ghost click" is not useful here, so disable it.
      ev.preventDefault();
    },

    /**
     * Handles a mouse click selection event on a table row.
     *
     * Mouse selection supports range selection using keyboard modifiers
     * like shift-click, control-click (on Windows or Linux), or
     * command-click (on a Mac):
     *
     * - For all platforms, if there are no keyboard modifiers, then a mouse
     *   click clears any previous selection and starts a new one.
     *
     * - For all platforms, if the shift key is down during a click, a selection
     *   is extended from the most recent selection to the clicked on row.
     *
     * - For Windows and Linux platforms, if the control key is down during a
     *   click, a selected row is toggled.
     *
     * - For Mac platforms, if the command (meta) key is down during a
     *   click, a selected row is toggled.
     *
     * @param ev
     *   The row event to handle.
     * @param env
     *   The environment object.
     */
    tableClickSelect: function (ev, env) {
      var $tr    = $(this);
      var $table = env.gather.$table;
      var $tbody = env.gather.$tbody;
      var first  = $table.attr("selectionFirstRowIndex");
      var last   = $table.attr("selectionLastRowIndex");

      // If the clicked-on row does not have a name column, then ignore.
      // This can happen for an empty table with a generic empty message
      // and no name column.
      var $tdName = $('td.' + env.gather.nameColumn, $tr);
      if ($tdName.length === 0) {
        return;
      }

      var isMac = (navigator.appVersion.indexOf("Mac") != -1);

      // Check for keyboard modifiers and mimic Windows/Linux/Mac behavior.
      // If more than one modifier is held down, the control/command
      // modifier has a higher priority than the shift modifier.
      if ((isMac === true && ev.metaKey === true) ||
          (isMac === false && ev.ctrlKey === true)) {
        // Control/Command-click
        // ---------------------
        // On a Mac, a command-click toggles the selection state of the
        // clicked-on row.
        //
        // On all other platforms (e.g. Windows and Linux), a control-click
        // toggles the selection state of the clicked-on row.
        var newState = !$tr.hasClass('selected');
        $tr.toggleClass('selected', newState);

        if (newState === true) {
          // A clicked-on row always resets the range to that row.
          $table.attr('selectionFirstRowIndex', this.rowIndex);
          $table.attr('selectionLastRowIndex', this.rowIndex);
        }
        else {
          first = Number(first);
          last = Number(last);

          if (this.rowIndex === first && this.rowIndex === last) {
            // The unselected row was the only row in the selection.
            // Empty the range.
            $table.attr('selectionFirstRowIndex', '');
            $table.attr('selectionLastRowIndex', '');
          }
          else if (this.rowIndex === first) {
            // The unselected row was the start of the range. Shorten the
            // range to start on the next row.
            $table.attr('selectionFirstRowIndex', first + 1);
          }
          else if (this.rowIndex === last) {
            // The unselected row was the end of the range. Shorten the
            // range to end on the previous row.
            $table.attr('selectionLastRowIndex', last - 1);
          }
          else {
            // The unselected row was within the range. Shorten the range
            // to include the lower half of the range.
            $table.attr('selectionFirstRowIndex', this.rowIndex + 1);
          }
        }
      }
      else if (ev.shiftKey === true) {
        // Shift-click
        // -----------
        // For all platforms, a shift-click adjusts a current selection.
        //
        // The following combinations could occur:
        //
        // - No current selection. Select the clicked-on row and save the
        //   range as (first = last = clicked-on row).
        //
        // - Current selection and clicked-on row is above it. Flip the
        //   range by clearing the entire selection first, then selecting
        //   rows from the clicked-on row to through the first item of the
        //   old selection. Save the range as (first = clicked-on row) and
        //   (last = old first).
        //
        // - Current selection and clicked-on row is below it. Extend the
        //   selection by selecting rows from (last+1) through the
        //   clicked-on row. Save the range as (first = old first) and
        //   (last = clicked-on row).
        //
        // - Current selection and clicked-on row within the range.
        //   Shorten the range by clearing everything from the row after
        //   the clicked-on row through the last row of the range. Save
        //   the range as (first = old first) and (last = clicked-on row).
        //
        // Note that row indexes are 1-based, but loop/array/element
        // indexes are 0-based.
        if (typeof last === 'undefined' || last === '') {
          // No prior selection. Select from 1st row thru this row.
          $('tr', $tbody).slice(0, this.rowIndex).each( function() {
            $(this).toggleClass('selected', true);
          });

          $table.attr("selectionFirstRowIndex", 1);
          $table.attr("selectionLastRowIndex", this.rowIndex);
        }
        else {
          first = Number(first);
          last = Number(last);

          if (this.rowIndex > last) {
            // Extend selection downwards thru the clicked-on row.
            $('tr', $tbody).slice(last, this.rowIndex).each( function() {
              $(this).toggleClass('selected', true);
            });

            $table.attr("selectionFirstRowIndex", first);
            $table.attr("selectionLastRowIndex", this.rowIndex);
          }
          else if (this.rowIndex < first) {
            // Flip selection upwards thru the clicked-on row. Clear the
            // current selection, except the first row, then add the new rows.
            $('tr', $tbody).slice(first, last+1).each( function() {
              $(this).toggleClass('selected', false);
            });
            $('tr', $tbody).slice(this.rowIndex-1, first).each( function() {
              $(this).toggleClass('selected', true);
            });

            $table.attr("selectionFirstRowIndex", this.rowIndex);
            $table.attr("selectionLastRowIndex", first);
          }
          else {
            // Shorten selection to end on the clicked-on row. Clear all rows
            // after the clicked-on row.
            $('tr', $tbody).slice(this.rowIndex, last).each( function() {
              $(this).toggleClass('selected', false);
            });

            $table.attr("selectionFirstRowIndex", first);
            $table.attr("selectionLastRowIndex", this.rowIndex);
          }
        }
      }
      else {
        // Click
        // -----
        // When there are no keyboard modifiers, clicking on a row clears
        // the previous selection (if any) and selects the row.
        $('tr', $tbody).each( function() {
          $(this).toggleClass('selected', false);
        });

        // For out of range row (<1), clear selection.
        // Otherwise select the clicked-on row and save its index.
        if (this.rowIndex <= 0) {
          $table.attr('selectionFirstRowIndex', '');
          $table.attr('selectionLastRowIndex', '');
        }
        else {
          $tr.toggleClass('selected', true);
          $table.attr('selectionFirstRowIndex', this.rowIndex);
          $table.attr('selectionLastRowIndex', this.rowIndex);
        }
      }

      // A click can sometimes cause a text selection if the mouse
      // moved a little between mouse down and up. Such a text
      // selection is meaningless here, so disable it.
      window.getSelection().removeAllRanges();
    },

    /**
     * Handles a single table row selection as if by a mouse click.
     *
     * Used to select a row based upon some non-mouse event, this method
     * marks a single row as selected, clearing any previous selection.
     *
     * @param $tr
     *   The row to select.
     * @param env
     *   The environment object.
     */
    tableSelectRow: function ($tr, env) {
      var $table = env.gather.$table;
      var $tbody = env.gather.$tbody;

      // Selecting a row clears the previous selection (if any) and
      // selects the row.
      $('tr', $tbody).each( function() {
        $(this).toggleClass('selected', false);
      });

      // For out of range row (<1), clear selection.
      // Otherwise select the clicked-on row and save its index.
      if (this.rowIndex <= 0) {
        $table.attr('selectionFirstRowIndex', '');
        $table.attr('selectionLastRowIndex', '');
      }
      else {
        $tr.toggleClass('selected', true);
        $table.attr('selectionFirstRowIndex', this.rowIndex);
        $table.attr('selectionLastRowIndex', this.rowIndex);
      }

      // A click can sometimes cause a text selection if the mouse
      // moved a little between mouse down and up. Such a text
      // selection is meaningless here, so disable it.
      window.getSelection().removeAllRanges();
    },

    /**
     * Handles the start of an entity row drag.
     *
     * An entity row drag copies or moves one or more table rows, depicting
     * entities, and drops them into a folder or root folder. The drag list
     * created by the drag is a list of entity IDs for the dragged rows:
     *
     * - If the drag starts on a selected item, the entire selection is
     *   added to the drag list in a pending data transfer.
     *
     * - If the drag starts on an unselected item, that single row is
     *   added to the drag list in a pending data transfer.
     *
     * In both cases, a ghost image is created that shows the names
     * of the items being dragged. The data transfer state is initialized
     * and table attributes set to record that a row drag is in progress.
     *
     * @param ev
     *   The row event to handle.
     * @param env
     *   The environment object.
     */
    tableRowDragStart: function (ev, env) {
      var $thisTr    = $(this);
      var $thisTable = env.gather.$table;
      var thisScript = Drupal.foldershare.UIFolderTableMenu;

      //
      // Mark the table
      // --------------
      // Mark the table as having a row drag in progress. This mark is
      // removed when the drag is done and it indicates that row dragging,
      // rather than off-browser file dragging is in progress.
      $thisTable.attr(thisScript.tableDragOperand, 'rows');
      $thisTable.attr(thisScript.tableDragRowIndex, this.rowIndex);

      //
      // Create ghost
      // ------------
      // The drag image during the drag is from a ghost table that contains
      // a clone of the name column (or entire row) for dragged items.
      //
      // While looping over the items to add to the ghost, collect their
      // entity ID's to use as the data transfer data.
      //
      // Note, however, that older browsers may not support setting the drag
      // image. If they don't support it, skip creating the ghost image.
      var dragImageSupported = typeof(ev.originalEvent.dataTransfer.setDragImage) === 'function';
      var $dragTable = null;
      var $dragTbody = null;
      var rowHeight = 0;

      if (dragImageSupported === true) {
        $dragTable = $('<table class="dragImage">');
        $dragTbody = $('<tbody>');
        $dragTable.append($dragTbody);
      }

      var nameColumn = env.gather.nameColumn;

      // Add clones of the current row or all selected rows to the ghost table.
      //
      // Get the height of a dragged table row to use to position the ghost
      // table under the cursor.
      var draggedList = [];

      if ($thisTr.hasClass('selected') === true) {
        // The user has started a drag atop a selected row.
        //
        // Add all selected rows in the table into a list of dragged rows
        // and the ghost table.
        $('tr.selected', $thisTable).each(function() {
          var $td = $('td.' + nameColumn, $(this));

          // Save the row's entity ID.
          var $a = $('a', $td);
          if ($a.length === 0) {
            // Fail. No anchor? Ignore row.
            return false;
          }

          draggedList.push($a.attr('data-foldershare-id'));

          if (dragImageSupported === true) {
            // Clone the column and add it to the ghost table.
            // Remove row and column classes so we don't get any residual
            // styling of the ghost.
            var rh = this.offsetHeight;
            if (rh > rowHeight) {
              rowHeight = rh;
            }

            var $newTd = $td.clone(false).removeClass();
            $dragTbody.append($('<tr>').append($newTd));
          }
        });
      }
      else {
        // The user has started a drag atop an unselected row.
        //
        // Add the single row to the list of dragged rows and the ghost table.
        var $td = $('td.' + nameColumn, $thisTr);

        // Save the row's entity ID.
        var $a = $('a', $td);
        if ($a.length === 0) {
          // Fail. No anchor? Ignore drag start.
          return true;
        }

        draggedList.push($a.attr('data-foldershare-id'));

        if (dragImageSupported === true) {
          // Clone the column or row and add it to the ghost table.
          // Remove row and column classes so we don't get any residual
          // styling of the ghost.
          rowHeight = $thisTr[0].offsetHeight;
          var $td = $('td.' + nameColumn, $thisTr);
          var $newTd = $td.clone(false).removeClass();
          $dragTbody.append($('<tr>').append($newTd));
        }
      }

      //
      // Set up the data transfer
      // ------------------------
      // - Set the transferred data to be a list of entity IDs.
      // - Set the drag image to be the ghost table.
      // - Set the allowed 'effects' (e.g. copy or move).
      // - Set the initial 'effect' (e.g. copy or move).
      ev.originalEvent.dataTransfer.setData(
        'foldershare/local-entity-list',
        JSON.stringify(draggedList));

      var allowed = 'none';
      var effect = 'non';
      if (env.dndCopyEnabled === true && env.dndMoveEnabled === true) {
        allowed = 'copyMove';
        effect = 'move';
      }
      else if (env.dndCopyEnabled === true) {
        allowed = 'copy';
        effect = 'copy';
      }
      else {
        allowed = 'move';
        effect = 'move';
      }

      ev.originalEvent.dataTransfer.effectAllowed = allowed;
      ev.originalEvent.dataTransfer.dropEffect = effect;
      $thisTable.attr(thisScript.tableDragEffectAllowed, allowed);

      if (dragImageSupported === true) {
        // The ghost table must be on the page in order to be rendered
        // and used as the ghost table. So add it temporarily.
        $('body').append($dragTable);

        ev.originalEvent.dataTransfer.setDragImage(
          $dragTable[0],
          0,
          (rowHeight/2));

        // The ghost table must exist in the body long enough for it to be
        // rendered for use as the drag image. But after that it is clutter
        // that will show up at the end of the page. To remove it as soon
        // as possible, we set a timeout function.
        setTimeout(function() { $dragTable.remove(); });
      }

      return true;
    },

    /**
     * Handles the end of an entity row drag.
     *
     * An entity row drag copies or moves one or more table rows, depicting
     * entities, and drops them into a folder or root folder. The start of
     * the drag has already initialized the event's data transfer object
     * to contain a list of dragged entity IDs.
     *
     * A drag can end in one of two ways:
     * - The user dropped the drag.
     * - The user canceled the drag (such as by the ESC key).
     *
     * If the user dropped the drag, the "drop" event behavior has already
     * handled collecting the entity ID list from the data transfer and
     * sending a copy or move command to the server.
     *
     * This method cleans up after either a drop or a cancel by resetting
     * table attributes and unhighlighting whatever row was most recently
     * under the cursor during the drag (if any).
     *
     * @param ev
     *   The row event to handle.
     * @param env
     *   The environment object.
     *
     * @return
     *   Returns false.
     */
    tableRowDragEnd: function (ev, env) {
      var $thisTr     = $(this);
      var $thisTable  = env.gather.$table;
      var thisScript  = Drupal.foldershare.UIFolderTableMenu;
      var oldRowIndex = Number($thisTable.attr(thisScript.tableDragRowIndex));

      //
      // Unhighlight drag row
      // --------------------
      // The row most recently under the cursor during the drag may have
      // been highlighted (if it was a folder). Unhighlight it.
      if (isNaN(oldRowIndex) === false) {
        // Note that the saved row index is from the event, which numbers
        // rows with 1 for the 1st row. jQuery numbers rows with 0 for
        // the 1st row.
        $('tbody tr', $thisTable).eq(oldRowIndex-1).removeClass('foldershare-draghover');
      }

      //
      // Reset state
      // -----------
      // Reset the table attributes saving the drag state.
      $thisTable.attr(thisScript.tableDragOperand, 'none');
      $thisTable.attr(thisScript.tableDragEffectAllowed, 'none');
      $thisTable.attr(thisScript.tableDragRowIndex, 'NaN');

      return false;
    },

    /**
     * Handles entering a region during an entity or file drag.
     *
     * The drag in progress can be either an entity row drag or a file drag
     * that is dragging a file in from off-browser. File drag events are
     * ignored here. Only row drag events are handled.
     *
     * A row drag entry event is generated every time the user's cursor crosses
     * an element boundary and into the element during a drag. Element
     * boundaries include the rows of the table, but also table cells and
     * any elements within them (such as <div> or <a>).
     *
     * If the event notes a cross into a new row, the old row (if any)
     * is unhighlighted and the new row's kind checked. If the new row
     * is for a folder or root folder, it is highlighted.
     *
     * @param ev
     *   The row event to handle.
     * @param env
     *   The environment object.
     *
     * @return
     *   Returns false.
     */
    tableRowDragEnter: function (ev, env) {
      var $thisTr    = $(this);
      var $thisTable = env.gather.$table;
      var thisScript = Drupal.foldershare.UIFolderTableMenu;

      switch ($thisTable.attr(thisScript.tableDragOperand)) {
        default:
        case 'none':
        case 'files':
          break;

        case 'rows':
          // On a row drag, if the event notes a row crossing, update
          // highlighting.
          var oldRowIndex = Number($thisTable.attr(thisScript.tableDragRowIndex));
          var newRowIndex = this.rowIndex;

          if (newRowIndex !== oldRowIndex) {
            if (isNaN(oldRowIndex) === false) {
              // The saved row index is from the event, which numbers
              // rows with 1 for the 1st row. jQuery numbers rows with
              // 0 for the 1st row.
              $('tbody tr', $thisTable).eq(oldRowIndex-1).removeClass('foldershare-draghover');
            }

            // Save the new row index.
            $thisTable.attr(thisScript.tableDragRowIndex, newRowIndex);

            // If the current row is a folder, highlight it as an acceptable
            // drop target. Update the allowed drag effect.
            var $a = $('td.' + env.gather.nameColumn + ' a', $thisTr);
            var rowKind = $a.attr('data-foldershare-kind');
            var allowed = 'none';
            var effect = 'none';

            if (rowKind === 'folder' || rowKind === 'rootfolder') {
              $thisTr.addClass('foldershare-draghover');

              if (env.dndCopyEnabled === true && env.dndMoveEnabled === true) {
                allowed = 'copyMove';
                effect = 'move';
              }
              else if (env.dndCopyEnabled === true) {
                allowed = 'copy';
                effect = 'copy';
              }
              else if (env.dndMoveEnabled === true) {
                allowed = 'move';
                effect = 'move';
              }
            }

            $thisTable.attr(thisScript.tableDragEffectAllowed, allowed);
            ev.originalEvent.dataTransfer.effectAllowed = allowed;
            ev.originalEvent.dataTransfer.dropEffect = effect;
          }
          break;
      }

      return false;
    },

    /**
     * Handles the continuation of an entity or file drag.
     *
     * The drag in progress can be either an entity row drag or a file drag
     * that is dragging a file in from off-browser. It is important
     * to keep this behavior as fast as possible because browsers generate
     * a large number of these events, whether the user's cursor is moving
     * or not.
     *
     * For entity row drags, most processing is done on a "dragenter". This
     * method merely reaffirms the current drag effect (e.g. copy or move).
     *
     * For file drags, most processing is done on a "dragleave". But on the
     * first "dragover" event during a file drag, intial setup is done.
     * Thereafter this method merely reaffirms the current drag effect.
     *
     * @param ev
     *   The row event to handle.
     * @param env
     *   The environment object.
     *
     * @return
     *   Returns false and prevents further event processing.
     */
    tableRowDragOver: function (ev, env) {
      var $thisTr    = $(this);
      var $thisTable = env.gather.$table;
      var thisScript = Drupal.foldershare.UIFolderTableMenu;

      switch ($thisTable.attr(thisScript.tableDragOperand)) {
        default:
        case 'rows':
        case 'files':
          break;

        case 'none':
          // Since the drag operand is still 'none', this must be the first
          // drag event for a file drag from off-browser.
          //
          // The event's dataTransfer property exists and can be configured
          // at this point, BUT the list of files being dragged is not yet
          // known. We therefore cannot confirm that the drag is valid yet.
          //
          // We can check if the browser supports dragged files for uploads.
          // This tries to use the empty file list from the dataTransfer and
          // set the input field's files. If this fails, the browser does not
          // support setting the field.
          if (thisScript.checkFileDragSupport(ev, env) === false) {
            break;
          }

          // Mark the table as having a file drag in progress. File drags
          // are always 'copy' operations.
          $thisTable.attr(thisScript.tableDragOperand, 'files');
          $thisTable.attr(thisScript.tableDragEffectAllowed, 'copy');
          $thisTable.attr(thisScript.tableDragRowIndex, this.rowIndex);

          ev.originalEvent.dataTransfer.dropEffect = 'copy';
          ev.originalEvent.dataTransfer.effectAllowed = 'copy';

          // Highlight the current row if it is a folder.
          var $a = $('td.' + env.gather.nameColumn + ' a', $thisTr);
          var rowKind = $a.attr('data-foldershare-kind');
          if (rowKind === 'folder' || rowKind === 'rootfolder') {
            $thisTr.addClass('foldershare-draghover');
          }
          break;
      }

      ev.preventDefault();
      ev.stopPropagation();
      return false;
    },

    /**
     * Handles leaving a region during an entity or file drag.
     *
     * The drag in progress can be either an entity row drag or a file drag
     * that is dragging a file in from off-browser.
     *
     * For entity row drags, most processing is done on a "dragenter". This
     * method does nothing.
     *
     * For file drags, most processing is done here. With a file drag, there
     * is no unique ending event if the drag is canceled. All we get is a
     * final "dragleave" and we cannot determine if the event is from a
     * canceled drag or just a "dragleave" as the user's cursor is moving
     * across an element boundary during a drag. This method is forced to
     * assume the drag has been canceled and clean up for it since there will
     * be no other opportunity to do so.
     *
     * @param ev
     *   The row event to handle.
     * @param env
     *   The environment object.
     */
    tableRowDragLeave: function (ev, env) {
      var $thisTr    = $(this);
      var $thisTable = env.gather.$table;
      var thisScript = Drupal.foldershare.UIFolderTableMenu;

      switch ($thisTable.attr(thisScript.tableDragOperand)) {
        default:
        case 'rows':
        case 'none':
          break;

        case 'files':
          // Assume this is the last event of a canceled file drag, since
          // we can't tell otherwise. End the file drag.
          $thisTr.removeClass('foldershare-draghover');
          $thisTable.attr(thisScript.tableDragOperand, 'none');
          $thisTable.attr(thisScript.tableDragEffectAllowed, 'none');
          $thisTable.attr(thisScript.tableDragRowIndex, 'NaN');
          break;
      }

      return false;
    },

    /**
     * Handles a drop of an entity drag.
     *
     * The drop triggers a move or copy of the dragged entities into a
     * subfolder.
     *
     * @param ev
     *   The row event to handle.
     * @param env
     *   The environment object.
     *
     * @return
     *   Returns false and prevents further event processing.
     */
    tableRowDragDrop: function (ev, env) {
      var $thisTr    = $(this);
      var $thisTable = env.gather.$table;
      var thisScript = Drupal.foldershare.UIFolderTableMenu;

      // Get the entity ID under the drop row.
      var $a = $('td.' + env.gather.nameColumn + ' a', $thisTr);
      var rowKind = $a.attr('data-foldershare-kind');
      var dropEntityId = $a.attr('data-foldershare-id');

      // The data transfer's "dropEffect" is handled differently by
      // different browsers:
      //
      // - Microsoft Edge and Mozilla Firefox set "dropEffect" to
      //   "copy" or "move" when earlier "dragenter" or "dragover"
      //   behaviors have constained the allowed effect to "copyMove".
      //
      // - Apple Safari sets "dropEffect" to "none" and "effectAllowed" to
      //   "all", "copy", or "move" when we constrain the allowed effect to
      //   "copyMove".
      switch ($thisTable.attr(thisScript.tableDragOperand)) {
        default:
        case 'none':
          break;

        case 'rows':
          // Drop entity rows.
          //
          // Make sure the drop row is a folder.
          if (rowKind !== 'folder' && rowKind !== 'rootfolder') {
            // User error. Cannot drop onto a non-folder.
            break;
          }

          // Get the drag's list of entity IDs and make sure the drop row's
          // entity ID is not in the list.
          var entityIdList = JSON.parse(ev.originalEvent.dataTransfer.getData(
              'foldershare/local-entity-list'));
          if ($.inArray(dropEntityId, entityIdList) !== (-1)) {
            // User error. Cannot drop onto self.
            break;
          }

          // Determine if the operation is a copy or move.
          var effect = ev.originalEvent.dataTransfer.dropEffect;
          var command = null;
          if (effect === 'none') {
            switch (ev.originalEvent.dataTransfer.effectAllowed) {
              case 'copyMove':
              case 'linkMove':
              case 'move':
              case 'all':
                effect = 'move';
                break;

              case 'copyLink':
              case 'copy':
                effect = 'copy';
                break;
            }

            if (effect === 'none') {
              // Still none. Cannot figure out effect.
              break;
            }
          }

          switch (effect) {
            case 'move':
              command = thisScript.moveCommand;
              break;
            case 'copy':
              command = thisScript.copyCommand;
              break;
          }

          // Issue the copy or move command.
          thisScript.serverCommandSetup(
            env,
            command,
            null,
            dropEntityId,
            entityIdList,
            null
          );
          thisScript.serverCommandSubmit(env);
          break;

        case 'files':
          // Drop files.
          //
          // Clean up at the end of a file drag.
          $thisTr.removeClass('foldershare-draghover');
          $thisTable.attr(thisScript.tableDragOperand, 'none');
          $thisTable.attr(thisScript.tableDragEffectAllowed, 'none');
          $thisTable.attr(thisScript.tableDragRowIndex, 'NaN');

          // If the drop row is not a folder or root folder, use the
          // current page's folder, if any.
          if (rowKind !== 'folder' && rowKind !== 'rootfolder') {
            dropEntityId = env.settings.foldershare.page.id;
            if (dropEntityId === (-1)) {
              // There is no current page entity. Cannot drop here.
              break;
            }
          }

          thisScript.checkFileDragValid(ev, env,
            function (ev, env, fileList) {
              // Issue the upload command.
              thisScript.serverCommandSetup(
                env,
                thisScript.uploadCommand,
                dropEntityId,
                null,
                null,
                fileList);
              thisScript.serverCommandSubmit(env);
            },
            function (ev, env, fileList) {
              // Tell the user the drag was not valid.
              var text = '<div>';
              if (fileList.length <= 1) {
                var translated = env.settings.foldershare.terminology.text.upload_dnd_invalid_singular;
                if (typeof translated === 'undefined') {
                  text += '<p><strong>Drag-and-drop item cannot be uploaded.</strong></p>';
                  text += '<p>You may not have access to the item, or it may be a folder. Folder upload is not supported.</p>';
                }
                else {
                  text += translated;
                }
              }
              else {
                var translated = env.settings.foldershare.terminology.text.upload_dnd_invalid_plural;
                if (typeof translated === 'undefined') {
                  text += '<p><strong>Drag-and-drop items cannot be uploaded.</strong></p>';
                  text += '<p>You may not have access to these items, or one of them may be a folder. Folder upload is not supported.</p>';
                }
                else {
                  text += translated;
                }
              }
              text += '</div>';

              Drupal.dialog(text, {}).showModal();
            });
          break;
      }

      ev.preventDefault();
      ev.stopPropagation();
      return false;
    },

    /**
     * Returns the current table row selection, grouped by entity kind.
     *
     * The view table is scanned for selected rows. The entity ID, kind, and
     * access information for each selected row are extracted and used to
     * bin entities into an object with one property for each kind found.
     * The value of the property is an array containing one object for each
     * entity found of that property's kind. Each of those objects has 'id'
     * and 'access' properties containing the corresponding values for the
     * entity.
     *
     * @param env
     *   The environment object.
     *
     * @return
     *   The returned object contains one property for each entity kind
     *   found. The value for each property is an array of objects that each
     *   contain an entity ID and access grants for that entity.
     */
    tableGetSelectionIdsByKind: function (env) {
      var $table = env.gather.$table;
      var $tbody = env.gather.$tbody;
      var result = { };

      $('tr.selected td.' + env.gather.nameColumn + ' a', $tbody).each(
        function (index, element) {
          // Get the entity ID, kind, and access for the entity on the row.
          // If any of these is missing, the row is malformed and ignored.
          var entityId = $(this).attr('data-foldershare-id');
          var kind     = $(this).attr('data-foldershare-kind');
          var access   = $(this).attr('data-foldershare-access');

          if (typeof entityId === 'undefined' ||
            typeof kind       === 'undefined' ||
            typeof access     === 'undefined' ) {
            // Fail. Something is missing. Ignore the row.
            return true;
          }

          // Decode the kind and access, and parse the access into JSON.
          // If this fails, the row is malformed and ignored.
          try {
            access = access.split(',');
          }
          catch (err) {
            // Fail. Parse error. Ignore the row.
            return true;
          }

          // Add this row into the selection. Use the row kind to group
          // rows, and save the entity ID and access array.
          if (typeof result[kind] === 'undefined') {
            result[kind] = [];
          }

          result[kind].push({
            "id":     entityId,
            "access": access
          });

          return true;
        });

      return result;
    },

    /**
     * Returns the current table row selection as an array of entity IDs.
     *
     * The view table is scanned for selected rows. The entity ID for each
     * selected row is added to an array and the array returned.
     *
     * @param env
     *   the environment object.
     *
     * @return
     *   Returns an array of entity IDs for selected rows.
     */
    tableGetSelectionIds: function (env) {
      var $table = env.gather.$table;
      var $tbody = env.gather.$tbody;
      var result = [];

      $('tr.selected td.' + env.gather.nameColumn + ' a', $tbody).each(
        function (index, element) {
          var entityId = $(this).attr('data-foldershare-id');
          if (typeof entityId === 'undefined') {
            // Fail. ID is missing. Ignore the row.
            return true;
          }
          result.push(entityId);
          return true;
        });

      return result;
    },

    /*--------------------------------------------------------------------
     *
     * Validate.
     *
     *--------------------------------------------------------------------*/

    /**
     * Validates that the selection meets a command's constraints.
     *
     * Each command has selection constraints that limit the command to
     * apply only to files, folders, or root folders, or some combination
     * of these.
     *
     * This mimics similar checking on the server and is used to disable
     * command menu items that cannot be chosen in the current context.
     *
     * @param env
     *   The environment object.
     * @param nSelected
     *   The number of items selected.
     * @param selection
     *   An array of selected entity kinds, each with an array of entity Ids.
     * @param commandId
     *   A ID of the command to check for use with the selection.
     *
     * @return
     *   Returns true if the command's selection constraints are met in this
     *   context, and false otherwise.
     */
    checkSelectionConstraints: function (env, nSelected, selection, commandId) {
      //
      // Setup
      // -----
      // Get the command's selection constraints.
      var constraints = env.settings.foldershare.commands[commandId].selectionConstraints;

      //
      // Check selection size
      // --------------------
      // Insure the selection size is compatible with the command.
      //
      // If the command does not use a selection, then we can skip all of this.
      var types = constraints['types'];

      if ($.inArray('none', types) !== (-1)) {
        // When the command does not use a selection, it should only be
        // enabled when there is no selection.
        if (nSelected === 0) {
          return true;
        }
        return false;
      }
      var selectionIsPageEntity = false;

      // The command uses a selection, so check it.
      if (nSelected === 0) {
        // There is no selection.
        //
        // Does the command support defaulting to operating on the page entity,
        // if there is one?
        var pageEntityId = env.settings.foldershare.page.id;
        if (pageEntityId !== (-1) && $.inArray('parent', types) !== (-1)) {
          // There is a page entity, and this command accepts defaulting the
          // selection to the parent. So create a fake selection for the
          // remainder of this function's checking.
          var pageKind = env.settings.foldershare.page.kind;
          var pageAccess = env.settings.foldershare.user.pageAccess;
          selection[pageKind] = {
            "id":     pageEntityId,
            "access": pageAccess,
          };
          nSelected = 1;
          selectionIsPageEntity = true;
        }
        else {
          // The command does not default to the page entity when there is no
          // selection, and we already checked that the command does not
          // work when there is no selection.
          return false;
        }
      }
      else if (nSelected === 1) {
        // There is a single item selected
        if ($.inArray('one',  types) === (-1)) {
          // But the command does not support having just one item.
          return false;
        }
      }
      else {
        // There are multiple items selected.
        if ($.inArray('many', types) === (-1)) {
          // But the command does not support having multiple items.
          return false;
        }
      }

      //
      // Check selection kinds
      // ---------------------
      // Insure the kinds of items in the selection are compatible with
      // the command.
      var kinds = constraints['kinds'];

      if ($.inArray('any', kinds) === (-1)) {
        // The command has specific kind requirements since it did not use
        // the catch-all 'any'. Loop through the selection and make sure
        // every kind is supported.
        for (var kind in selection) {
          if ($.inArray(kind, kinds) === (-1)) {
            // Kind not supported by this command.
            if (selectionIsPageEntity === true) {
              selection = {};
            }
            return false;
          }
        }
      }

      //
      // Check selection access
      // ----------------------
      // Insure each selected item allows the command's access.
      var access = constraints['access'];

      if (access !== 'none') {
        // The command has specific access requirements. Loop through
        // the selection and make sure each selected item grants the
        // required access.
        for (var kind in selection) {
          var items = selection[kind];
          for (var i = 0, len = items.length; i < len; ++i) {
            if ($.inArray(access, items[i]['access']) === (-1)) {
              if (selectionIsPageEntity === true) {
                selection = {};
              }
              return false;
            }
          }
        }
      }

      if (selectionIsPageEntity === true) {
        selection = {};
      }

      return true;
    },

  };

  /*--------------------------------------------------------------------
   *
   * On Drupal ready behaviors.
   *
   * Set up behaviors to execute when the page is fully loaded, or whenever
   * AJAX sends a new page fragment.
   *
   *--------------------------------------------------------------------*/

  Drupal.behaviors.foldershare_UIFolderTableMenu = {
    attach: function (pageContext, settings) {
      Drupal.foldershare.UIFolderTableMenu.attach(pageContext, settings);
    }
  };

})(jQuery, Drupal, drupalSettings);
