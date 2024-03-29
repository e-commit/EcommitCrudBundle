# Création manuelle du paginator

Par défaut, un paginator `Ecommit\DoctrineUtils\Paginator\AbstractDoctrinePaginator` est automatiquement créé. Le comportement par défaut peut être modifié par
la méthode `setBuildPaginator`.

**Exemple 1 - Options de génération :**

```diff
<?php
//src/Controller/MyCrudController
namespace App\Controller;

//...

class MyCrudController extends AbstractCrudController
{
    protected getCrudOptions(): array
    {
        //...
        
        $crudConfig = $this->createCrudConfig('my_crud'); //Passé en argument: Nom du CRUD
        $crudConfig->addColumn(['id' => 'id', 'alias' => 'c1.id', 'label' => 'Id'])
            //...
            ->setRoute('my_crud_ajax')
+           ->setBuildPaginator([
+               //Options de Ecommit\DoctrineUtils\Paginator\DoctrinePaginatorBuilder::createDoctrinePaginator
+               'by_identifier' => 'c1.id',
+               'count' => [
+                   'behavior' => 'count_by_alias',
+                   'alias' => 'c1.id',
+               ],
+           ])
            //...

        return $crudConfig->getOptions();
    }

    //...
}
```

**Exemple 2 - Création manuelle du paginator :**

```diff
<?php
//src/Controller/MyCrudController
namespace App\Controller;

+ use Ecommit\DoctrineUtils\Paginator\DoctrineORMPaginator;

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
+           ->setBuildPaginator(function ($queryBuilder, $page, $resultsPerPage) {
+               $queryBuilder->andWhere('c1.active = 1');
+               $paginator = new DoctrineORMPaginator([
+                   'query_builder' => $queryBuilder,
+                   'page' => $page,
+                   'max_per_page' => $resultsPerPage,
+               ]);
+
+               return $paginator;
+           })
            //...

        return $crudConfig->getOptions();
    }

    //...
}
```
