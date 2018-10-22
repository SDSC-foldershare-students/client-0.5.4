<?php

/**
 * @file
 * Implements hooks to update the module.
 */

use Drupal\foldershare\Constants;
use Drupal\foldershare\Utilities;

/**
 * Update the configurations for views, search, and entity views.
 *
 * These mandatory updates support a substantial overhaul of the user
 * interface that revises the module's views, entity view page, and search
 * indexing. Upon completion, the search index will be cleared and all site
 * caches flushed.
 */
function foldershare_update_8001(&$sandbox) {
  // Restore the search configuration and clear the index.
  if (\Drupal::service('module_handler')->moduleExists('search') === TRUE) {
    Utilities::revertConfiguration('core', 'search.page.foldershare_search');
    search_index_clear(Constants::SEARCH_INDEX);
  }

  // Restore the view configuration.
  Utilities::revertConfiguration('view', Constants::VIEW_LISTS);

  // Restore the entity display configuration.
  Utilities::revertConfiguration('core', 'entity_view_display.foldershare.foldershare.default');

  // Flush all caches.
  drupal_flush_all_caches();
}
