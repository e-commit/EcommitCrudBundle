<?php

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class AbstractCrudLegacyController extends AbstractController
{
    use CrudControllerTrait;

    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            self::getCrudRequiredServices()
        );
    }
}
