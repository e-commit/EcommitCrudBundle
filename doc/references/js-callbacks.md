# Callbacks JavaScript

## Définition des callbacks

```js
//Methode 1
var callback = function (arg) {
    alert('go');
    //...
};

//Methode 2
var callback = 'alert("go")';

//Méthode 3
var callback = 'function (arg) { alert("go"); }';
```


Gestion de plusieurs callbacks :

```js
var callbacks = [
    //Callback 1
    function (arg) {
        alert('go');
        //...
    },
    
    //Callback 2
    'alert("go")'
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
        callback: 'alert("go")',
        priority: 20
    }
];
```


## Appel des callbacks

```js
import runCallback from '@ecommit/crud-bundle/js/callback';

var callbacks = [
    //... Voir paragraphe précédent
];

//1er argument: Callback ou liste de callbacks
//2ème argument: Argument passé à l'appel de chaque callback
runCallback(callbacks, '5');
```
