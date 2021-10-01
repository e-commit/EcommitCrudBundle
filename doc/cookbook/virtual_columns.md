# Colonnes virtuelles

Des colonnes virtuelles peuvent être ajoutées au CRUD. Une colonne virtuelle ne s'affiche pas dans la liste des
résultats mais les utilisateurs peuvent faire des recherches dessus.

Pour ajouter une colonne virtuelle, la méthode `addVirtualColumn` doit être utilisée.

Cette méthode prend comme paramètres:
* Id de la colonne **Requis**
* Alias Doctrine utilisé lors de la recherche SQL. **Requis**

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
+           ->addVirtualColumn('my_virtual_column', 'c1.name')
            ->setRoute('my_crud_ajax')
            //...

        return $crud;
    }

    //...
}
```
