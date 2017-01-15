<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @copyright Copyright (c) 2013-2016 Alexander Weissman
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\Admin\Controller;

use Carbon\Carbon;
use UserFrosting\Sprinkle\Core\Controller\SimpleController;
use UserFrosting\Support\Exception\ForbiddenException;
use UserFrosting\Sprinkle\Account\Model\Group;
use UserFrosting\Sprinkle\Account\Model\User;
use UserFrosting\Sprinkle\Account\Model\Role;
use UserFrosting\Sprinkle\Admin\Model\Version;
use UserFrosting\Sprinkle\Core\Util\EnvironmentInfo;

/**
 * AdminController Class
 *
 * Controller class for /admin URL.  Handles admin-related activities
 *
 * @author Alex Weissman
 * @link http://www.userfrosting.com/navigating/#structure
 */
class AdminController extends SimpleController
{

    /**
     * Renders the admin panel dashboard
     *
     */
    public function pageDashboard($request, $response, $args)
    {
        //** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = $this->ci->currentUser;

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'uri_dashboard')) {
            throw new ForbiddenException();
        }

        // Probably a better way to do this
        $users = User::orderBy('created_at', 'desc')
               ->take(8)
               ->get();

        // Transform the `create_at` date in "x days ago" type of string
        $users->transform(function ($item, $key) {
            $item->registered = Carbon::parse($item->created_at)->diffForHumans();
            return $item;
        });

        /** @var Config $config */
        $config = $this->ci->config;

        return $this->ci->view->render($response, "pages/dashboard.html.twig", [
            'counter' => [
                'users' => User::count(),
                'roles' => Role::count(),
                'groups' => Group::count(),
            ],
            'info' => [
                'version' => [
                    'UF' => Version::where('sprinkle', 'core')->first()->version,
                    'php' => phpversion(),
                    'database' => EnvironmentInfo::database()
                ],
                'database' => [
                    'name' => $config['db.default.database']
                ],
                'environment' => $this->ci->environment,
                'path' => [
                    'project' => \UserFrosting\ROOT_DIR
                ]
            ],
            'sprinkles' => $this->ci->sprinkleManager->getSprinkles(),
            'users' => $users
        ]);
    }
}
