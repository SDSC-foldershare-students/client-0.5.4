<?php

namespace Drupal\foldershare\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Session\AccountInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\foldershare\Constants;
use Drupal\foldershare\Entity\FolderShare;

/**
 * Creates a form and menu button to select among ancestor folders.
 *
 * This class builds a form containing a menu button and list of ancestor
 * folder destinations. Selecting the menu button presents the ancestor
 * folder list as a pull-down menu. Selecting a menu item loads that
 * ancestor's page.
 *
 * Technically, a form is not required since it just uses Javascript to
 * reload the page. The form is never submitted.
 *
 * <B>Warning:</B> This class is strictly internal to the FolderShare
 * module. The class's existance, name, and content may change from
 * release to release without any promise of backwards compatability.
 *
 * @ingroup foldershare
 */
class UIAncestorMenu extends FormBase {

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

  /*--------------------------------------------------------------------
   *
   * Construction.
   *
   *--------------------------------------------------------------------*/

  /**
   * Constructs a new form.
   */
  public function __construct(
    RouteProvider $routeProvider,
    AccountInterface $currentUser) {

    $this->routeProvider = $routeProvider;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('current_user'));
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
    // Get the current page's entity ID from the build arguments.
    // If not provided, default to (-1) to indicate there is no page entity.
    $args = $formState->getBuildInfo()['args'];

    if (empty($args) === TRUE) {
      $pageEntityId = (int) (-1);
      $pageEntity   = NULL;
    }
    elseif ((int) $args[0] < 0) {
      $pageEntityId = (int) $args[0];
      $pageEntity   = NULL;
    }
    else {
      $pageEntityId = (int) $args[0];
      $pageEntity   = FolderShare::load($pageEntityId);
      if ($pageEntity === NULL) {
        $pageEntityId = (int) (-1);
      }
    }

    //
    // Define form classes
    // -------------------
    // Define classes used to mark the form and its items. These classes
    // are then used in CSS to style the form.
    $form['#attributes']['class'][] = 'foldershare-ancestormenu';

    $uiClass         = 'foldershare-ancestormenu';
    $menuButtonClass = $uiClass . '-menu-button';
    $menuClass       = $uiClass . '-menu';

    //
    // Add ancestors
    // -------------
    // Build a list of ancestor folders for the ancestor menu. For each one,
    // include the ancestor's URL as an attribute. Javascript will use this
    // to load the appropriate page. The URL is not included in an <a> for
    // the menu item so that menu items aren't styled as links.
    //
    // File classes are added so that themes can style the item with
    // folder icons.
    $menuHtml = "<ul class=\"hidden $menuClass\">";

    if ($pageEntity !== NULL) {
      // The page is for entity. Get its ancestors.
      $folders = $pageEntity->getAncestorFolders();

      // Push the current entity onto the ancestor list so that it gets
      // included in the menu.
      array_unshift($folders, $pageEntity);

      // Add ancestors to menu.
      foreach ($folders as $item) {
        // Get the URL to the folder.
        $url = rawurlencode($item->toUrl(
          'canonical',
          [
            'absolute' => TRUE,
          ])->toString());

        // Get the name for the folder.
        $name = Html::escape($item->getName());

        // Add the HTML. Include file classes that mark this as a folder
        // or root folder.
        if ($item->isRootFolder() === TRUE) {
          $fileClasses = 'file file--folder file--mime-rootfolder-directory';
        }

        if ($item->isFolder() === TRUE) {
          $fileClasses = 'file file--folder file--mime-folder-directory';
        }
        else {
          $mimes = explode('/', $item->getMimeType());
          $fileClasses = 'file file--' . $mimes[0] .
            ' file--mime-' . $mimes[0] . '-' . $mimes[1];
        }

        $menuHtml .= "<li data-foldershare-url=\"$url\"><div><span class=\"$fileClasses\"></span>$name</div></li>";
      }
    }

    //
    // Add root folder group
    // ---------------------
    // Decide on the appropriate group by looking at the root folder
    // of the page entity. If there is none, then default to the
    // personal root folder list (for authenticated users) or the public
    // root folder list (for anonymous users).
    if ($pageEntityId !== (-2)) {
      if ($pageEntity === NULL) {
        if ($this->currentUser->isAnonymous() === TRUE) {
          $r = Constants::ROUTE_PUBLIC_ROOTFOLDERS;
        }
        else {
          $r = Constants::ROUTE_PERSONAL_ROOTFOLDERS;
        }
      }
      else {
        $rootFolder = $pageEntity->getRootFolder();
        switch ($rootFolder->getSharingStatus()) {
          default:
          case 'private':
            // Root folder is owned by someone else and has not been shared
            // with the current user.  Since the entity is being viewed anyway,
            // presumably the current user has admin permission.
            if ($this->currentUser->hasPermission(Constants::ADMINISTER_PERMISSION) === TRUE) {
              $r = Constants::ROUTE_ALL_ROOTFOLDERS;
            }
            else {
              $r = Constants::ROUTE_PERSONAL_ROOTFOLDERS;
            }
            break;

          case 'shared by you':
          case 'personal':
            // Root folder is owned by this user. Use the personal group.
            $r = Constants::ROUTE_PERSONAL_ROOTFOLDERS;
            break;

          case 'public':
            // Root folder is owned by anonymous or shared with anonymous.
            // Use the public group.
            $r = Constants::ROUTE_PUBLIC_ROOTFOLDERS;
            break;

          case 'shared with you':
            // Root folder is owned by someone else and shared with this user.
            // Use the shared group.
            $r = Constants::ROUTE_SHARED_ROOTFOLDERS;
            break;
        }
      }

      $route = $this->routeProvider->getRouteByName($r);
      $name = t($route->getDefault('_title'))->render();
      $url = Url::fromRoute(
        $r,
        [],
        ['absolute' => TRUE])->toString();
      $fileClasses = 'file file--folder file--mime-rootfolder-group-directory';
      $menuHtml .= "<li data-foldershare-url=\"$url\"><div><span class=\"$fileClasses\"></span>$name</div></li>";
    }

    //
    // Add root folder groups
    // ----------------------
    // Use the route to add an entry to the groups page.
    $r = Constants::ROUTE_ROOTFOLDERS_GROUPS;
    $route = $this->routeProvider->getRouteByName($r);
    $name = t($route->getDefault('_title'))->render();
    $url = Url::fromRoute(
      $r,
      [],
      ['absolute' => TRUE])->toString();
    $fileClasses = 'file file--folder file--mime-rootfolder-groups-directory';
    $menuHtml .= "<li data-foldershare-url=\"$url\"><div><span class=\"$fileClasses\"></span>$name</div></li>";

    $menuHtml .= '</ul>';

    //
    // Create button
    // -------------
    // Create HTML for a button. Include:
    //
    // - Class 'hidden' so that the button is initially hidden and only shown
    //   later by Javascript, if the browser supports scripting.
    $buttonText = t('Ancestors');
    $buttonHtml = "<button type=\"button\" class=\"hidden $menuButtonClass\"><span>$buttonText</span></button>";

    //
    // Create UI
    // ---------
    // Everything is hidden initially, and only exposed by Javascript, if
    // the browser supports Javascript.
    $form[$uiClass] = [
      '#type'              => 'container',
      '#weight'            => -90,
      '#attributes'        => [
        'class'            => [
          $uiClass,
          'hidden',
        ],
      ],

      // Add a hierarchical menu of ancestors. Javascript uses jQuery.ui
      // to build a menu from this and presents it from a menu button.
      // The menu was built and marked as hidden.
      //
      // Implementation note: The field is built using an inline template
      // to avoid Drupal's HTML cleaning that can remove classes and
      // attributes on the menu items, which we need to retain to provide
      // the URLs of ancestor folders. Those URLs are used by Javascript
      // to load the appropriate page when a menu item is selected.
      $menuClass           => [
        '#type'            => 'inline_template',
        '#template'        => '{{ menu|raw }}',
        '#context'         => [
          'menu'           => $menuHtml,
        ],
      ],

      // Add a button to go up a folder. Javascript binds a behavior
      // to the button to load the parent page. The button is hidden
      // initially and only shown if the browser supports Javascript.
      //
      // Implementation note: The field is built using an inline template
      // so that we get a <button>. If we used the '#type' 'button',
      // Drupal instead creates an <input>. Since we specifically want a
      // button so that jQuery.button() will button-ize it, we have to
      // bypass Drupal.
      $menuButtonClass       => [
        '#type'            => 'inline_template',
        '#template'        => '{{ button|raw }}',
        '#context'         => [
          'button'         => $buttonHtml,
        ],
      ],
    ];

    return $form;
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
    // Do nothing.
  }

  /*--------------------------------------------------------------------
   *
   * Form submit
   *
   *--------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    // Do nothing.
  }

}
