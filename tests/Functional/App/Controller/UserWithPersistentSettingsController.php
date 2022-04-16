<?php

declare(strict_types=1);

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Tests\Functional\App\Controller;

use Symfony\Component\HttpFoundation\Response;

class UserWithPersistentSettingsController extends UserController
{
    public function getCrudName(): string
    {
        return 'crud_persistent_settings';
    }

    public function getCrudRouteName(): string
    {
        return 'user_with_persistent_settings_ajax_crud';
    }

    public function getCrudRouteParams(): array
    {
        return [
            'scope' => $this->container->get('request_stack')->getCurrentRequest()->attributes->get('scope'),
            'persistent' => $this->container->get('request_stack')->getCurrentRequest()->attributes->get('persistent'),
        ];
    }

    public function getPersistentSettings(): bool
    {
        return 'yes' === $this->container->get('request_stack')->getCurrentRequest()->attributes->get('persistent');
    }

    public function loginAction(): Response
    {
        return $this->render('login.html.twig');
    }
}
