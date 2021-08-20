# Callbacks JavaScript

## Définition des callbacks

**Méthode 1 :**

```js
var callback = function (arg) {
    alert('go');
    //...
};
```

**Méthode 2 :**

```js
import * as callbackManager from '@ecommit/crud-bundle/js/callback-manager';

callbackManager.registerCallback('my_callback_name', function (arg) {
    alert('go');
});
```


**Gestion de plusieurs callbacks :**

```js
var callbacks = [
    //Callback 1
    function (arg) {
        alert('go');
        //...
    },
    
    //Callback 2
    'my_callback_name' //Callback enregistré par nom (voir méthode 2 plus haut)
];

//Gestion des priorités (priorité par défaut = 0)
var callbacks = [
    //Callback 1
    {
        callback: function (arg) {
            alert('go');
            //...
        },
        priority: 10
    },
    
    //Callback 2
    {
        callback: 'my_callback_name', //Callback enregistré par nom (voir méthode 2 plus haut)
        priority: 20
    }
];
```


## Appel des callbacks

La fonction `runCallback` doit être appelée avec comme arguments :
* Callback ou liste de callbacks
* Argument passé à l'appel de chaque callback


Le premier argument de la fonction `runCallback` est un callback ou liste de callbacks. Un callback (unique ou parmi le tableau) peut être :
* 1 callback JavaScript (voir méthode 1 plus haut)
* 1 nom de callback enregistré par par la méthode `registerCallback` (voir méthode 2 plus haut)
* 1 objet avec les propriétés `callback` et `priority` (voir plus haut)


```js
import runCallback from '@ecommit/crud-bundle/js/callback';

//Exemple avec un unique callback
//Voir paragraphe précédent
//var callback = 
runCallback(callback, '5');

//Exemple avec un tableau de callbacks
var callbacks = [
    //... Voir paragraphe précédent
];
runCallback(callbacks, '5');
```
