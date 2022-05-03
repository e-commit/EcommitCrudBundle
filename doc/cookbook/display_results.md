# Affichage des résultats uniquement si recherche envoyée

Par défaut, le CRUD affiche les résultats même si le formulaire de recherche n'a pas été utilisé.

Il est possible d'afficher les résultats uniquement si et seulement si :
* le formulaire de recherche a été envoyé 
* les données du formulaire de recherche sont valides

Pour cela, il suffit de passer la valeur `true` à la méthode `setDisplayResultsOnlyIfSearch` :

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
            ->setRoute('my_crud_ajax')
+           ->setDisplayResultsOnlyIfSearch(true)
            //...

        return $crudConfig->getOptions();
    }

    //...
}
```
