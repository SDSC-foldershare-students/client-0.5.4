/**
 * @file
 * Implements the FolderShare utility functions.
 *
 * The utility functions are shared among multiple UI scripts for the module.
 * They provide string handling and error message printing.
 *
 * @ingroup foldershare
 */
(function($, Drupal, drupalSettings) {

  'use strict';

  // Check pre-requisits.
  //
  // Define Drupal.foldershare if it hasn't been defined yet.
  if ('foldershare' in Drupal === false) {
    Drupal.foldershare = {};
  }

  Drupal.foldershare.utility = {

    /*--------------------------------------------------------------------
     *
     * String utilities.
     *
     *--------------------------------------------------------------------*/

    /**
     * Returns the title-case form of a string.
     *
     * @param string
     *   The string to convert to title-case.
     */
    getTitleCase: function (string) {
      return string.replace(/\w\S*/g, function (txt) {
        return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
      });
    },

    /**
     * Returns the translated singular entity kind name in title-case.
     *
     * The kind name (e.g. "file", "folder", etc.) is looked up in a list
     * of translated kinds provided by the server. The value is converted to
     * singular title-case and returned.
     *
     * @param terminology
     *   A terminology object containing a 'kinds' property that is an array
     *   with kind name keys. Each entry in the array is an object with
     *   'plural' and 'singular' properties that provide the translated
     *   plural and singular forms of the kind name.
     * @param kind
     *   The kind name to look up.
     *
     * @return
     *   The title-case translated singular kind.
     */
    getKindSingular: function (terminology, kind) {
      // Find the translated singular or plural term.
      if ('kinds' in terminology === true &&
          kind in terminology.kinds === true) {
        kind = terminology.kinds[kind].singular;
      }

      return Drupal.foldershare.utility.getTitleCase(kind);
    },

    /**
     * Returns the translated plural entity kind name in title-case.
     *
     * The kind name (e.g. "file", "folder", etc.) is looked up in a list
     * of translated kinds provided by the server. The value is converted to
     * plural title-case and returned.
     *
     * @param terminology
     *   A terminology object containing a 'kinds' property that is an array
     *   with kind name keys. Each entry in the array is an object with
     *   'plural' and 'singular' properties that provide the translated
     *   plural and singular forms of the kind name.
     * @param kind
     *   The kind name to look up.
     *
     * @return
     *   The title-case translated plural kind.
     */
    getKindPlural: function (terminology, kind) {
      // Find the translated singular or plural term.
      if ('kinds' in terminology === true &&
          kind in terminology.kinds === true) {
        kind = terminology.kinds[kind].plural;
      }

      return Drupal.foldershare.utility.getTitleCase(kind);
    },

    /**
     * Returns the translated term in title-case or lower-case.
     *
     * The term is looked up in the list of translated terms provided by
     * the server. The term is converted to title case and returned.
     *
     * @param terminology
     *   A terminology object containing a 'text' property that is an array
     *   with strings as keys, and the translated form of the string as
     *   values.
     * @param term
     *   The term to look up.
     * @param titleCase
     *   (optional, default = true) When true, the returned term uses
     *   title-case. When false, it is entirely lower case.
     *
     * @return
     *   The title case translated term.
     */
    getTerm: function (terminology, term, titleCase = true) {
      // Find the translated singular or plural term.
      if ('text' in terminology === true &&
          term in terminology.text === true) {
        term = terminology.text[term];
      }

      if (titleCase === true) {
        return Drupal.foldershare.utility.getTitleCase(term);
      }

      // Map to lower case.
      term = term.toLowerCase();

      return term;
    },

    /*--------------------------------------------------------------------
     *
     * Print utilities.
     *
     *--------------------------------------------------------------------*/

    /**
     * Prints a malformed page error message to the console.
     *
     * @param body
     *   The body of the message.
     */
    printMalformedError: function (body = '') {
      Drupal.foldershare.utility.printMessage(
        'Malformed page',
        body + ' The user interface cannot be enabled.');
    },

    /**
     * Prints a message to the console.
     *
     * The title is printed in bold, followed by optional body text
     * in a normal weight font, indented below the title.
     *
     * @param title
     *   The short title of the message.
     * @param body
     *   The body of the message.
     */
    printMessage: function (title, body = '') {
      console.log('%cFolderShare: ' +
        title + ':%c' + "\n%c" + body + '%c',
        'font-weight: bold',
        'font-weight: normal',
        'padding-left: 2em',
        'padding-left: 0');
    },

    /**
     * Prints a message to the console, followed by the selection.
     *
     * The title is printed in bold, followed by optional body text
     * in a normal weight font, indented below the title. Below this,
     * the current selection's IDs are listed.
     *
     * @param string title
     *   The short title of the message.
     * @param string body
     *   The body of the message.
     * @param selection
     *   A selection object with one property for each kind for which a
     *   selected item is present. The value for each property is an array
     *   of integer entity IDs of that kind.
     */
    printSelection: function (title, body, selection) {
      Drupal.foldershare.utility.printMessage(title, body);

      if (selection.length === 0) {
        console.log('%cSelection: none%c',
          'padding-left: 2em',
          'padding-left: 0');
        return;
      }

      for (var kind in selection) {
        var n = selection[kind].length;
        var ids = [];
        for (var i = 0; i < n; ++i) {
          var entry = selection[kind][i];
          ids.push(Number(entry['id']));
        }
        console.log('%cSelection of ' + kind + ': ' + ids.toString() + '%c',
          'padding-left: 2em',
          'padding-left: 0');
      }
    },

    /**
     * Prints a message to the console, followed by the file list.
     *
     * The title is printed in bold, followed by optional body text
     * in a normal weight font, indented below the title. Below this,
     * the file list's file names, sizes, and MIME types are listed.
     *
     * @param string title
     *   The short title of the message.
     * @param string body
     *   The body of the message.
     * @param files
     *   The file list.
     */
    printFileList: function (title, body, files) {
      Drupal.foldershare.utility.printMessage(title, body);

      // Output the file list.
      for (var i = 0; i < files.length; ++i) {
        var file = files.item(i);
        console.log('%c' + file.name +
          ' (' + file.size + ' bytes, ' + file.type + ')%c',
          'padding-left: 2em',
          'padding-left: 0');
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
