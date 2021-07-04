# Création d'un CRUD

## Introduction

Supposons que notre projet possède ces 2 entités Doctrine :

```php
<?php
//src/Entity/Car
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
* @ORM\Entity
*/
class Car
{
    /**
    * @ORM\Id
    * @ORM\Column(type="integer")
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
    * @ORM\Column(type="string", length="255")
    */
    protected $name;

    /**
    *
    * @ORM\Column(type="date")
    * @Assert\DateTime()
    */
    protected $purchaseDate;

    /**
    *
    * @ORM\ManyToOne(targetEntity="Category")
    * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
    */
    protected $category;

    /**
    *
    * @ORM\Column(type="boolean")
    */
    protected $active;

    //Getters and Setters

    //...
}
```

```php
<?php
//src/Entity/Category
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
*/
class Category
{

    /**
    * @ORM\Id
    * @ORM\Column(type="integer")
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
    * @ORM\Column(type="string", length="255")
    */
    protected $name;

    public function __toString()
    {
        return $this->name;
    }

    //Getters and Setters

    //...
}
```

## Création du contrôleur

Nous devons créer une classe contrôleur héritant la classe abstraite `Ecommit\CrudBundle\Controller\AbstractCrudController`. 

Notre classe doit surcharger les méthodes abstraites :
* getCrud
* getTemplateName

```php
<?php
//src/Controller/MyCrudController
namespace App\Controller;

use App\Entity\Car;
use Ecommit\CrudBundle\Controller\AbstractCrudController;
use Ecommit\CrudBundle\Crud\Crud;
use Symfony\Component\Routing\Annotation\Route;

class MyCrudController extends AbstractCrudController
{
    protected function getCrud(): Crud
    {
        $em = $this->getDoctrine()->getManager();
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->from(Car::class, 'c1')
            ->select('c1, c2')
            ->innerJoin('c1.category', 'c2');
        
        $crud = $this->createCrud('my_crud'); //Passé en argument: Nom du CRUD
        $crud->addColumn('id', 'c1.id', 'Id')
            ->addColumn('name', 'c1.name', 'Name')
            ->addColumn('category', 'c2.name', 'Category', ['alias_search' => 'c2.id'])
            ->addColumn('purchase_date', 'c1.purchaseDate', 'Purchase date')
            ->addColumn('active', 'c1.active', 'Active')
            ->setQueryBuilder($queryBuilder)
            ->setAvailableResultsPerPage([2, 5, 10], 5)
            ->setDefaultSort('id', Crud::ASC)
            ->setRoute('my_crud_ajax')
            ->setPersistentSettings(true) //Enregistre les paramètres d'affichage en base de données. Par défaut: false
            ->init();

        return $crud;
    }
    
    protected function getTemplateName(string $action): string
    {
        return sprintf('my_crud/%s.html.twig', $action);
    }
    
    /**
     * @Route("/my-crud", name="my_crud")
     */
    public function crudAction()
    {
        return $this->getCrudResponse();
    }

    /**
     * @Route("/my-crud/ajax", name="my_crud_ajax")
     */
    public function ajaxCrudAction()
    {
        return $this->getAjaxCrudResponse();
    }
}
```

Explications de configCrud():

* Avec `$this->createCrud('my_crud')`, nous créons un CRUD (instance de `Ecommit\CrudBundle\Crud\Crud`) dont le nom est `my_crud`.
* Nous déclarons chaque colonne du CRUD par la méthode `addColumn()`. Cette méthode prend en paramètres :
    * Id de la colonne (Nous donnons un ID à la colonne). Cet ID est totalement indépendant des noms de colonnes DQL/SQL. **Dans ce document, à chaque fois que nous parlerons de l'ID d'une colonne, c'est ce paramètre qui sera concerné. Requis**
    * Alias Doctrine (du query builder) utilisé pour la requête Doctrine **Requis**
    * Label donné à la colonne (le label sera traduit si traduction est active sur le projet) **Requis**
    * Tableau d'options (facultatif):
        * **sortable**: Booléen qui définit si on active (ou non) le tri sur cette colonne **Défaut: True**
        * **default_displayed**: Booléen qui définit si on affiche (ou non) cette colonne par défaut **Défaut: True**
        * **alias_search**: Alias Doctrine utilisé lors de la recherche DQL/SQL. Si pas défini, utilise l'alias Doctrine défini en 2ème paramètre
        * **alias_sort**: Alias Doctrine (chaine de caractères ou tableau de chaines de caractères) utilisé(s) lors du tri sur cette colonne. Si pas défini, utilise l'alias Doctrine défini en 2ème paramètre
* Nous donnons la requête Doctrine (sous forme d'objet QueryBuilder ou d'une fonction anonyme) avec la méthode setQueryBuilder() Le moteur du CRUD modifiera automatiquement cette requête, en fonction des actions demandées par l'utilisateur
* Nous définissions les paramètres du nombre de pages avec la méthode `setAvailableResultsPerPage()`. Cette méthode prend 2 paramètres :
    * Un tableau contenant les différents nombres possibles du nombre de résultats par page. **Requis**
    * Le nombre de résultats par page, par défaut. **Requis**
* Nous définissions le tri par défaut par la méthode `setDefaultSort`. Cette méthode prend 2 paramètres :
    * L'id de la colonne utilisée pour le tri par défaut **Requis**
    * Le sens du tri par défaut (`Crud::ASC` ou `Crud::DESC`). **Requis**
* On définit par la fonction `setRoute` la route Ajax utilisée pour mettre à jour notre liste
* On active par la fonction `setPersistentSettings` l'enregistrement des propriétés d'affichage des utilisateurs en base de données. Par défaut, désactivé.
* On initialise le Crud par la méthode `init()`.
* On retourne notre objet créé

## Ajout templates

```twig
{# templates/my_crud/index.html.twig #}
{% extends 'layout.html.twig' %}

{% block content %}
    <div id="crud_list">
        {% include 'my_crud/list.html.twig' %}
    </div>
{% endblock %}
```

```twig
{# templates/my_crud/list.html.twig #}
{% if crud.paginator %}
    <div>
        <table class="result">
            <thead>
            <tr>
                {{ crud_th('id', crud) }}
                {{ crud_th('name', crud) }}
                {{ crud_th('category', crud) }}
                {{ crud_th('purchase_date', crud) }}
                {{ crud_th('active', crud) }}
            </tr>
            </thead>
            <tbody>
            {% for car in crud.paginator %}
                <tr>
                    {{ crud_td('id', crud, car.id) }}
                    {{ crud_td('name', crud, car.name) }}
                    {{ crud_td('category', crud, car.category.name) }}
                    {{ crud_td('purchase_date', crud, car.purchaseDate|date('Y-m-d')) }}
                    {{ crud_td('active', crud, (car.active) ? 'yes' : 'no') }}
                </tr>
            {% endfor %}
            </tbody>
        </table>
    </div>


    <div class="info-pagination">
        {{ 'pagination_count_results'|trans({'count': crud.paginator.countResults}) }}<br />
        {{ 'pagination_indices'|trans({'first': crud.paginator.firstIndice, 'last': crud.paginator.lastIndice}) }}  -
        {{ 'pagination_pages'|trans({'page': crud.paginator.page, 'lastPage': crud.paginator.lastPage}) }}
    </div>

    {{ crud_paginator_links(crud) }}
{% endif %}

{{ crud_display_settings(crud) }}
```

Vous pouvez ajouter par exemple les traductions suivantes (+intl-icu) à votre projet :

```yaml
# translations/messages+intl-icu.fr.yaml
pagination_count_results: >
    {count, plural,
        =0    {Pas de résultat}
        one   {1 résultat trouvé}
        other {# résultats trouvés}
    }
pagination_indices: Résultats {first}-{last}
pagination_pages: Page {page}/{lastPage}
```

## Ajout formulaire de recherche (FACULTATIF)

La classe Searcher représente les champs du formulaire de recherche.

Si vous ne désirez pas activer le formulaire de recherche sur le CRUD, passez ce paragraphe.

Note classe doit hériter de `Ecommit\CrudBundle\Form\Searcher\AbstractSearcher` (ou implémenter `Ecommit\CrudBundle\Form\Searcher\SearcherInterface`) :

```php
<?php
//src/Form/Searcher/CarSearcher
namespace App\Form\Searcher;

use App\Entity\Category;
use Ecommit\CrudBundle\Crud\SearchFormBuilder;
use Ecommit\CrudBundle\Form\Filter as Filter;
use Ecommit\CrudBundle\Form\Searcher\AbstractSearcher;

class CarSearcher extends AbstractSearcher
{
    public $id;

    public $name;

    public $nameEmpty;

    public $purchaseBeginningDate;

    public $purchaseEndDate;

    public $category;

    public $active;

    public function buildForm(SearchFormBuilder $builder, array $options): void
    {
        //La méthode addFilter a 3 arguments:
        //  - Le nom du filtre = Nom de la propriété dans cette classe (créer dans cette classe une données membre public ou avec des getters / setters
        //  - Le type du filtre à utiliser
        //  - Un tableau d'options (facultatif)

        //Le lien entre un filtre et une donnée membre de cette classe se fait par le 1er argument de la méthode addFilter.
        //Par défaut, le lien entre un filtre et une colonne du CRUD se fait par le 1er argument de la méthode addFilter.
        //Si le nom du filtre n'est pas identique à l'ID d'une colonne du CRUD, on peut spécifier pour chaque filtre l'ID de
        //la colonne du CRUD associée (voir exemples ci-dessous pour les filtres "nameEmpty", "purchaseBeginningDate" et "purchaseEndDate")

        $builder->addFilter('id', Filter\IntegerFilter::class, [
            'comparator' => Filter\IntegerFilter::EQUAL,
        ]);

        $builder->addFilter('name', Filter\TextFilter::class, [
            'must_begin' => true,
        ]);

        $builder->addFilter('nameEmpty', Filter\NullFilter::class, [
            //On ne souhaite pas relier le filtre "nameEmpty" à la colonne "nameEmpty" du CRUD (qui n'existe pas).
            //On précise donc manuellement l'ID de la colonne
            'column_id' => 'name',
            'type_options' => [
                'label' => 'Name empty',
            ],
        ]);

        $builder->addFilter('purchaseBeginningDate', Filter\DateFilter::class, [
            'comparator' => Filter\DateFilter::GREATER_EQUAL,
            'column_id' => 'purchase_date',
            'type_options' => [
                'label' => 'Purchase date - From'
            ],
        ]);

        $builder->addFilter('purchaseEndDate', Filter\DateFilter::class, [
            'comparator' => Filter\DateFilter::SMALLER_EQUAL,
            'column_id' => 'purchase_date',
            'type_options' => [
                'label' => 'Purchase date - To'
            ],
        ]);

        $builder->addFilter('active', Filter\BooleanFilter::class);

        $builder->addFilter('category', Filter\EntityFilter::class, [
            'class' => Category::class,
            'multiple' => 'true',
        ]);
    }
}
```

> **_REMARQUE:_**  La liste des différents filtres disponibles (ainsi que leurs configurations) est disponible [ici](references/filters.md)

> **_REMARQUE:_**  Il est aussi possible de faire des recherches plus complexes sans utiliser les filtres pré-définis. [En savoir plus](cookbook/advanced-searcher.md)

Une fois la classe Searcher créée, nous devons modifier notre contrôleur :

```diff
<?php
//src/Controller/MyCrudController
namespace App\Controller;

//...
+ use App\Form\Searcher\CarSearcher;

class MyCrudController extends AbstractCrudController
{
    protected function getCrud(): Crud
    {
        //...
        
        $crud = $this->createCrud('my_crud'); //Passé en argument: Nom du CRUD
        $crud->addColumn('id', 'c1.id', 'Id')
            //...
            ->setRoute('my_crud_ajax')
+           ->createSearchForm(new CarSearcher())
            //...
            ->init();

        return $crud;
    }
}
```

Nous devons ensuite créer un nouveau template Twig :

```twig
{# templates/my_crud/search.html.twig #}
{{ crud_search_form_start(crud) }}
    {{ form_row(crud.searchForm.id) }}
    {{ form_row(crud.searchForm.name) }}
    {{ form_row(crud.searchForm.nameEmpty) }}
    {{ form_row(crud.searchForm.purchaseBeginningDate) }}
    {{ form_row(crud.searchForm.purchaseEndDate) }}
    {{ form_row(crud.searchForm.active) }}
    {{ form_row(crud.searchForm.category) }}

    <div>
        {{ crud_search_form_submit(crud) }}
        {{ crud_search_form_reset(crud) }}
    </div>
</form>
```

Et enfin modifier le template principal :

```twig
{# templates/my_crud/index.html.twig #}
{% extends 'layout.html.twig' %}

{% block content %}
    <div id="crud_search">
        {% include 'my_crud/search.html.twig'  %}
    </div>

    <div id="crud_list">
        {% include 'my_crud/list.html.twig' %}
    </div>
{% endblock %}
```

> **_REMARQUE:_**  [En savoir plus sur la configuration du formulaire de recherche](references/searcher.md)



> **_REMARQUE:_**  [En savoir plus sur l'utilisation avancée du CRUD](index.md#fonctionnalités-avancées)
