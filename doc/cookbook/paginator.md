# Création manuelle du paginator

Par défaut, un paginator `Ecommit\CrudBundle\Paginator\AbstractDoctrinePaginator` est automatiquement créé. Le comportement par défaut peut être modifié par
la méthode `setBuildPaginator`.

**Exemple 1 - Options de génération :**

```diff
<?php
//src/Controller/MyCrudController
namespace App\Controller;

//...

class MyCrudController extends AbstractCrudController
{
    protected function getCrud(): Crud
    {
        //...
        
        $crud = $this->createCrud('my_crud'); //Passé en argument: Nom du CRUD
        $crud->addColumn('id', 'c1.id', 'Id')
            //...
            ->setRoute('my_crud_ajax')
+           ->setBuildPaginator([
+               //Options de Ecommit\CrudBundle\DoctrineExtension\Paginate::createDoctrinePaginator
+               'behavior' => 'identifier_by_sub_request',
+               'identifier' => 'c1.id',
+               'count_options' => [
+                   'behavior' => 'count_by_alias',
+                   'alias' => 'c1.id',
+               ],
+           ])
            //...
            ->init();

        return $crud;
    }

    //...
}
```

**Exemple 2 - Création manuelle du paginator :**

```diff
<?php
//src/Controller/MyCrudController
namespace App\Controller;

+ use Ecommit\CrudBundle\Paginator\DoctrineORMPaginator;

//...

class MyCrudController extends AbstractCrudController
{
    protected function getCrud(): Crud
    {
        //...
        
        $crud = $this->createCrud('my_crud'); //Passé en argument: Nom du CRUD
        $crud->addColumn('id', 'c1.id', 'Id')
            //...
            ->setRoute('my_crud_ajax')
+           ->setBuildPaginator(function ($queryBuilder, $page, $resultsPerPage) {
+               $paginator = new DoctrineORMPaginator($resultsPerPage);
+               
+               $queryBuilder->andWhere('c1.active = 1');
+               $paginator->setQueryBuilder($queryBuilder);
+               $paginator->setPage($page);
+               $paginator->init();
+
+               return $paginator;
+           })
            //...
            ->init();

        return $crud;
    }

    //...
}
```
