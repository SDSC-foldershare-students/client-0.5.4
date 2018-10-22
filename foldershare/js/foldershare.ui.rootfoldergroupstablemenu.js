/**
 * @file
 * Implements the FolderShare root folder groups table menu user interface.
 *
 * The root folder groups table shows a list of well-known root folder
 * groups (e.g. "personal", "public", etc.). Each group looks like a folder.
 * To keep the UI consistent between this table and pages showing a list of
 * root folders, or the contents of a folder, this UI creates a menu button
 * and pull-down menu that lists commands that operate upon root folder groups.
 * That command list is very abbreviated... it only includes "Open...".
 *
 * The "Open..." command requires a selection of a single root folder group
 * to open, so this script supports selecting single rows in the table.
 * Double-clicking a row opens the row's group by advancing to its page.
 * Right-clicking on a row shows a context menu with "Open...".
 *
 * Unlike folder tables, this table does not support:
 * - Multi-row selection.
 * - Drag-and-drop of rows.
 * - Drag-and-drop of files.
 * - A file dialog for uploading.
 *
 * This script requires that each row have a name column and that that
 * column's value in each row includes an anchor tag with a URL that leads
 * to the group's page. This script simply triggers loading of that URL.
 *
 * @ingroup foldershare
 * @see \Drupal\foldershare\Form\UIRootFolderGroupsTableMenu
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
      "%cfoldershare.ui.rootfoldergroupstablemenu.js requires that foldershare.ui.utility.js be included first.",
      'font-weight: bold',
      'font-weight: normal',
      'padding-left: 2em',
      'padding-left: 0');
    window.stop()
  }

  Drupal.foldershare.UIRootFolderGroupsTableMenu = {

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
     * Attaches the module's root folder groups table menu UI behaviors.
     *
     * The root folder groups table menu UI includes
     * - A command menu that only includes "Open...".
     * - A menu button used to present the command menu.
     * - Table row selection.
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
      // <div class="foldershare-toolbar-and-root-folder-groups-table">
      //   <div class="foldershare-toolbar">...</div>
      //   <div class="foldershare-root-folder-groups-table">...</div>
      // </div>
      //
      var topSelector = '.foldershare-toolbar-and-root-folder-groups-table';

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
      var thisObject = this;
      $topElements.each(function (index, element) {
        // Create a new environment object.
        var env = {
          'settings':    settings,
          '$topElement': $(element),
        };

        //
        // Gather configuration
        // --------------------
        // Find the table of root folder groups.
        if (thisObject.gather(env) === false) {
          // Fail. UI elements could not be found.
          return true;
        }

        //
        // Build UI
        // --------
        // Build the UI. This includes building the menu button, main menu,
        // and context menu. This is followed by attaching behaviors.
        if (thisObject.build(env) === false) {
          // Fail. Something didn't work.
          return true;
        }
      });

      return true;
    },

    /**
     * Gathers UI elements from the page.
     *
     * This function searches for the UI's elements and saves them into the
     * environment:
     * - env.gather.$form      = the <form> for the menu button.
     * - env.gather.$table     = the <table> of root folder groups.
     * - env.gather.nameColumn = the group name table column.
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
      var base       = 'foldershare-root-folder-groups-table';
      var nameColumn = 'views-field-name';

      // The page structure contains a toolbar and table wrapper <div>,
      // then two child <div>s containing the toolbar and table:
      //
      // <div class="foldershare-toolbar-and-root-folder-groups-table">
      //   <div class="foldershare-toolbar">...</div>
      //   <div class="foldershare-root-folder-groups-table">...</div>
      // </div>
      //
      // The toolbar <div> contains a form into which to place the menu
      // button. The form is not used for anything else.
      //
      // The table <div> contains a <table> listing root folder groups.
      // One column should be a 'name' column that contains group names
      // and anchors with URLs leading to individual group pages.
      //
      // Find toolbar form
      // -----------------
      // Find the form into which to put the menu button.
      var cls = base + '-menu-form';

      var $form = $('.' + cls, env.$topElement).eq(0);
      if ($form.length === 0) {
        utility.printMalformedError(
          "The required <form> with class '" + cls + "' could not be found.");
        return false;
      }

      //
      // Find table
      // ----------
      // Find the table of root folder groups.
      cls = 'views-table';

      var $table = $('div.' + base + ' table.' + cls, env.$topElement).eq(0);
      if ($table.length === 0) {
        utility.printMalformedError(
          "The required <table> with class '" + cls + "' could not be found.");
        return false;
      }

      //
      // Find name column in table
      // -------------------------
      // Operations to open a root folder group need to know the group's
      // name cell and the anchor within it in order to trigger that anchor.
      var $td = $('td.' + nameColumn, $table);
      if ($td.length === 0) {
        utility.printMalformedError(
          "The required table column with class '" + nameColumn + "' could not be found.");
        return false;
      }

      //
      // Update environment
      // ------------------
      // Save information.
      env['gather'] = {
        '$form':      $form,
        '$table':     $table,
        'nameColumn': nameColumn,
      };

      return true;
    },

    /*--------------------------------------------------------------------
     *
     * Build the root folder groups table menu UI.
     *
     *--------------------------------------------------------------------*/

    /**
     * Builds the UI.
     *
     * The main UI has several features:
     * - A hierarchical main menu that pops up from a menu button.
     * - A context menu that pops up from a right-click on a row.
     * - Selectable rows in the view table.
     *
     * @param env
     *   The environment object.
     *
     * @return
     *   Returns TRUE on success and FALSE otherwise.
     */
    build: function (env) {
      var utility     = Drupal.foldershare.utility;
      var base        = 'foldershare-root-folder-groups-table';
      var terminology = env.settings.foldershare.terminology;

      var menuLabel = utility.getTerm(terminology, 'menu');
      var openLabel = utility.getTerm(terminology, 'open');

      var $form  = env.gather.$form;
      var $table = env.gather.$table;
      var $tbody = $table.find('tbody');
      var nameColumn = env.gather.nameColumn;

      //
      // Create main menu button
      // -----------------------
      // Create the main menu button and append it to the form.
      // If there is a button already there, remove it first.
      var cls = base + '-mainmenu-button';

      $('.' + cls, $form).remove();

      $form.prepend(
        '<button type="button" class="' + cls + '">' +
        '<span>' + menuLabel + '</span>' +
        '</button>');

      var $menuButton = $('.' + cls, $form);
      $menuButton.button().show();

      //
      // Create main menu
      // ----------------
      // Create the main menu HTML and append it to the form.
      // If there is a menu already there, remove it first.
      cls = base + '-mainmenu';

      var menuHtml = '<ul class="hidden ' + cls + '">' +
        '<li data-foldershare-command="open"><div>' +
        openLabel + '</div></li></ul>';

      $('.' + cls, $form).remove();

      $form.append(menuHtml);

      var $menu = $('.' + cls, $form);
      $menu.menu().hide();
      $menu.removeClass('hidden');

      //
      // Create context menu
      // -------------------
      // Create the context menu HTML and append it to the form.
      // If there is a menu already there, remove it first.
      cls = base + '-contextmenu';

      menuHtml = '<ul class="hidden ' + cls + '"><li><div>' +
        openLabel + '</div></li></ul>';

      $('.' + cls, $form).remove();

      $form.append(menuHtml);

      var $contextMenu = $('.' + cls, $form);
      $contextMenu.menu().hide();
      $contextMenu.removeClass('hidden');

      //
      // Attach main menu button behavior
      // --------------------------------
      // When the main menu button is pressed, show the main menu.
      // When the menu is about to be shown, update all menu items to
      // enable/disable based on the selection.
      $menuButton.off('click.foldershare');
      $menuButton.on('click.foldershare', function (ev) {
        if ($menu.menu().is(":visible")) {
          // When the menu is already visible, hide it.
          $menu.menu().hide();
          return false;
        }

        // If there is no selection, disable the menu. Otherwise enable.
        if ($('tr.selected', $tbody).length === 0) {
          $('.ui-menu-item', $menu).addClass('ui-state-disabled').removeClass('ui-state-enabled');
        }
        else {
          $('.ui-menu-item', $menu).addClass('ui-state-enabled').removeClass('ui-state-disabled');

        }

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

        return false;
      });

      //
      // Attach main & context menu item behaviors
      // -----------------------------------------
      // When a menu item is selected, follow the selected row's anchor.
      $menu.off('menuselect.foldershare');
      $menu.on('menuselect.foldershare', function (ev, ui) {
        // Insure the menu is hidden.
        $menu.menu().hide();

        // Get the first selected row. Get the anchor URL and load it.
        var $tr = $('tr.selected', $tbody).eq(0);

        var $a = $('td.' + nameColumn + ' a', $tr);
        $a[0].click();

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

        // Get the first selected row. Get the anchor URL and load it.
        var $tr = $('tr.selected', $table).eq(0);

        var $a = $('td.' + env.gather.nameColumn + ' a', $tr);
        $a[0].click();

        return true;
      });

      //
      // Context menu right-click
      // ------------------------
      // Attach a row behavior to present the context menu. Typically this
      // event is generated by a right-click, but it also may be presented
      // by a special context menu keyboard key.
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
              // Clear the current selection.
              $('tr', $tbody).each( function() {
                $(this).toggleClass('selected', false);
              });

              // If the row index is within the table, select the row.
              if (this.rowIndex > 0) {
                $thisTr.toggleClass('selected', true);
              }

              // A click can sometimes cause a text selection if the mouse
              // moved a little between mouse down and up. Such a text
              // selection is meaningless here, so disable it.
              window.getSelection().removeAllRanges();
            }

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
          var $a = $('td.' + nameColumn + ' a', $(this));
          $a[0].click();
        });

      //
      // Add selection behavior
      // ----------------------
      // For each body row, add behaviors that respond to mouse clicks and
      // touch screen touches.
      //
      // Selection only allows one row to be selected at a time. So on each
      // click/touch, clear the current selection (if any) and then select
      // the clicked/touched row.
      $('tr', $tbody).once('row-click-touch').on(
        'click.foldershare touchend.foldershare',
        function (ev) {
          // Clear the current selection.
          $('tr', $tbody).each( function() {
            $(this).toggleClass('selected', false);
          });

          // If the row index is within the table, select the row.
          if (this.rowIndex > 0) {
            $(this).toggleClass('selected', true);
          }

          // A click can sometimes cause a text selection if the mouse
          // moved a little between mouse down and up. Such a text
          // selection is meaningless here, so disable it.
          window.getSelection().removeAllRanges();
        });
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

  Drupal.behaviors.foldershare_UIRootFolderGroupsTableMenu = {
    attach: function (pageContext, settings) {
      Drupal.foldershare.UIRootFolderGroupsTableMenu.attach(pageContext, settings);
    }
  };

})(jQuery, Drupal, drupalSettings);
