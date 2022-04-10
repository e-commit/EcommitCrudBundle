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

namespace Ecommit\CrudBundle\Tests\Twig;

use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Crud\CrudColumn;
use Ecommit\CrudBundle\Crud\CrudSession;
use Ecommit\CrudBundle\Twig\CrudExtension;
use Ecommit\Paginator\ArrayPaginator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Twig\Environment;
use Twig\Markup;

class CrudExtensionTest extends KernelTestCase
{
    /**
     * @var CrudExtension
     */
    protected $crudExtension;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var Environment
     */
    protected $environment;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->crudExtension = self::getContainer()->get(CrudExtension::class);
        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);
        $this->environment = self::getContainer()->get(Environment::class);
    }

    public function testFormStartAjax(): void
    {
        $html = $this->crudExtension->formStartAjax($this->createFormView());

        $xpath = '//form[@method="post" and @action="/url" and contains(concat(" ", normalize-space(@class), " "), " ec-crud-ajax-form-auto ")]';
        $this->assertMatchesXpath($html, $xpath);
    }

    public function testFormStartAjaxWithCustomAutoClass(): void
    {
        $html = $this->crudExtension->formStartAjax($this->createFormView(), [
            'auto_class' => 'custom-class',
        ]);

        $xpath = '//form[not(contains(concat(" ", normalize-space(@class), " "), " ec-crud-ajax-form-auto ")) and contains(concat(" ", normalize-space(@class), " "), " custom-class ")]';
        $this->assertMatchesXpath($html, $xpath);
    }

    public function testFormStartAjaxWithClassAttr(): void
    {
        $html = $this->crudExtension->formStartAjax($this->createFormView(), [
            'attr' => [
                'class' => 'my-class',
            ],
        ]);

        $xpath = '//form[(contains(concat(" ", normalize-space(@class), " "), " ec-crud-ajax-form-auto ")) and contains(concat(" ", normalize-space(@class), " "), " my-class ")]';
        $this->assertMatchesXpath($html, $xpath);
    }

    public function testFormStartAjaxWithAjaxOptions(): void
    {
        $html = $this->crudExtension->formStartAjax($this->createFormView(), [
            'ajax_options' => [
                'on_success' => 'a=b',
            ],
        ]);

        $xpath = '//form[(contains(concat(" ", normalize-space(@class), " "), " ec-crud-ajax-form-auto ")) and @data-ec-crud-ajax-on-success="a=b"]';
        $this->assertMatchesXpath($html, $xpath);
    }

    public function testFormStartAjaxWithAttrAndAjaxOptions(): void
    {
        $html = $this->crudExtension->formStartAjax($this->createFormView(), [
            'attr' => [
                'data-custom' => 'custom',
            ],
            'ajax_options' => [
                'on_success' => 'a=b',
            ],
        ]);

        $xpath = '//form[(contains(concat(" ", normalize-space(@class), " "), " ec-crud-ajax-form-auto ")) and @data-ec-crud-ajax-on-success="a=b" and @data-custom="custom"]';
        $this->assertMatchesXpath($html, $xpath);
    }

    public function testFormStartAjaxWithBadAjaxOptions(): void
    {
        $this->expectException(UndefinedOptionsException::class);
        $this->crudExtension->formStartAjax($this->createFormView(), [
            'ajax_options' => [
                'badOption' => 'value',
            ],
        ]);
    }

    /**
     * @dataProvider getTestAjaxAttributesProvider
     */
    public function testAjaxAttributes(array $ajaxOptions, string $expected): void
    {
        $result = $this->crudExtension->ajaxAttributes(
            $this->environment,
            $ajaxOptions
        );

        $this->assertSame($expected, $result);
    }

    public function getTestAjaxAttributesProvider(): array
    {
        return [
            [[], ''],
            [['url' => '/url?id=a'], ' data-ec-crud-ajax-url="/url?id=a"'],
            [['url' => '/url?id=a', 'method' => 'GET'], ' data-ec-crud-ajax-url="/url?id=a" data-ec-crud-ajax-method="GET"'],
            [['data' => '<script>ValueToEscape</script>'], ' data-ec-crud-ajax-data="&lt;script&gt;ValueToEscape&lt;/script&gt;"'],
            [['cache' => true], ' data-ec-crud-ajax-cache="true"'],
            [['cache' => false], ' data-ec-crud-ajax-cache="false"'],
            [['data' => ['var1' => 'value1']], ' data-ec-crud-ajax-data="{&quot;var1&quot;:&quot;value1&quot;}"'],
        ];
    }

    public function testAjaxAttributesWithBadAjaxOptions(): void
    {
        $this->expectException(UndefinedOptionsException::class);
        $this->crudExtension->ajaxAttributes(
            $this->environment,
            ['badOption' => 'value']
        );
    }

    public function testPaginatorLinksWithNoResult(): void
    {
        $paginator = new ArrayPaginator([
            'max_per_page' => 5,
            'data' => [],
        ]);

        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_crud');

        $this->assertEmpty($result);
    }

    public function testPaginatorLinksWithOnePage(): void
    {
        $paginator = new ArrayPaginator([
            'max_per_page' => 5,
            'data' => ['val'],
        ]);

        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_crud');

        $this->assertEmpty($result);
    }

    /**
     * @dataProvider getTestPaginatorWithDefaultOptionsProvider
     */
    public function testPaginatorWithDefaultOptions(int $page, string $expected): void
    {
        $paginator = new ArrayPaginator([
            'max_per_page' => 5,
            'data' => range(1, 100),
            'page' => $page,
        ]);

        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_crud');

        $this->assertSame($expected, $result);
    }

    public function getTestPaginatorWithDefaultOptionsProvider(): array
    {
        return [
            [1, '<nav><ul class="ec-crud-pagination"><li class="current"><a href="/user?page=1">1</a></li><li><a href="/user?page=2">2</a></li><li><a href="/user?page=3">3</a></li><li><a href="/user?page=4">4</a></li><li class="next"><a href="/user?page=2">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [2, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=1">‹</a></li><li><a href="/user?page=1">1</a></li><li class="current"><a href="/user?page=2">2</a></li><li><a href="/user?page=3">3</a></li><li><a href="/user?page=4">4</a></li><li><a href="/user?page=5">5</a></li><li class="next"><a href="/user?page=3">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [10, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=9">‹</a></li><li><a href="/user?page=7">7</a></li><li><a href="/user?page=8">8</a></li><li><a href="/user?page=9">9</a></li><li class="current"><a href="/user?page=10">10</a></li><li><a href="/user?page=11">11</a></li><li><a href="/user?page=12">12</a></li><li><a href="/user?page=13">13</a></li><li class="next"><a href="/user?page=11">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [19, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=18">‹</a></li><li><a href="/user?page=16">16</a></li><li><a href="/user?page=17">17</a></li><li><a href="/user?page=18">18</a></li><li class="current"><a href="/user?page=19">19</a></li><li><a href="/user?page=20">20</a></li><li class="next"><a href="/user?page=20">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [20, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=19">‹</a></li><li><a href="/user?page=17">17</a></li><li><a href="/user?page=18">18</a></li><li><a href="/user?page=19">19</a></li><li class="current"><a href="/user?page=20">20</a></li></ul></nav>'],
        ];
    }

    /**
     * @dataProvider getTestPaginatorWithMaxPagesOptionsProvider
     */
    public function testPaginatorWithMaxPagesOptions(int $page, string $expected): void
    {
        $paginator = new ArrayPaginator([
            'max_per_page' => 5,
            'data' => range(1, 100),
            'page' => $page,
        ]);

        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_crud', [], [
            'max_pages_before' => 4,
            'max_pages_after' => 2,
        ]);

        $this->assertSame($expected, $result);
    }

    public function getTestPaginatorWithMaxPagesOptionsProvider(): array
    {
        return [
            [1, '<nav><ul class="ec-crud-pagination"><li class="current"><a href="/user?page=1">1</a></li><li><a href="/user?page=2">2</a></li><li><a href="/user?page=3">3</a></li><li class="next"><a href="/user?page=2">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [2, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=1">‹</a></li><li><a href="/user?page=1">1</a></li><li class="current"><a href="/user?page=2">2</a></li><li><a href="/user?page=3">3</a></li><li><a href="/user?page=4">4</a></li><li class="next"><a href="/user?page=3">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [10, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=9">‹</a></li><li><a href="/user?page=6">6</a></li><li><a href="/user?page=7">7</a></li><li><a href="/user?page=8">8</a></li><li><a href="/user?page=9">9</a></li><li class="current"><a href="/user?page=10">10</a></li><li><a href="/user?page=11">11</a></li><li><a href="/user?page=12">12</a></li><li class="next"><a href="/user?page=11">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [19, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=18">‹</a></li><li><a href="/user?page=15">15</a></li><li><a href="/user?page=16">16</a></li><li><a href="/user?page=17">17</a></li><li><a href="/user?page=18">18</a></li><li class="current"><a href="/user?page=19">19</a></li><li><a href="/user?page=20">20</a></li><li class="next"><a href="/user?page=20">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [20, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=19">‹</a></li><li><a href="/user?page=16">16</a></li><li><a href="/user?page=17">17</a></li><li><a href="/user?page=18">18</a></li><li><a href="/user?page=19">19</a></li><li class="current"><a href="/user?page=20">20</a></li></ul></nav>'],
        ];
    }

    /**
     * @dataProvider getTestPaginatorWithTypeOptionProvider
     */
    public function testPaginatorWithTypeOption(int $page, string $type, string $expected): void
    {
        $paginator = new ArrayPaginator([
            'max_per_page' => 5,
            'data' => range(1, 100),
            'page' => $page,
        ]);

        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_crud', [], [
            'type' => $type,
        ]);

        $this->assertSame($expected, $result);
    }

    public function getTestPaginatorWithTypeOptionProvider(): array
    {
        $full = function (int $currentPage) {
            $result = '';
            for ($i = 1; $i <= 20; ++$i) {
                $class = ($i === $currentPage) ? ' class="current"' : '';
                $result .= sprintf('<li%s><a href="/user?page=%s">%s</a></li>', $class, $i, $i);
            }

            return $result;
        };

        return [
            [1, 'sliding', '<nav><ul class="ec-crud-pagination"><li class="current"><a href="/user?page=1">1</a></li><li><a href="/user?page=2">2</a></li><li><a href="/user?page=3">3</a></li><li><a href="/user?page=4">4</a></li><li class="next"><a href="/user?page=2">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [2, 'sliding', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=1">‹</a></li><li><a href="/user?page=1">1</a></li><li class="current"><a href="/user?page=2">2</a></li><li><a href="/user?page=3">3</a></li><li><a href="/user?page=4">4</a></li><li><a href="/user?page=5">5</a></li><li class="next"><a href="/user?page=3">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [10, 'sliding', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=9">‹</a></li><li><a href="/user?page=7">7</a></li><li><a href="/user?page=8">8</a></li><li><a href="/user?page=9">9</a></li><li class="current"><a href="/user?page=10">10</a></li><li><a href="/user?page=11">11</a></li><li><a href="/user?page=12">12</a></li><li><a href="/user?page=13">13</a></li><li class="next"><a href="/user?page=11">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [19, 'sliding', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=18">‹</a></li><li><a href="/user?page=16">16</a></li><li><a href="/user?page=17">17</a></li><li><a href="/user?page=18">18</a></li><li class="current"><a href="/user?page=19">19</a></li><li><a href="/user?page=20">20</a></li><li class="next"><a href="/user?page=20">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [20, 'sliding', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=19">‹</a></li><li><a href="/user?page=17">17</a></li><li><a href="/user?page=18">18</a></li><li><a href="/user?page=19">19</a></li><li class="current"><a href="/user?page=20">20</a></li></ul></nav>'],

            [1, 'elastic', '<nav><ul class="ec-crud-pagination">'.$full(1).'<li class="next"><a href="/user?page=2">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [2, 'elastic', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=1">‹</a></li>'.$full(2).'<li class="next"><a href="/user?page=3">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [10, 'elastic', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=9">‹</a></li>'.$full(10).'<li class="next"><a href="/user?page=11">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [19, 'elastic', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=18">‹</a></li>'.$full(19).'<li class="next"><a href="/user?page=20">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [20, 'elastic', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=19">‹</a></li>'.$full(20).'</ul></nav>'],
        ];
    }

    public function testPaginatorWithEmptyArrayAjaxOption(): void
    {
        $paginator = new ArrayPaginator([
            'max_per_page' => 5,
            'data' => range(1, 100),
            'page' => 1,
        ]);

        $expected = '<nav><ul class="ec-crud-pagination"><li class="current"><a href="/user?page=1" class="ec-crud-ajax-link-auto">1</a></li><li><a href="/user?page=2" class="ec-crud-ajax-link-auto">2</a></li><li class="next"><a href="/user?page=2" class="ec-crud-ajax-link-auto">›</a></li><li class="last"><a href="/user?page=20" class="ec-crud-ajax-link-auto">»</a></li></ul></nav>';
        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_crud', [], [
            'ajax_options' => [],
            'max_pages_before' => 1,
            'max_pages_after' => 1,
        ]);

        $this->assertSame($expected, $result);
    }

    public function testPaginatorWithAjaxOption(): void
    {
        $paginator = new ArrayPaginator([
            'max_per_page' => 5,
            'data' => range(1, 100),
            'page' => 1,
        ]);

        $expected = '<nav><ul class="ec-crud-pagination"><li class="current"><a href="/user?page=1" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">1</a></li><li><a href="/user?page=2" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">2</a></li><li class="next"><a href="/user?page=2" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">›</a></li><li class="last"><a href="/user?page=20" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">»</a></li></ul></nav>';
        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_crud', [], [
            'ajax_options' => [
                'update' => '#myId',
            ],
            'max_pages_before' => 1,
            'max_pages_after' => 1,
        ]);

        $this->assertSame($expected, $result);
    }

    public function testPaginatorWithAttributePageOption(): void
    {
        $paginator = new ArrayPaginator([
            'max_per_page' => 5,
            'data' => range(1, 100),
            'page' => 1,
        ]);

        $expected = '<nav><ul class="ec-crud-pagination"><li class="current"><a href="/user?p=1">1</a></li><li><a href="/user?p=2">2</a></li><li class="next"><a href="/user?p=2">›</a></li><li class="last"><a href="/user?p=20">»</a></li></ul></nav>';
        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_crud', [], [
            'attribute_page' => 'p',
            'max_pages_before' => 1,
            'max_pages_after' => 1,
        ]);

        $this->assertSame($expected, $result);
    }

    public function testPaginatorWithAttrOptions(): void
    {
        $paginator = new ArrayPaginator([
            'max_per_page' => 5,
            'data' => range(1, 100),
            'page' => 10,
        ]);

        $expected = '<nav class="navattr" data-nav="val"><ul class="ulattr ec-crud-pagination" data-ul="val"><li class="lifirstpageattr first" data-li-first="val"><a href="/user?page=1" class="afirstpageattr" data-a-first="val">«</a></li><li class="lipreviouspageattr previous"><a href="/user?page=9" class="apreviouspageattr">‹</a></li><li class="liotherpageattr"><a href="/user?page=9" class="aotherpageattr">9</a></li><li class="liccurentpageattr current"><a href="/user?page=10" class="accurentpageattr">10</a></li><li class="liotherpageattr"><a href="/user?page=11" class="aotherpageattr">11</a></li><li class="linextpageattr next"><a href="/user?page=11" class="anextpageattr">›</a></li><li class="lilastpageattr last"><a href="/user?page=20" class="alastpageattr">»</a></li></ul></nav>';
        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_crud', [], [
            'nav_attr' => ['class' => 'navattr', 'data-nav' => 'val'],
            'ul_attr' => ['class' => 'ulattr', 'data-ul' => 'val'],
            'li_attr' => [
                'first_page' => ['class' => 'lifirstpageattr', 'data-li-first' => 'val'],
                'previous_page' => ['class' => 'lipreviouspageattr'],
                'current_page' => ['class' => 'liccurentpageattr'],
                'next_page' => ['class' => 'linextpageattr'],
                'last_page' => ['class' => 'lilastpageattr'],
                'other_page' => ['class' => 'liotherpageattr'],
            ],
            'a_attr' => [
                'first_page' => ['class' => 'afirstpageattr', 'data-a-first' => 'val'],
                'previous_page' => ['class' => 'apreviouspageattr'],
                'current_page' => ['class' => 'accurentpageattr'],
                'next_page' => ['class' => 'anextpageattr'],
                'last_page' => ['class' => 'alastpageattr'],
                'other_page' => ['class' => 'aotherpageattr'],
            ],
            'max_pages_before' => 1,
            'max_pages_after' => 1,
        ]);

        $this->assertSame($expected, $result);
    }

    public function testPaginatorWithRenderOption(): void
    {
        $paginator = new ArrayPaginator([
            'max_per_page' => 5,
            'data' => range(1, 100),
            'page' => 1,
        ]);

        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_crud', [], [
            'render' => 'render.html.twig',
        ]);

        $this->assertRegExp('/OK/', $result);
    }

    public function testPaginatorWithThemeAndBlockOptions(): void
    {
        $paginator = new ArrayPaginator([
            'max_per_page' => 5,
            'data' => range(1, 100),
            'page' => 1,
        ]);

        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_crud', [], [
            'theme' => 'theme.html.twig',
            'block' => 'hello_world',
        ]);

        $this->assertRegExp('/Hello world/', $result);
    }

    public function testPaginatorWithBadOptions(): void
    {
        $this->expectException(UndefinedOptionsException::class);
        $paginator = new ArrayPaginator([
            'max_per_page' => 5,
            'data' => range(1, 100),
            'page' => 1,
        ]);

        $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_crud', [], [
            'bad_option' => 'bad',
        ]);
    }

    public function testCrudPaginatorLinks(): void
    {
        $paginator = new ArrayPaginator([
            'max_per_page' => 5,
            'data' => range(1, 100),
            'page' => 1,
        ]);

        $crud = $this->getMockBuilder(Crud::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDivIdList', 'getPaginator', 'getRouteName', 'getRouteParams'])
            ->getMock();
        $crud->expects($this->once())->method('getDivIdList')->willReturn('myId');
        $crud->expects($this->once())->method('getPaginator')->willReturn($paginator);
        $crud->expects($this->once())->method('getRouteName')->willReturn('user_crud');
        $crud->expects($this->once())->method('getRouteParams')->willReturn([]);

        $expected = '<nav><ul class="ec-crud-pagination"><li class="current"><a href="/user?page=1" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">1</a></li><li><a href="/user?page=2" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">2</a></li><li class="next"><a href="/user?page=2" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">›</a></li><li class="last"><a href="/user?page=20" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">»</a></li></ul></nav>';
        $result = $this->crudExtension->crudPaginatorLinks($this->environment, $crud, [
            'max_pages_before' => 1,
            'max_pages_after' => 1,
        ]);

        $this->assertSame($expected, $result);
    }

    public function testThColumnNotDisplayed(): void
    {
        $html = $this->crudExtension->th($this->environment, 'column4', $this->createCrud());
        $this->assertSame('', $html);
    }

    public function testThColumnNotSortable(): void
    {
        $html = $this->crudExtension->th($this->environment, 'column3', $this->createCrud());
        $this->assertSame('<th class="ec-crud-th ec-crud-th-not-sortable">label3</th>', $html);
    }

    public function testThColumnSortableNotActive(): void
    {
        $html = $this->crudExtension->th($this->environment, 'column2', $this->createCrud());
        $this->assertSame('<th class="ec-crud-th ec-crud-th-sortable-not-active"><a href="/user?sort=column2" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">label2</a></th>', $html);
    }

    public function testThColumnSortableActiveAsc(): void
    {
        $html = $this->crudExtension->th($this->environment, 'column1', $this->createCrud());
        $this->assertSame('<th class="ec-crud-th ec-crud-th-sortable-active-asc"><a href="/user?sort=column1&amp;sense=DESC" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">label1 <i>^</i></a></th>', $html);
    }

    public function testThColumnSortableActiveDesc(): void
    {
        $html = $this->crudExtension->th($this->environment, 'column1', $this->createCrud('column1', CRUD::DESC));
        $this->assertSame('<th class="ec-crud-th ec-crud-th-sortable-active-desc"><a href="/user?sort=column1&amp;sense=ASC" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">label1 <i>v</i></a></th>', $html);
    }

    public function testThWithAjaxOptions(): void
    {
        $html = $this->crudExtension->th($this->environment, 'column2', $this->createCrud(), [
            'ajax_options' => [
                'update' => '#div2',
            ],
        ]);
        $this->assertSame('<th class="ec-crud-th ec-crud-th-sortable-not-active"><a href="/user?sort=column2" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#div2">label2</a></th>', $html);
    }

    public function testThWithLabel(): void
    {
        $html = $this->crudExtension->th($this->environment, 'column2', $this->createCrud(), [
            'label' => 'My label',
        ]);
        $this->assertSame('<th class="ec-crud-th ec-crud-th-sortable-not-active"><a href="/user?sort=column2" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">My label</a></th>', $html);
    }

    /**
     * @dataProvider getTestThWithAttrOptionsProvider
     */
    public function testThWithAttrOptions(string $columnId, string $sort, string $sense, string $expected): void
    {
        $html = $this->crudExtension->th($this->environment, $columnId, $this->createCrud($sort, $sense), [
            'th_attr' => [
                'not_sortable' => ['class' => 'a', 'data-a' => 'val'],
                'sortable_active_asc' => ['class' => 'b', 'data-b' => 'val'],
                'sortable_active_desc' => ['class' => 'c', 'data-c' => 'val'],
                'sortable_not_active' => ['class' => 'd', 'data-d' => 'val'],
            ],
            'a_attr' => [
                'sortable_active_asc' => ['class' => 'e', 'data-e' => 'val'],
                'sortable_active_desc' => ['class' => 'f', 'data-f' => 'val'],
                'sortable_not_active' => ['class' => 'g', 'data-g' => 'val'],
            ],
        ]);

        $this->assertSame($expected, $html);
    }

    public function getTestThWithAttrOptionsProvider(): array
    {
        return [
            ['column3', 'column1', Crud::ASC, '<th class="a ec-crud-th ec-crud-th-not-sortable" data-a="val">label3</th>'],
            ['column1', 'column1', Crud::ASC, '<th class="b ec-crud-th ec-crud-th-sortable-active-asc" data-b="val"><a href="/user?sort=column1&amp;sense=DESC" class="e ec-crud-ajax-link-auto" data-e="val" data-ec-crud-ajax-update="#myId">label1 <i>^</i></a></th>'],
            ['column1', 'column1', Crud::DESC, '<th class="c ec-crud-th ec-crud-th-sortable-active-desc" data-c="val"><a href="/user?sort=column1&amp;sense=ASC" class="f ec-crud-ajax-link-auto" data-f="val" data-ec-crud-ajax-update="#myId">label1 <i>v</i></a></th>'],
            ['column2', 'column1', Crud::ASC, '<th class="d ec-crud-th ec-crud-th-sortable-not-active" data-d="val"><a href="/user?sort=column2" class="g ec-crud-ajax-link-auto" data-g="val" data-ec-crud-ajax-update="#myId">label2</a></th>'],
        ];
    }

    public function testThWithRenderOption(): void
    {
        $html = $this->crudExtension->th($this->environment, 'column1', $this->createCrud(), [
            'render' => 'render.html.twig',
        ]);

        $this->assertRegExp('/OK/', $html);
    }

    public function testThWithThemeAndBlockOptions(): void
    {
        $html = $this->crudExtension->th($this->environment, 'column1', $this->createCrud(), [
            'theme' => 'theme.html.twig',
            'block' => 'hello_world',
        ]);

        $this->assertRegExp('/Hello world/', $html);
    }

    public function testThWithBadOptions(): void
    {
        $this->expectException(UndefinedOptionsException::class);
        $this->crudExtension->th($this->environment, 'column1', $this->createCrud(), [
            'bad_option' => 'bad',
        ]);
    }

    public function testTdColumnNotDisplayed(): void
    {
        $html = $this->crudExtension->td($this->environment, 'column4', $this->createCrud(), 'value4');
        $this->assertSame('', $html);
    }

    public function testTdWithEscape(): void
    {
        $html = $this->crudExtension->td($this->environment, 'column1', $this->createCrud(), 'value1 é&');
        $this->assertSame('<td>value1 é&amp;</td>', $html);
    }

    public function testTdWithoutEscape(): void
    {
        $html = $this->crudExtension->td($this->environment, 'column1', $this->createCrud(), 'value1 é&', [
            'escape' => false,
        ]);
        $this->assertSame('<td>value1 é&</td>', $html);
    }

    public function testRepeatedValuesStringFirst(): CrudExtension
    {
        $html = $this->crudExtension->td($this->environment, 'column1', $this->createCrud(), 'value1', [
            'repeated_values_string' => '"',
        ]);
        $this->assertSame('<td>value1</td>', $html);

        return $this->crudExtension;
    }

    /**
     * @depends testRepeatedValuesStringFirst
     */
    public function testRepeatedValuesStringRepeat(CrudExtension $crudExtension): CrudExtension
    {
        $html = $crudExtension->td($this->environment, 'column1', $this->createCrud(), 'value1', [
            'repeated_values_string' => 'Bis',
        ]);
        $this->assertSame('<td title="value1">Bis</td>', $html);

        return $crudExtension;
    }

    /**
     * @depends testRepeatedValuesStringRepeat
     */
    public function testRepeatedValuesStringOtherColumn(CrudExtension $crudExtension): CrudExtension
    {
        $html = $crudExtension->td($this->environment, 'column2', $this->createCrud(), 'value1', [
            'repeated_values_string' => 'Bis',
        ]);
        $this->assertSame('<td>value1</td>', $html);

        return $crudExtension;
    }

    /**
     * @depends testRepeatedValuesStringOtherColumn
     */
    public function testRepeatedValuesStringOtherColumnRepeat(CrudExtension $crudExtension): CrudExtension
    {
        $html = $crudExtension->td($this->environment, 'column2', $this->createCrud(), 'value1', [
            'repeated_values_string' => 'Bis',
        ]);
        $this->assertSame('<td title="value1">Bis</td>', $html);

        return $crudExtension;
    }

    /**
     * @depends testRepeatedValuesStringOtherColumnRepeat
     */
    public function testRepeatedValuesStringRepeatWithMarkup(CrudExtension $crudExtension): CrudExtension
    {
        $markup = new Markup('value1', 'UTF-8');
        $html = $crudExtension->td($this->environment, 'column1', $this->createCrud(), $markup, [
            'repeated_values_string' => 'Bis',
        ]);
        $this->assertSame('<td title="value1">Bis</td>', $html);

        return $crudExtension;
    }

    /**
     * @depends testRepeatedValuesStringRepeatWithMarkup
     */
    public function testRepeatedValuesStringRepeatWithTitleAttr(CrudExtension $crudExtension): CrudExtension
    {
        $html = $crudExtension->td($this->environment, 'column1', $this->createCrud(), 'value1', [
            'repeated_values_string' => 'Bis',
            'td_attr' => ['title' => 'Repeated'],
        ]);
        $this->assertSame('<td title="Repeated">Bis</td>', $html);

        return $crudExtension;
    }

    /**
     * @depends testRepeatedValuesStringRepeatWithMarkup
     */
    public function testRepeatedValuesStringNotRepeat(CrudExtension $crudExtension): CrudExtension
    {
        $html = $crudExtension->td($this->environment, 'column1', $this->createCrud(), 'value2', [
            'repeated_values_string' => 'Bis',
        ]);
        $this->assertSame('<td>value2</td>', $html);

        return $crudExtension;
    }

    /**
     * @depends testRepeatedValuesStringNotRepeat
     */
    public function testRepeatedValuesWithEmptyValueFirst(CrudExtension $crudExtension): CrudExtension
    {
        $html = $crudExtension->td($this->environment, 'column1', $this->createCrud(), '', [
            'repeated_values_string' => 'Bis',
        ]);
        $this->assertSame('<td></td>', $html);

        return $crudExtension;
    }

    /**
     * @depends testRepeatedValuesWithEmptyValueFirst
     */
    public function testRepeatedValuesWithEmptyValueRepeat(CrudExtension $crudExtension): CrudExtension
    {
        $html = $crudExtension->td($this->environment, 'column1', $this->createCrud(), '', [
            'repeated_values_string' => 'Bis',
        ]);
        $this->assertSame('<td></td>', $html);

        return $crudExtension;
    }

    public function testTdWithAttrOptions(): void
    {
        $html = $this->crudExtension->td($this->environment, 'column1', $this->createCrud(), 'value1', [
            'td_attr' => ['class' => 'a', 'data-a' => 'val'],
        ]);
        $this->assertSame('<td class="a" data-a="val">value1</td>', $html);
    }

    public function testTdWithRenderOption(): void
    {
        $html = $this->crudExtension->td($this->environment, 'column1', $this->createCrud(), 'value1', [
            'render' => 'render.html.twig',
        ]);

        $this->assertRegExp('/OK/', $html);
    }

    public function testTdWithThemeAndBlockOptions(): void
    {
        $html = $this->crudExtension->td($this->environment, 'column1', $this->createCrud(), 'value1', [
            'theme' => 'theme.html.twig',
            'block' => 'hello_world',
        ]);

        $this->assertRegExp('/Hello world/', $html);
    }

    public function testTdWithBadOptions(): void
    {
        $this->expectException(UndefinedOptionsException::class);
        $this->crudExtension->td($this->environment, 'column1', $this->createCrud(), 'value1', [
            'bad_option' => 'bad',
        ]);
    }

    protected function createCrud(string $sort = 'column1', string $sense = Crud::ASC): Crud
    {
        $columns = [
            'column1' => new CrudColumn('column1', 'alias1', 'label1', true, true, 'alias1', 'alias1'),
            'column2' => new CrudColumn('column2', 'alias2', 'label2', true, false, 'alias2', 'alias2'),
            'column3' => new CrudColumn('column3', 'alias3', 'label3', false, true, 'alias3', 'alias3'),
            'column4' => new CrudColumn('column4', 'alias4', 'label4', true, true, 'alias4', 'alias4'),
        ];
        $crudSession = new CrudSession();
        $crudSession->displayedColumns = [
            'column1',
            'column2',
            'column3',
        ];
        $crudSession->sort = $sort;
        $crudSession->sense = $sense;

        $crud = $this->getMockBuilder(Crud::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDivIdList', 'getRouteName', 'getRouteParams', 'getSessionValues', 'getColumn'])
            ->getMock();
        $crud->expects($this->any())->method('getDivIdList')->willReturn('myId');
        $crud->expects($this->any())->method('getRouteName')->willReturn('user_crud');
        $crud->expects($this->any())->method('getRouteParams')->willReturn([]);
        $crud->expects($this->any())->method('getSessionValues')->willReturn($crudSession);
        $crud->expects($this->any())->method('getColumn')->willReturnCallback(fn ($columnId) => $columns[$columnId]);

        return $crud;
    }

    protected function createFormView(): FormView
    {
        $builder = $this->formFactory->createBuilder(FormType::class, null, [
            'action' => '/url',
            'method' => 'POST',
            'csrf_protection' => false,
        ]);

        return $builder->getForm()->createView();
    }

    protected function assertMatchesXpath(?string $html, string $expression, int $count = 1): void
    {
        $crawler = new Crawler($html);
        $this->assertCount($count, $crawler->filterXPath($expression));
    }
}
