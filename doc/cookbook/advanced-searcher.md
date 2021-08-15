# Personnalisation avancée de la classe Searcher

## Solution 1: Utilisation de addField et updateQueryBuilder

```php
<?php
//src/Form/Searcher/CarSearcher
namespace App\Form\Searcher;

use Ecommit\CrudBundle\Crud\SearchFormBuilder;
use Ecommit\CrudBundle\Form\Filter as Filter;
use Ecommit\CrudBundle\Form\Searcher\AbstractSearcher;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CarSearcher extends AbstractSearcher
{
    public $id;

    public $name;

    public function buildForm(SearchFormBuilder $builder, array $options): void
    {
        //Filtre habituel
        $builder->addFilter('id', Filter\IntegerFilter::class, [
            'comparator' => Filter\IntegerFilter::EQUAL,
        ]);

        //Ajout manuel d'un champ dans filtre de recherche
        $builder->addField('name', TextType::class, [
            'required' => false,
        ]);
    }
    
    public function updateQueryBuilder($queryBuilder, array $options): void
    {
        //Traitement du filtre de recherche "name"
        if (null !== $this->name) {
            $queryBuilder->andWhere('c1.name = :name')
                ->setParameter('name', $this->name);
        }
    }
}
```

## Solution 2: Utilisation d'une classe FormType

```php
<?php
//src/Form/Searcher/CarSearcher
namespace App\Form\Searcher;

use Ecommit\CrudBundle\Form\Searcher\AbstractSearcher;
use Symfony\Component\Validator\Constraints as Assert;

class CarSearcher extends AbstractSearcher
{
    /**
     * @Assert\Length(max=255)
     */
    public $name;

    public function updateQueryBuilder($queryBuilder, array $options): void
    {
        //Traitement du filtre de recherche "name"
        if (null !== $this->name) {
            $queryBuilder->andWhere('c1.name = :name')
                ->setParameter('name', $this->name);
        }
    }
}
```

> **_REMARQUE:_**  Pensez à la validation de vos champs.


```php
<?php
//src/Form/Type/CarSearcherType
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class CarSearcherType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addField('name', TextType::class, [
            'required' => false,
        ]);
    }
}
```

```diff
<?php
//src/Controller/MyCrudController
namespace App\Controller;

//...
+ use App\Form\Type\CarSearcherType;

class MyCrudController extends AbstractCrudController
{
    protected function getCrud(): Crud
    {
        //...
        
        $crud = $this->createCrud('my_crud'); //Passé en argument: Nom du CRUD
        $crud->addColumn('id', 'c1.id', 'Id')
            //...
            ->setRoute('my_crud_ajax')
-           ->createSearchForm(new CarSearcher())
+           ->createSearchForm(new CarSearcher(), CarSearcherType::class , [
+               //Options
+           ])
            //...
            ->init();

        return $crud;
    }
}
```