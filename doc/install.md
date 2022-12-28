# Installation

Prérequis :
* Projet Symfony fonctionnel
* Entité utilisateur avec Doctrine
* Yarn
* Webpack Encore
* jQuery
* Pour une compatibilité avec un maximum de navigateurs Internet, il est conseillé :
  * De configurer Webpack Encore pour activer Babel et core-js
  * D'utiliser un [polyfill pour Fetch](https://github.com/github/fetch)
  * D'utiliser un [polyfill pour FormData](https://github.com/jimmywarting/FormData)
* Un gestionnaire de thème chargé par Webpack Encore parmi :
    * Bootstrap 3
    * Bootstrap 4
    * Bootstrap 5
    * Votre thème personnalisé (créer un thème Twig qui hérite `@EcommitCrud/Theme/base.html.twig`)
* Un gestionnaire d'icones chargé par Webpack Encore parmi :
    * Fontawesome 4
    * Fontawesome 5 Solid
    * Fontawesome 6 Solid
    * Votre thème personnalisé (créer un thème Twig qui hérite `@EcommitCrud/IconTheme/base.html.twig`)

Installez le bundle avec Composer : A la racine de votre projet Symfony, éxécutez la commande suivante :

```bash
$ composer require ecommit/crud-bundle:3.*@dev
$ yarn add --dev @ecommit/crud-bundle@link:vendor/ecommit/crud-bundle/assets
```

Activez le bundle dans le fichier de configuration `config/bundles.php` de votre projet :

```php
return [
    //...
    Ecommit\CrudBundle\EcommitCrudBundle::class => ['all' => true],
    //...
];
```

Ajoutez à votre projet le fichier de configuration `config/packages/ecommit_crud.yaml` :

```yaml
ecommit_crud:
    #Theme
    #Themes disponibles :
    #@EcommitCrud/Theme/base.html.twig
    #@EcommitCrud/Theme/bootstrap3.html.twig (boostrap3 requis)
    #@EcommitCrud/Theme/bootstrap4.html.twig (boostrap4 requis)
    #@EcommitCrud/Theme/bootstrap5.html.twig (boostrap5 requis)
    #Ou faire son propre terme (doit hériter de l'un des thèmes précédents)
    theme: '@EcommitCrud/Theme/bootstrap5.html.twig'

    #Theme pour les icones
    #Themes disponibles :
    #@EcommitCrud/IconTheme/base.html.twig
    #@EcommitCrud/IconTheme/fontawesome4.html.twig (fontawesome4 requis)
    #@EcommitCrud/IconTheme/fontawesome5_solid.html.twig (fontawesome5 Solid requis)
    #@EcommitCrud/IconTheme/fontawesome6_solid.html.twig (fontawesome5 Solid requis)
    #Ou faire son propre terme (doit hériter de l'un des thèmes précédents)
    icon_theme: '@EcommitCrud/IconTheme/fontawesome6_solid.html.twig'
```

Votre entité Doctrine "utilisateur" doit implémenter l'interface `Ecommit\CrudBundle\Entity\UserCrudInterface`. Exemple :

```php
<?php

namespace App\Entity;

use Ecommit\CrudBundle\Entity\UserCrudInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, UserCrudInterface
{
    //...
}
```

La configuration Doctrine doit être adaptée en conséquence :

```yaml
#config/packages/doctrine.yaml

doctrine:
    orm:
        resolve_target_entities:
            #Adaptez App\Entity\User en fonction du nom de votre classe utilisateur
            Ecommit\CrudBundle\Entity\UserCrudInterface: App\Entity\User
```

Dans votre entrée principale [Webpack Encore](https://symfony.com/doc/current/frontend.html), rajoutez les instructions suivantes :

```js
//Exemple dans assets/js/app.js
import '@ecommit/crud-bundle/js/crud';
import * as modalManager from '@ecommit/crud-bundle/js/modal/modal-manager';
//Choix du moteur de modal (exemple avec Boostrap5)
//Bootstrap5 requis (et chargé via Webpack Encore)
var modalEngine = require('@ecommit/crud-bundle/js/modal/engine/bootstrap5');
modalManager.defineEngine(modalEngine);
```

Recompiliez avec Webpack Encore:

```bash
yarn encore dev
```

Mettez à jour vos entités Doctrine :

```bash
php bin/console doctrine:schema:update --force

#Ou si vous utilisez Doctrine Migrations:
#php bin/console doctrine:migrations:diff
#php bin/console doctrine:migrations:migrate
```
