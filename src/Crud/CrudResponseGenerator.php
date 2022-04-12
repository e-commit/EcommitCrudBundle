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

namespace Ecommit\CrudBundle\Crud;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;

final class CrudResponseGenerator implements ServiceSubscriberInterface
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function getResponse(Crud $crud, array $options = []): Response
    {
        $options = $this->getOptions($options);
        $data = $this->processCrud($crud, $options);

        return $this->renderCrud($this->getTemplateName($options['template_generator'], 'index'), $data);
    }

    public function getAjaxResponse(Crud $crud, array $options = []): Response
    {
        $masterRequest = $this->container->get('request_stack')->getMainRequest();
        if (!$masterRequest->isXmlHttpRequest()) {
            throw new NotFoundHttpException('Ajax is required');
        }

        $options = $this->getOptions($options);
        $data = $this->processCrud($crud, $options);

        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->query->has('search')) {
            return new JsonResponse([
                'render_search' => $this->renderCrudView($this->getTemplateName($options['template_generator'], 'search'), $data),
                'render_list' => $this->renderCrudView($this->getTemplateName($options['template_generator'], 'list'), $data),
            ]);
        } elseif ($request->query->has('display-settings')) {
            return new JsonResponse([
                'render_list' => $this->renderCrudView($this->getTemplateName($options['template_generator'], 'list'), $data),
                'form_is_valid' => $crud->getDisplaySettingsForm()->vars['valid'],
            ]);
        }

        return $this->renderCrud($this->getTemplateName($options['template_generator'], 'list'), $data);
    }

    public function getCrudData(Crud $crud, array $options = []): array
    {
        $options = $this->getOptions($options);

        return $this->processCrud($crud, $options);
    }

    public function getCrudContents(Crud $crud, array $options = []): array
    {
        $data = $this->getCrudData($crud, $options);

        return [
            'render_search' => $this->renderCrudView($this->getTemplateName($options['template_generator'], 'search'), $data),
            'render_list' => $this->renderCrudView($this->getTemplateName($options['template_generator'], 'list'), $data),
        ];
    }

    protected function processCrud(Crud $crud, array $options): array
    {
        $data = [
            'crud' => $crud,
        ];

        $request = $this->container->get('request_stack')->getCurrentRequest();

        $crud->initIfNecessary();
        if ($request->query->has('search')) {
            $crud->processForm();
        }

        if (null !== $options['before_build']) {
            $data = $options['before_build']($crud, $data);
        }

        $crud->build();

        if (null !== $options['after_build']) {
            $data = $options['after_build']($crud, $data);
        }

        $crud->clearTemplate();

        return $data;
    }

    protected function getOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'before_build' => null,
            'after_build' => null,
        ]);
        $resolver->setRequired([
            'template_generator',
        ]);
        $resolver->setAllowedTypes('template_generator', 'callable');
        $resolver->setAllowedTypes('before_build', ['null', 'callable']);
        $resolver->setAllowedTypes('after_build', ['null', 'callable']);

        return $resolver->resolve($options);
    }

    protected function getTemplateName(callable $templateGenerator, string $action): string
    {
        return $templateGenerator($action);
    }

    protected function renderCrudView(string $view, array $parameters = []): string
    {
        return $this->container->get('twig')->render($view, $parameters);
    }

    protected function renderCrud(string $view, array $parameters = [], Response $response = null): Response
    {
        $content = $this->container->get('twig')->render($view, $parameters);

        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($content);

        return $response;
    }

    public static function getSubscribedServices(): array
    {
        return [
            'twig' => Environment::class,
            'request_stack' => RequestStack::class,
        ];
    }
}
