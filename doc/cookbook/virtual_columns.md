# Colonnes virtuelles

Des colonnes virtuelles peuvent être ajoutées au CRUD. Une colonne virtuelle ne s'affiche pas dans la liste des
résultats mais les utilisateurs peuvent faire des recherches dessus.

Pour ajouter une colonne virtuelle, la méthode `addVirtualColumn` doit être utilisée.

Cette méthode prend comme paramètre un tableau d'options :
* **id** : Id de la colonne (Nous donnons un ID à la colonne). Cet ID est totalement indépendant des noms de colonnes DQL/SQL. **Dans ce document, à chaque fois que nous parlerons de l'ID d'une colonne, c'est ce paramètre qui sera concerné. Requis**
* **alias** : Alias Doctrine (du query builder) utilisé pour la requête Doctrine **Requis**

```diff
<?php
//src/Controller/MyCrudController
namespace App\Controller;

//...

class MyCrudController extends AbstractCrudController
{
    protected function getCrudOptions(): array
    {
        //...
        
        $crudConfig = $this->createCrudConfig('my_crud'); //Passé en argument: Nom du CRUD
        $crudConfig->addColumn(['id' => 'id', 'alias' => 'c1.id', 'label' => 'Id'])
            //...
+           ->addVirtualColumn(['id' => my_virtual_column', 'alias' => c1.name'])
            ->setRoute('my_crud_ajax')
            //...

        return $crudConfig->getOptions();
    }

    //...
}
```
