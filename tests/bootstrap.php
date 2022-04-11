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

use DG\BypassFinals;

require __DIR__.'/Functional/App/config/bootstrap.php';

function bootstrap(): void
{
    BypassFinals::enable();

    $kernel = new \Ecommit\CrudBundle\Tests\Functional\App\Kernel('test', true);
    $kernel->boot();

    $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
    $application->setAutoExit(false);

    $application->run(new \Symfony\Component\Console\Input\ArrayInput([
        'command' => 'doctrine:database:drop',
        '--force' => true,
    ]));

    $application->run(new \Symfony\Component\Console\Input\ArrayInput([
        'command' => 'doctrine:database:create',
    ]));

    $application->run(new \Symfony\Component\Console\Input\ArrayInput([
        'command' => 'doctrine:schema:update',
        '--force' => true,
    ]));

    $application->run(new \Symfony\Component\Console\Input\ArrayInput([
        'command' => 'doctrine:fixtures:load',
        '--no-interaction' => true,
    ]));
}

bootstrap();
