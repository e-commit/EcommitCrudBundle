# Configuration par défaut des templates des CRUD

Il est possible de définir les options par défaut des fonctions Twig suivantes :
* paginator_links
* crud_paginator_links
* crud_th
* crud_td
* crud_display_settings
* crud_search_form_start
* crud_search_form_submit
* crud_search_form_reset

Ces options par défaut peuvent être définies :
* Pour l'application
* Pour un CRUD


L'ordre de priorité prise en compte pour les options est le suivant :
* Options définies lors de l'appel de la méthode Twig
* Options définies dans les options du CRUD
* Options définies dans l'application
* Options par défaut de EcommitCrudBundle

## Options définies dans l'application

```yaml
#config/packages/ecommit_crud.yaml
ecommit_crud:
    twig_functions_configuration:
        #Nom de la fonction Twig
        crud_td:
            #Options par défaut
            escape: false
```

## Options définies dans le CRUD

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
+           ->setTwigFunctionsConfiguration([
+               //Nom de la fonction Twig
+               'crud_td' => [
+                   //Options par défaut
+                   'escape' => false,
+               ],
+           ])
            //...

        return $crudConfig->getOptions();
    }

    //...
}
```
