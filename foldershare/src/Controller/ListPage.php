<?php

namespace Drupal\foldershare\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Access\AccessManager;
use Drupal\Core\Routing\RouteProvider;

use Drupal\views\Views;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Drupal\foldershare\Constants;
use Drupal\foldershare\Messages;
use Drupal\foldershare\Settings;
use Drupal\foldershare\Form\UIFolderTableMenu;
use Drupal\foldershare\Form\UIRootFolderGroupsTableMenu;
use Drupal\foldershare\Form\UIAncestorMenu;
use Drupal\foldershare\Form\UISearchBox;

/**
 * Creates pages showing a user interface and a list of root folders.
 *
 * <B>Warning:</B> This class is strictly internal to the FolderShare
 * module. The class's existance, name, and content may change from
 * release to release without any promise of backwards compatability.
 *
 * This controller's methods create a page body with a user
 * interface form and an embedded view that shows a list of root folders.
 *
 * The root folders listed depend upon the page method called, and the
 * underlying embedded view:
 *
 * - allPage(): show all root folders, regardless of ownership.
 *
 * - personalPage(): show all root folders owned by the current user.
 *
 * - publicPage(): show all root folders owned by or shared with anonymous.
 *
 * - sharedPage(): show all root folders shared with the current user.
 *
 * The view used is also influenced by the current module settings to
 * enable sharing, and enable sharing with anonymous.
 *
 * @ingroup foldershare
 *
 * @see \Drupal\foldershare\Entity\FolderShare
 * @see \Drupal\foldershare\Form\UIFolderTableMenu
 * @see \Drupal\foldershare\Form\UIAncestorMenu
 * @see \Drupal\foldershare\Form\UISearchBox
 * @see \Drupal\foldershare\Settings
 */
class ListPage extends ControllerBase {

  /*--------------------------------------------------------------------
   *
   * Fields.
   *
   *--------------------------------------------------------------------*/

  /**
   * The current user account, set at construction time.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The route provider, set at construction time.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * The access manager, set at construction time.
   *
   * @var \Drupal\Core\AccessManager
   */
  protected $accessManager;

  /*--------------------------------------------------------------------
   *
   * Construction.
   *
   *--------------------------------------------------------------------*/

  /**
   * Constructs a new page.
   */
  public function __construct(
    RouteProvider $routeProvider,
    AccountInterface $currentUser,
    AccessManager $accessManager) {

    $this->routeProvider = $routeProvider;
    $this->currentUser = $currentUser;
    $this->accessManager = $accessManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('current_user'),
      $container->get('access_manager'));
  }

  /*--------------------------------------------------------------------
   *
   * Virtual folder list page.
   *
   *--------------------------------------------------------------------*/

  /**
   * Builds and returns a page listing all root folder groups.
   *
   * The page contains a table listing each of the well-known root folder
   * groups.
   */
  public function rootFolderGroups() {
    //
    // Search box UI
    // -------------
    // If the UI is enabled, create the search form to include below.
    // This will fail if the Search module is not enabled, the search plugin
    // cannot be found, or if the user does not have search permission.
    $searchBoxForm = NULL;
    if (Constants::ENABLE_UI_SEARCH_BOX === TRUE) {
      $searchBoxForm = \Drupal::formBuilder()->getForm(
        UISearchBox::class,
        (-1));
    }

    //
    // Ancestor menu UI
    // ----------------
    // If the UI is enabled, create the form to include below.
    //
    // Passing a (-2) flags that we are on a groups page.
    $ancestorMenuForm = NULL;
    if (Constants::ENABLE_UI_ANCESTOR_MENU === TRUE) {
      $ancestorMenuForm = \Drupal::formBuilder()->getForm(
        UIAncestorMenu::class,
        (-2));
    }

    //
    // Root folder groups menu UI
    // --------------------------
    // If the UI is enabled, create the UI form to include below.
    //
    // Passing a (-2) flags that we are on a groups page.
    $rootFolderGroupsMenuForm = NULL;
    if (Constants::ENABLE_UI_ROOT_FOLDER_GROUPS_TABLE_MENU === TRUE) {
      $rootFolderGroupsMenuForm = \Drupal::formBuilder()->getForm(
        UIRootFolderGroupsTableMenu::class,
        (-2));
    }

    //
    // Build root folder group table
    // -----------------------------
    // A "root folder group" is a well-known grouping of root folders, such
    // as "personal" root folders owned by the user, "public" root folders
    // available to everyone, and "shared" root folders explicitly shared
    // with the user.
    //
    // Each group has a translated name linked to a page showing the specific
    // root folder group. Each group has a known route and its URL.
    //
    // Collect the names of groups available to this user.  The Constants
    // table lists well-known groups and their routes. The routing table
    // provides human-readable names and descriptions.
    $groups = [];
    foreach (Constants::ROOTFOLDERS_GROUPS as $key => $group) {
      // Get the route and confirm that it exists.
      $routeName = $group['route'];
      $route = $this->routeProvider->getRouteByName($routeName);
      if ($route === NULL) {
        // Fail. Unknown route.
        continue;
      }

      // Check that the current user has permission for the group.
      if ($this->accessManager->checkNamedRoute(
        $routeName,
        [],
        $this->currentUser,
        FALSE) === FALSE) {
        // Fail. Access denied.
        continue;
      }

      // Use the route to get the human-readable name of the group.
      // Translate the name to a string we can use for sorting.
      $routeTitle = $route->getDefault('_title');
      if (empty($routeTitle) === TRUE) {
        $routeTitle = $key;
      }

      $groups[$key] = t($routeTitle)->render();
    }

    // Sort the groups on the translated name. The resulting sort
    // order may be different than the original English name sort order.
    asort($groups);

    // Create the table.
    //
    // Add classes to the table and rows to make the table look like it was
    // generated by Views. This helps it gain styling that matches that of
    // file and folder tables that are, in fact, generated by Views.
    //
    // Add a row for each available group. Use a link for the name
    // and add classes to the name to support an appropriate folder icon.
    $groupTable = [
      '#type'       => 'table',
      '#responsive' => TRUE,
      '#header'     => [
        'name'    => [
          'data'  => t('Name'),
          'class' => [
            'priority-high',
            'views-field',
            'views-field-name',
            'views-align-left',
          ],
        ],
        'description' => [
          'data'  => t('Description'),
          'class' => [
            'priority-low',
            'views-field',
            'views-field-description',
            'views-align-left',
          ],
        ],
      ],
      '#attributes' => [
        'class'     => [
          'views-table',
          'views-view-table',
        ],
      ],
    ];

    foreach ($groups as $key => $translatedTitle) {
      // Get the route again. At this point, the route is already known to
      // exist and the current user has access to the route.
      $group = Constants::ROOTFOLDERS_GROUPS[$key];
      $route = $this->routeProvider->getRouteByName($group['route']);

      // Get the human-readable description for the route.
      $description = $route->getDefault('_description');
      if (empty($description) === TRUE) {
        $description = '';
      }

      $groupTable[] = [
        'name'     => [
          '#type'  => 'link',
          '#wrapper_attributes' => [
            'class' => [
              'priority-high',
              'views-field',
              'views-field-name',
              'views-align-left',
            ],
          ],
          '#title' => $translatedTitle,
          '#url'   => Url::fromRoute(
            $group['route'],
            [],
            [
              'attributes' => [
                'class' => [
                  'file',
                  'file--folder',
                  'file--mime-rootfolder-group-directory',
                ],
              ],
            ]),
        ],

        'description' => [
          '#type'  => 'html_tag',
          '#tag'   => 'span',
          '#value' => t($description),
          '#wrapper_attributes' => [
            'class' => [
              'priority-low',
              'views-field',
              'views-field-description',
              'views-align-left',
            ],
          ],
        ],
      ];
    }

    //
    // Build page
    // ----------
    // Assemble parts of the page, including the UI forms and embedded view.
    //
    // When the main UI is disabled, revert to the base user interface
    // included with the embedded view. Just add the view to the page, and
    // no Javascript-based main UI.
    //
    // When the main UI is enabled, attach Javascript and the main UI
    // form before the view. The form adds hidden fields used by the
    // Javascript.
    $page = [
      '#theme'            => Constants::THEME_VIEW,
      '#attached'         => [
        'library'         => [
          Constants::LIBRARY_MODULE,
        ],
      ],
      '#attributes'       => [
        'class'           => [
          Constants::MODULE . '-view',
          Constants::MODULE . '-view-page',
        ],
      ],

      'toolbar-and-folder-table' => [
        '#type'           => 'container',
        '#attributes'     => [
          'class'         => [
            'foldershare-toolbar-and-root-folder-groups-table',
          ],
        ],

        'toolbar'         => [
          '#type'         => 'container',
          '#attributes'   => [
            'class'       => [
              'foldershare-toolbar',
            ],
          ],

          // Add the root folder groups menu UI.
          'rootfoldergroupstablemenu' => $rootFolderGroupsMenuForm,

          // Add the ancestor menu UI.
          'ancestormenu'  => $ancestorMenuForm,

          // Add the search box UI.
          'searchbox'     => $searchBoxForm,
        ],

        // Add the table of root folder groups. Place the table within a
        // container with classes like those used for a view. Add a footer
        // like that in a view.
        'view'            => [
          '#type'         => 'container',
          '#attributes'   => [
            'class'       => [
              'foldershare-folder-table',
              'foldershare-root-folder-groups-table',
              'view',
              'view-foldershare-lists',
            ],
          ],

          'table'         => $groupTable,

          'footer'        => [
            '#type'       => 'container',
            '#attributes' => [
              'class'     => [
                'foldershare-footer',
                'view-footer',
              ],
            ],
            '#markup'     => t(
              '@count items',
              [
                '@count'  => count($groups),
              ]),
          ],
        ],
      ],
    ];

    if (Constants::ENABLE_UI_ROOT_FOLDER_GROUPS_TABLE_MENU === TRUE) {
      $page['#attached']['library'][] = Constants::LIBRARY_MAINUI;
    }
    else {
      unset($page['foldershare-toolbar-and-folder-table']['foldershare-toolbar']['foldertablemenu']);
    }

    if (Constants::ENABLE_UI_ANCESTOR_MENU === FALSE) {
      unset($page['foldershare-toolbar-and-folder-table']['foldershare-toolbar']['ancestormenu']);
    }

    if (Constants::ENABLE_UI_SEARCH_BOX === FALSE) {
      unset($page['foldershare-toolbar-and-folder-table']['foldershare-toolbar']['searchbox']);
    }

    return $page;
  }

  /*--------------------------------------------------------------------
   *
   * Root folder group pages.
   *
   *--------------------------------------------------------------------*/

  /**
   * Returns a page listing all root folders.
   *
   * The view associated with this page lists all root folders owned by
   * anybody, regardless of share settings.
   *
   * @return array
   *   A renderable array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws an exception if the user is not an administrator.
   */
  public function allRootFolders() {
    return $this->buildPage(
      Constants::VIEW_LISTS,
      Constants::VIEW_LISTS_DISPLAY_ALL);
  }

  /**
   * Returns a page listing root folders owned by or shared with the user.
   *
   * The view associated with this page lists all root folders owned by
   * the current user.
   *
   * @return array
   *   A renderable array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws an exception if the the view display does not allow access.
   *
   * @see ::publicPage()
   * @see ::ownedPage()
   */
  public function personalRootFolders() {
    return $this->buildPage(
      Constants::VIEW_LISTS,
      Constants::VIEW_LISTS_DISPLAY_USER_OWNED);
  }

  /**
   * Returns a page listing root folders owned by/shared with anonymous.
   *
   * When the module's sharing settings are enabled AND sharing with anonymous
   * is enabled, the view associated with this page lists all root folders
   * owned by or shared with anonymous.
   *
   * When the module's sharing settings are disabled for the site or for
   * sharing with anonymous, the view associated with this page lists all
   * root folders owned by anonymous. Root folders marked as shared with
   * anonymous are not listed.
   *
   * @return array
   *   A renderable array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws an exception if the the view display does not allow access.
   */
  public function publicRootFolders() {
    // Authenticated user or anonymous.
    if (Settings::getSharingAllowed() === TRUE &&
        Settings::getSharingAllowedWithAnonymous() === TRUE) {
      // Sharing enabled. Show content owned by or shared with user.
      $displayName = Constants::VIEW_LISTS_DISPLAY_ANONYMOUS_OWNED_OR_SHARED;
    }
    else {
      // Sharing disabled. Show content owned by anonymous.
      $displayName = Constants::VIEW_LISTS_DISPLAY_ANONYMOUS_OWNED;
    }

    return $this->buildPage(Constants::VIEW_LISTS, $displayName);
  }

  /**
   * Returns a page listing root folders shared with the user.
   *
   * When the module's sharing settings are enabled for the site, the view
   * associated with this page lists all root folders shared with the
   * current user, but not owned by them.
   *
   * When the module's sharing settings are disabled for the site, an
   * error message is shown.
   *
   * @return array
   *   A renderable array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws an exception if the the view display does not allow access.
   */
  public function sharedRootFolders() {
    // Sharing allowed?
    if (Settings::getSharingAllowed() === FALSE) {
      return [
        '#type' => 'html_tag',
        '#tag'  => 'p',
        '#value' => t('File and folder sharing has been disabled by the site administrator.'),
      ];
    }

    return $this->buildPage(
      Constants::VIEW_LISTS,
      Constants::VIEW_LISTS_DISPLAY_USER_SHARED);
  }

  /**
   * Builds and returns a renderable array describing a view page.
   *
   * Arguments name the view and display to use. If the view or display
   * do not exist, a 'misconfigured web site' error message is logged and
   * the user is given a generic error message. If the display does not
   * allow access, an access denied exception is thrown.
   *
   * Otherwise, a page is generated that includes a user interface above
   * an embed of the named view and display.
   *
   * @param string $viewName
   *   The name of the view to embed in the page.
   * @param string $displayName
   *   The name of the view display to embed in the page.
   *
   * @return array
   *   A renderable array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws an exception if the named display's access controls
   *   do not allow access.
   */
  private function buildPage(string $viewName, string $displayName) {
    //
    // View setup
    // ----------
    // Find the embedded view and display, confirming that both exist and
    // that the user has access. Generate errors if something is wrong.
    $error = FALSE;
    $view = NULL;

    if (($view = Views::getView($viewName)) === NULL) {
      // Unknown view!
      \Drupal::logger(Constants::MODULE)->error(t(
        Messages::VIEW_MISSING,
        [
          '@viewName'   => $viewName,
          '@moduleName' => Constants::MODULE,
        ]));
      $error = TRUE;
    }
    elseif ($view->setDisplay($displayName) === FALSE) {
      // Unknown display!
      \Drupal::logger(Constants::MODULE)->error(t(
        Messages::VIEW_DISPLAY_MISSING,
        [
          '@viewName'    => $viewName,
          '@displayName' => $displayName,
          '@moduleName'  => Constants::MODULE,
        ]));
      $error = TRUE;
    }
    elseif ($view->access($displayName) === FALSE) {
      // User does not have access. Access denied.
      throw new AccessDeniedHttpException();
    }

    //
    // Error page
    // ----------
    // If the view could not be found, there is nothing to embed and there
    // is no point in adding a UI. Return an error message in place of the
    // view's content.
    if ($error === TRUE) {
      return [
        '#attached' => [
          'library' => [
            Constants::LIBRARY_MODULE,
          ],
        ],
        '#attributes' => [
          'class'   => [
            Constants::MODULE . '-error',
          ],
        ],

        // Do not cache this page. If any of the above conditions change,
        // the page needs to be regenerated.
        '#cache' => [
          'max-age' => 0,
        ],

        // Return an error message.
        'error'     => [
          '#type'   => 'item',
          '#markup' => t(Messages::PROBLEM_WITH_PAGE),
        ],
      ];
    }

    //
    // Search box UI
    // -------------
    // If the UI is enabled, create the search form to include below.
    // This will fail if the Search module is not enabled, the search plugin
    // cannot be found, or if the user does not have search permission.
    $searchBoxForm = NULL;
    if (Constants::ENABLE_UI_SEARCH_BOX === TRUE) {
      $searchBoxForm = \Drupal::formBuilder()->getForm(
        UISearchBox::class,
        (-1));
    }

    //
    // Ancestor menu UI
    // ----------------
    // If the UI is enabled, create the form to include below.
    //
    // Passing a (-1) flags that we are on a root folder group page.
    $ancestorMenuForm = NULL;
    if (Constants::ENABLE_UI_ANCESTOR_MENU === TRUE) {
      $ancestorMenuForm = \Drupal::formBuilder()->getForm(
        UIAncestorMenu::class,
        (-1));
    }

    //
    // Folder table menu UI
    // --------------------
    // If the UI is enabled, create the UI form to include below.
    //
    // Passing a (-1) flags that we are on a root folder group page.
    $folderTableMenuForm = NULL;
    if (Constants::ENABLE_UI_FOLDER_TABLE_MENU === TRUE) {
      $folderTableMenuForm = \Drupal::formBuilder()->getForm(
        UIFolderTableMenu::class,
        (-1));
    }

    //
    // Build view
    // ----------
    // Assemble parts of the page, including the UI forms and embedded view.
    //
    // When the main UI is disabled, revert to the base user interface
    // included with the embedded view. Just add the view to the page, and
    // no Javascript-based main UI.
    //
    // When the main UI is enabled, attach Javascript and the main UI
    // form before the view. The form adds hidden fields used by the
    // Javascript.
    $page = [
      '#theme'        => Constants::THEME_VIEW,
      '#attached'     => [
        'library'     => [
          Constants::LIBRARY_MODULE,
        ],
      ],
      '#attributes'   => [
        'class'       => [
          Constants::MODULE . '-view',
          Constants::MODULE . '-view-page',
        ],
      ],

      // Do not cache this page. If anybody adds or removes a folder or
      // changes sharing, the view will change and the page needs to
      // be regenerated.
      '#cache'        => [
        'max-age'     => 0,
      ],

      'toolbar-and-folder-table' => [
        '#type'       => 'container',
        '#attributes' => [
          'class'     => [
            'foldershare-toolbar-and-folder-table',
          ],
        ],

        'toolbar'       => [
          '#type'       => 'container',
          '#attributes' => [
            'class'     => [
              'foldershare-toolbar',
            ],
          ],

          // Add the folder table menu UI.
          'foldertablemenu' => $folderTableMenuForm,

          // Add the ancestor menu UI.
          'ancestormenu' => $ancestorMenuForm,

          // Add the search box UI.
          'searchbox'   => $searchBoxForm,
        ],

        // Add the view with the base UI overridden by the main UI.
        'view'          => [
          '#type'       => 'view',
          '#embed'      => TRUE,
          '#name'       => $viewName,
          '#display_id' => $displayName,
          '#arguments'  => [(-1)],
          '#attributes' => [
            'class'     => [
              'foldershare-folder-table',
            ],
          ],
        ],
      ],
    ];

    if (Constants::ENABLE_UI_FOLDER_TABLE_MENU === TRUE) {
      $page['#attached']['library'][] = Constants::LIBRARY_MAINUI;
      $page['#attached']['drupalSettings'] = [
        Constants::MODULE . '-view-page' => [
          'viewName'        => $viewName,
          'displayName'     => $displayName,
          'viewAjaxEnabled' => $view->ajaxEnabled(),
        ],
      ];
    }
    else {
      unset($page['foldershare-toolbar-and-folder-table']['foldershare-toolbar']['foldertablemenu']);
    }

    if (Constants::ENABLE_UI_ANCESTOR_MENU === FALSE) {
      unset($page['foldershare-toolbar-and-folder-table']['foldershare-toolbar']['ancestormenu']);
    }

    if (Constants::ENABLE_UI_SEARCH_BOX === FALSE) {
      unset($page['foldershare-toolbar-and-folder-table']['foldershare-toolbar']['searchbox']);
    }

    return $page;
  }

}
