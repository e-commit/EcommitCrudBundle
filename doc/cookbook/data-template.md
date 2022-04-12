# Ajout de données aux templates

Par défaut, un contrôleur héritant de `AbstractCrudController` ou utilisant `CrudControllerTrait` retourne aux templates l'objet Crud (nom de variable Twig: `crud`).

Pour passer des variables supplémentaires aux templates Twig, peuvent être surchargées les méthodes :
* `beforeBuild` : Ajouter des données avant la génération de la requête Doctrine et du paginator
* `afterBuild` : Ajouter des données après la génération de la requête Doctrine et du paginator

Exemple :

```php
<?php
//src/Controller/MyCrudController
namespace App\Controller;

use Ecommit\CrudBundle\Controller\AbstractCrudController;

class MyCrudController extends AbstractCrudController
{
    //...

    protected function beforeBuild(Crud $crud, array $data): array
    {
        $data['my_var'] = 'my value';
        
        return $data;
    }
    
}
