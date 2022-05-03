# Tri par défaut personnalisé

La méthode `setDefaultSort` permet  de définir le tri par défaut :

```php
<?php
//src/Controller/MyCrudController
namespace App\Controller;

use Ecommit\CrudBundle\Crud\Crud;
//...

class MyCrudController extends AbstractCrudController
{
    protected function getCrudOptions(): array
    {
        //...
        
        $crudConfig = $this->createCrudConfig('my_crud'); //Passé en argument: Nom du CRUD
        $crudConfig->addColumn(['id' => 'id', 'alias' => 'c1.id', 'label' => 'Id'])
            //...
            ->setRoute('my_crud_ajax')
            ->setDefaultSort('id', Crud::ASC)
            //...

        return $crudConfig->getOptions();
    }

    //...
}
```

Sur deux colonnes (ici va trier sur c1.id puis c1.name) :

```php
<?php
//src/Controller/MyCrudController
namespace App\Controller;

use Ecommit\CrudBundle\Crud\Crud;
//...

class MyCrudController extends AbstractCrudController
{
    protected function getCrudOptions(): array
    {
        //...
        
        $crudConfig = $this->createCrudConfig('my_crud'); //Passé en argument: Nom du CRUD
        $crudConfig->addColumn(['id' => 'id', 'alias' => 'c1.id', 'label' => 'Id',  'alias_sort' => ['c1.id', 'c1.name']])
            //...
            ->setRoute('my_crud_ajax')
            ->setDefaultSort('id', Crud::ASC)
            //...

        return $crudConfig->getOptions();
    }

    //...
}
```

Il est aussi possible de définir un tri par défaut personnalisé grâce à la méthode `setDefaultPersonalizedSort` :

```php
<?php
//src/Controller/MyCrudController
namespace App\Controller;

use Ecommit\CrudBundle\Crud\Crud;
//...

class MyCrudController extends AbstractCrudController
{
    protected function getCrudOptions(): array
    {
        //...
        
        $crudConfig = $this->createCrudConfig('my_crud'); //Passé en argument: Nom du CRUD
        $crudConfig->addColumn(['id' => 'id', 'alias' => 'c1.id', 'label' => 'Id'])
            //...
            ->setRoute('my_crud_ajax')
            ->setDefaultPersonalizedSort([
                'c1.purchaseDate' => Crud::DESC,
                'c1.id' => Crud::ASC,
            ])
            //...

        return $crudConfig->getOptions();
    }

    //...
}
```
