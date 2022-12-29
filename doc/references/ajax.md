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

**Options :**

| Option                     | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         | Requis | Valeur par défaut |
|----------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------------------|
| url                        | Url de l'action Ajax                                                                                                                                                                                                                                                                                                                                                                                                                                                                                | Oui    |                   |
| update                     | Si défini, mise à jour du DOM avec le résultat. (voir doc de la fonction `updateDom` plus bas)                                                                                                                                                                                                                                                                                                                                                                                                      | Non    |                   |
| updateMode                 | Méthode à utiliser pour la mise à jour du DOM (voir doc de la fonction `updateDom` plus bas)                                                                                                                                                                                                                                                                                                                                                                                                        | Non    | update            |
| onBeforeSend               | [Callback(s)](js-callbacks.md#définition-des-callbacks) lancé(s) avant l'envoi de la requête. `Function(options)`. Dans le callback peut passer `options.stop = false` pour annuler la requête.                                                                                                                                                                                                                                                                                                     | Non    |                   |
| onSuccess                  | [Callback(s)](js-callbacks.md#définition-des-callbacks) lancé(s) en cas de succès (code de réponse HTTP 200-299). `Function(data, response)`. <ul><li>`data`: Donnée de la réponse. Voir option `responseDataType` pour le format attendu</li><li>`response`: Objet de type [Response](https://developer.mozilla.org/en-US/docs/Web/API/Response)</li></ul>                                                                                                                                         | Non    |                   |
| onError                    | [Callback(s)](js-callbacks.md#définition-des-callbacks) lancé(s) en cas d'erreur (code de réponse HTTP ≠ 200-299 ou erreur avant la réponse). `Function(statusText, response)` <ul><li>`statusText`: [statusText](https://developer.mozilla.org/en-US/docs/Web/API/Response/statusText) de la réponse (si réponse). Sinon mesage d'erreur au format string</li><li>`response`: Objet de type [Response](https://developer.mozilla.org/en-US/docs/Web/API/Response) si réponse, nulm sinon</li></ul> | Non    |                   |
| onComplete                 | [Callback(s)](js-callbacks.md#définition-des-callbacks) lancé(s) après les callbacks `onSuccess` ou `onError`. `Function(statusText, response)`. Voir options `onSuccess` et `onError` pour détails sur `statusText` et `response`                                                                                                                                                                                                                                                                  | Non    |                   |
| successfulResponseRequired | En cas de code de réponse HTTP ≠ 200-299: <ul><li>Si cette option est vraie, la promesse est alors rejetée</li><li>Sinon elle est résolue</ul>                                                                                                                                                                                                                                                                                                                                                      | Non    | false             |
| responseDataType           | Format de donnée de réponse attendu dans le callback `OnSuccess`. Valeurs disponibles: <ul><li>`text`: Format string</li><li>`json`: Objet JavaScript converti depuis une réponse JSON</li><li>Autre valeur: NULL (donnée de réponse à récupérer manuellement dans l'objet Response)</li></ul>                                                                                                                                                                                                      | Non    | text              |
| method                     | Méthode HTTP                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        | Non    | POST              |
| body                       | Données envoyées dans le corps de la requête. Types de données acceptés: <ul><li>String</li><li>[FormData](https://developer.mozilla.org/en-US/docs/Web/API/FormData)</li><li>Objet</li></ul>                                                                                                                                                                                                                                                                                                       | Non    |                   |
| query                      | Paramètres à rajouter dans l'URL                                                                                                                                                                                                                                                                                                                                                                                                                                                                    | Non    | { }               |
| cache                      | Utilise ou non le cache                                                                                                                                                                                                                                                                                                                                                                                                                                                                             | Non    | false             |
| options                    | Tableau d'options de la fonction [Fetch](https://developer.mozilla.org/en-US/docs/Web/API/fetch)                                                                                                                                                                                                                                                                                                                                                                                                    | Non    | { }               |

**Promesses :**

La fonction `sendRequest` retourne une promesse.
* En cas de code de réponse HTTP 200-299, la promesse résout l'objet [Response](https://developer.mozilla.org/en-US/docs/Web/API/Response) représentant la réponse à la requête.
* En cas de code de réponse HTTP autre 200-299:
  * Si l'option `successfulResponseRequired` est à `false`, alors la promesse résout l'objet [Response](https://developer.mozilla.org/en-US/docs/Web/API/Response) représentant la réponse à la requête.
  * Sinon la promesse est rejetée.
* En cas d'annulation de la requête (par le callback `onBeforeSend` ou les événements `ec-crud-ajax` / `ec-crud-ajax-before-send`), la promesse résout une valeur nulle.
* En cas d'erreur lors de l'exécution de la requête, la promesse est rejetée.
* En cas d'erreur lors la lecture de la réponse, la promesse est rejetée.
* En cas d'erreur de configuration, la promesse est rejetée.


**Événements :**

| Événement                | Objet                                                                                    | Description                                                                                                                    | Propriétés disponibles                                                    |
|--------------------------|------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------|
| ec-crud-ajax             | [CustomEvent](https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent) (cancelable) | Appelé avant l'exécution de la requête, avant la résolution des options. Peut annuler la requête avec `event.preventDefault()` | <ul><li>event.details.options</li></ul>                                   |
| ec-crud-ajax-before-send | [CustomEvent](https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent) (cancelable) | Appelé avant l'exécution de la requête, après la résolution des options. Peut annuler la requête avec `event.preventDefault()` | <ul><li>event.details.options</li></ul>                                   |
| ec-crud-ajax-on-success  | [CustomEvent](https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent)              | Même fonctionnement que callback `onSuccess`                                                                                   | <ul><li>event.details.data</li><li>event.details.response</li></ul>       |
| ec-crud-ajax-on-error    | [CustomEvent](https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent)              | Même fonctionnement que callback `onError`                                                                                     | <ul><li>event.details.statusText</li><li>event.details.response</li></ul> |
| ec-crud-ajax-on-complete | [CustomEvent](https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent)              | Même fonctionnement que callback `onComplete`                                                                                  | <ul><li>event.details.statusText</li><li>event.details.response</li></ul> |



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
* La fonction retourne la promesse générée par `sendRequest`

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
* La fonction retourne la promesse générée par `sendRequest`


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

| Méthode | Description                                                             |
|---------|-------------------------------------------------------------------------|
| update  | Modifie le contenu de l'élément par le nouveau contenu                  |
| before  | Ajoute le nouveau contenu avant l'élément                               |
| after   | Ajoute le nouveau contenu après l'élément                               |
| prepend | Modifie le contenu de l'élément en ajoutant le nouveau contenu au début |
| append  | Modifie le contenu de l'élément en ajoutant le nouveau contenu à la fin |

La mise à jour du DOM utilise la fonction [innerHTML](https://developer.mozilla.org/en-US/docs/Web/API/Element/innerHTML).
Si le nouveau contenu contient des balises `<script>`, celles-ci ne seront pas exécutées. Cependant, cele ne protège pas
contre les failles XSS. [En savoir plus](https://developer.mozilla.org/en-US/docs/Web/API/Element/innerHTML#security_considerations)
