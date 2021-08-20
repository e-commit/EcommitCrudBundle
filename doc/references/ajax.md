# Fonctions Ajax

## Fonctions

### sendRequest

Fonction permettant de faire une requête AJAX.

```js
import * as ajax from '@ecommit/crud-bundle/js/ajax';

ajax.sendRequest({
    url: '/test',
    //Options
});
```

Options :

| Option | Description | Requis  | Valeur par défaut |
| ------ | ----------- | --------| ----------------- |
| url | Url de l'action Ajax | Oui |  |
| update | Si défini, mise à jour du DOM avec le résultat. (voir doc de la fonction `updateDom` plus bas) | Non | |
| updateMode | Méthode à utiliser pour la mise à jour du DOM (voir doc de la fonction `updateDom` plus bas) | Non | update |
| onBeforeSend | [Callback(s)](js-callbacks.md#définition-des-callbacks) lancé(s) avant l'envoi de la requête. `Function(options)`. Dans le callback peut passer `options.stop = false` pour annuler la requête. | Non | |
| onSuccess | [Callback(s)](js-callbacks.md#définition-des-callbacks) lancé(s) en cas de succès de la réponse. `Function(data, textStatus, jqXHR)` | Non | |
| onError | [Callback(s)](js-callbacks.md#définition-des-callbacks) lancé(s) en cas d'erreur lors de la réponse. `Function(jqXHR, textStatus, errorThrown)` | Non | |
| onComplete | [Callback(s)](js-callbacks.md#définition-des-callbacks) lancé(s) après réponse. `Function(jqXHR, textStatus)` | Non | |
| dataType | Type de données attendu (html, xml, json, jsonp, text) | Non | html |
| method | Méthode HTTP | Non | POST |
| data | Données envoyées dans la requête | Non | |
| cache | Utilise ou non le cache | Non | false |
| options | Tableau d'options de la fonction `ajax` de jQuery | Non | { } |

### click

Fonction permettant de faire une requête AJAX lors d'un clic sur élément du DOM.

L'élément du DOM doit avoir comme classe CSS `ec-crud-ajax-click-auto`.

Toutes les options de la fonction `sendRequest` peuvent être utilisées en les passant par les attributs `data-`. Pour cela:
* Préfixer chaque option par `data-ec-crud-ajax-`
* Les options d'origine (en JavaScript) de `sendRequest` sont en camelCase. Pour leur écriture par des attributs HTML, remplacer chaque nouveau mot en majuscule par un tiret.
Exemple: L'équivalent de l'option `updateMode` est `data-ec-crud-ajax-update-mode` en attribut HTML.

Exemple :

```html
<button class="ec-crud-ajax-click-auto" data-ec-crud-ajax-url="/goodRequest">Go !</button>
```

### link

Fonction permettant de faire une requête AJAX lors d'un clic sur lien.

Toutes les options de la fonction `sendRequest` peuvent être utilisées en les passant par les attributs `data-`. Pour cela:
* Préfixer chaque option par `data-ec-crud-ajax-`
* Les options d'origine (en JavaScript) de `sendRequest` sont en camelCase. Pour leur écriture par des attributs HTML, remplacer chaque nouveau mot en majuscule par un tiret.
  Exemple: L'équivalent de l'option `updateMode` est `data-ec-crud-ajax-update-mode` en attribut HTML.

#### Mode automatique

Le lien doit avoir comme classe CSS `ec-crud-ajax-link-auto`.

Exemple :

```html
<a href="/goodRequest" class="ec-crud-ajax-link-auto">Go !</a>
```

L'URL utilisée pour la requête Ajax est:
* La valeur de l'attribut `data-ec-crud-ajax-url` (si présent)
* Ou la valeur de `href`

#### Mode manuel

```html
<a href="/goodRequest" id="linkToTest">Go !</a>
```

```js
import * as ajax from '@ecommit/crud-bundle/js/ajax';

//Argument n°1: Lien
//Argument n°2: Options de sendRequest
ajax.link($('#linkToTest'), {
    //Options de sendRequest
    method: 'GET',
});
```

* L'URL utilisée pour la requête Ajax est:
    * La valeur de l'attribut `data-ec-crud-ajax-url` (si présent)
    * Ou la valeur de l'option `url` de la fonction `link` (si présent)
    * Ou la valeur de `href`
* Les attributs `data-ec-crud-ajax-*` (si présents) écrasent les options de la fonction `link` (si présent)

### sendForm

Fonction permettant de faire une requête AJAX depuis l'envoi d'un formulaire.

Toutes les options de la fonction `sendRequest` peuvent être utilisées en les passant par les attributs `data-`. Pour cela:
* Préfixer chaque option par `data-ec-crud-ajax-`
* Les options d'origine (en JavaScript) de `sendRequest` sont en camelCase. Pour leur écriture par des attributs HTML, remplacer chaque nouveau mot en majuscule par un tiret.
  Exemple: L'équivalent de l'option `updateMode` est `data-ec-crud-ajax-update-mode` en attribut HTML.

#### Mode automatique

Le formulaire doit avoir comme classe CSS `ec-crud-ajax-form-auto`.

Exemple :

```html
<form action="/goodRequest" method="POST" class="ec-crud-ajax-form-auto">
```

* L'URL utilisée pour la requête Ajax est:
    * La valeur de l'attribut `data-ec-crud-ajax-url` (si présent)
    * Ou la valeur de `action`
* La méthode utilisée pour la requête Ajax est:
    * La valeur de l'attribut `data-ec-crud-ajax-method` (si présent)
    * Ou la valeur de `method`
* Les données envoyées lors de la requête Ajax sont:
    * La valeur de l'attribut `data-ec-crud-ajax-data` (si présent)
    * Ou les données du formulaire

#### Mode manuel

```html
<form action="/goodRequest" method="POST" id="formToTest">
```

```js
import * as ajax from '@ecommit/crud-bundle/js/ajax';

//Argument n°1: Formulaire
//Argument n°2: Options de sendRequest
ajax.sendForm($('#formToTest'), {
    //Options de sendRequest
    cache: true,
});
```

* L'URL utilisée pour la requête Ajax est:
    * La valeur de l'attribut `data-ec-crud-ajax-url` (si présent)
    * Ou la valeur de l'option `url` de la fonction `sendForm` (si présent)
    * Ou la valeur de `action`
* La méthode utilisée pour la requête Ajax est:
    * La valeur de l'attribut `data-ec-crud-ajax-method` (si présent)
    * Ou la valeur de l'option `method` de la fonction `sendForm` (si présent)
    * Ou la valeur de `method`
* Les données envoyées lors de la requête Ajax sont:
    * La valeur de l'attribut `data-ec-crud-ajax-data` (si présent)
    * Ou la valeur de l'option `data` de la fonction `sendForm` (si présent)
    * Ou les données du formulaire
* Les attributs `data-ec-crud-ajax-*` (si présents) écrasent les options de la fonction `sendForm` (si présent)


### updateDom

Permet la mise à jour du DOM.

```js
import * as ajax from '@ecommit/crud-bundle/js/ajax';

//Argument n°1: Element à mettre à jour
//Argument n°2: Méthode de mise à jour
//Argument n°3: Contenu
ajax.updateDom($('#myDiv'), 'update', 'Hello world');
```

Méthodes disponibles pour la mise à jour :

| Méthode | Description |
| ------- | ----------- |
| update | Utilise le fonction  [`html` de jQuery](https://api.jquery.com/html/) |
| before | Utilise le fonction  [`before` de jQuery](https://api.jquery.com/before/) |
| after | Utilise le fonction  [`after` de jQuery](https://api.jquery.com/after/) |
| prepend | Utilise le fonction  [`prepend` de jQuery](https://api.jquery.com/prepend/) |
| append | Utilise le fonction  [`append` de jQuery](https://api.jquery.com/append/) |
