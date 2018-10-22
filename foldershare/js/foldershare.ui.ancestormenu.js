/**
 * @file
 * Implements the FolderShare ancestor menu user interface.
 *
 * The ancestor menu UI presents a menu button and pull-down menu that
 * lists ancestor folders for the current page's file or folder. Selecting
 * a folder from the menu loads that folder's page.
 *
 * The UI is available on:
 * - Root folder groups page.
 * - Root folder group pages.
 * - Folder pages.
 * - File/image/media pages.
 *
 * @ingroup foldershare
 * @see \Drupal\foldershare\Form\UIAncestorMenu
 */
(function($, Drupal, drupalSettings) {

  'use strict';

  // Check pre-requisits.
  //
  // Define Drupal.foldershare if it hasn't been defined yet.
  if ('foldershare' in Drupal === false) {
    Drupal.foldershare = {};
  }

  Drupal.foldershare.ancestormenu = {

    /**
     * Attaches the UI behaviors.
     *
     * The UI form's menu of ancestor folders is configured.
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
    attach: function (pageContext, settings = null) {
      //
      // Test and exit
      // -------------
      // This function is called every time any content is added to the
      // page by AJAX, even if it has nothing to do with navigation. It is
      // therefore important to act quickly and get out of the way if
      // there is nothing to do.
      //
      // Test if the navigate form has been unhidden. If not, this
      // method has already set up the UI and we can exit quickly.
      var uiClass = 'foldershare-ancestormenu';
      var $form = $('.' + uiClass, pageContext);
      if ($form.length === 0) {
        // Form is missing. UI is not installed.
        return true;
      }

      if ($form.hasClass('hidden') === false) {
        // Already unhidden.
        return true;
      }

      $form.removeClass('hidden');

      //
      // Initialize
      // ----------
      // Create the button and menu from the HTML elements.
      var $menuButton = $('.' + uiClass + '-menu-button', $form);
      $menuButton.button();

      var $menu = $('.' + uiClass + '-menu', $form);
      $menu.menu();

      //
      // Add menu button behavior
      // ------------------------
      // When the menu button is pressed, show the ancestor menu.
      $menuButton.off('click.foldershare');
      $menuButton.on('click.foldershare', function (ev) {
        if ($menu.menu().is(":visible")) {
          // When the menu is already visible, hide it.
          $menu.menu().hide();
        }
        else {
          // Show the menu.
          $menu.show().position({
            my:        "left top",
            at:        "left bottom",
            of:        ev.target,
            collision: "fit"
          });

          // Register a one-time handler to catch a off-menu click to hide it.
          $(document).on('click.foldershare', function (ev) {
            $menu.menu().hide();
            $(document).off('click.foldershare');
          } );
        }

        return false;
      });

      //
      // Add ancestor menu item behavior
      // -------------------------------
      // When a menu item is selected, load the associated page. The page's
      // URL is an attribute on the menu item.
      $menu.off('menuselect.foldershare');
      $menu.on('menuselect.foldershare', function (ev, ui) {
        $menu.menu().hide();
        $(document).off('click.foldershare');
        window.location.href = decodeURIComponent(
          $(ui.item).attr('data-foldershare-url'));
        return true;
      });

      $menu.menu().hide();
      $menu.removeClass('hidden');

      $menuButton.button().show();
      $menuButton.removeClass('hidden');

      return true;
    }
  };

  /*--------------------------------------------------------------------
   *
   * On script load behaviors.
   *
   * Execute immediately on script load without waiting for the document
   * "ready" state and all of Drupal's behaviors.
   *
   *--------------------------------------------------------------------*/

  Drupal.foldershare.ancestormenu.attach(document);

  /*--------------------------------------------------------------------
   *
   * On Drupal ready behaviors.
   *
   * Set up behaviors to execute when the page is fully loaded, or whenever
   * AJAX sends a new page fragment.
   *
   *--------------------------------------------------------------------*/

  Drupal.behaviors.foldershare_ancestormenu = {
    attach: function (pageContext, settings) {
      Drupal.foldershare.ancestormenu.attach(pageContext, settings);
    }
  };

})(jQuery, Drupal, drupalSettings);
