# Personnaliser les IDs des Divs

Par défaut, les IDs des DIVs de liste et recherche sont :

| DIV | ID |
| --- | --- |
| DIV liste des résultats | crud_list |
| DIV formulaire de recherche | crud_search |

Ces IDs peuvent être changés par les méthodes `setDivIdList` et `setDivIdSearch`:

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
+           ->setDivIdList('my_div1')
+           ->setDivIdSearch('my_div2')
            //...

        return $crudConfig->getOptions();
    }

    //...
}
```
