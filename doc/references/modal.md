# Gestionnaire de fenêtre modale

## Définition modale

Avant d'utiliser une fenêtre modale, vous devez manuellement ajouter le code source HTML de celle-ci.

Exemple avec Bootstrap:

```html
<div id="main_modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        </div>
    </div>
</div>
```

## Ouverture fenêtre modale

### Ouverture manuelle

```js
import * as modalManager from '@ecommit/crud-bundle/js/modal/modal-manager';

modalManager.openModal({
    //Options
    element: '#main_modal'
});
```

Options disponibles :

| Option | Description | Requis  | Valeur par défaut |
| ------ | ----------- | --------| ----------------- |
| element | Modal (élement du DOM) | Oui |  |
| onOpen | [Callback(s)](js-callbacks.md#définition-des-callbacks) lancé(s) avant l'ouverture de la modale | Non | |
| onClose | [Callback(s)](js-callbacks.md#définition-des-callbacks) lancé(s) avant la fermeture de la modale | Non | |


### Ouverture automatique

Ouverture de la fenêtre modale lors d'un clic sur un élément du DOM. 
L'élément du DOM doit avoir comme classe CSS `ec-crud-modal-auto`.

Toutes les options de la fonction `openModal` peuvent être utilisées en les passant par les attributs `data-`. Pour cela:
* Préfixer chaque option par `data-ec-crud-modal-`
* Les options d'origine (en JavaScript) de `openModal` sont en camelCase. Pour leur écriture par des attributs HTML, remplacer chaque nouveau mot en majuscule par un tiret.
  Exemple: L'équivalent de l'option `onOpen` est `data-ec-crud-modal-on-open` en attribut HTML.

Exemple :

```html
<a href="#" class="ec-crud-modal-auto" data-ec-crud-modal-element="#main_modal">Go !</a>
```

## Ouverture fenêtre modale avec Ajax

### Ouverture manuelle

```js
import * as modalManager from '@ecommit/crud-bundle/js/modal/modal-manager';

modalManager.openRemoteModal({
    //Options
    url: '/goodRequest',
    element: '#main_modal',
    elementContent: '#main_modal .modal-content'
});
```

Options disponibles :

| Option | Description | Requis  | Valeur par défaut |
| ------ | ----------- | --------| ----------------- |
| url | Url de l'action Ajax | Oui |  |
| element | Modal (élement du DOM) | Oui |  |
| elementContent | Element du DOM à mettre à jour | Oui | |
| onOpen | [Callback(s)](js-callbacks.md#définition-des-callbacks) lancé(s) avant l'ouverture de la modale | Non | |
| onClose | [Callback(s)](js-callbacks.md#définition-des-callbacks) lancé(s) avant la fermeture de la modale | Non | |
| method | Méthode HTTP | Non | POST |
| ajaxOptions | Options Ajax (voir [sendRequest](ajax.md#sendrequest) | Non | { } |


### Ouverture automatique

Ouverture de la fenêtre modale lors d'un clic sur un bouton ou un lien.
Le bouton ou le lien doit avoir comme classe CSS `ec-crud-remote-modal-auto`.

Toutes les options de la fonction `openRemoteModal` peuvent être utilisées en les passant par les attributs `data-`. Pour cela:
* Préfixer chaque option par `data-ec-crud-modal-`
* Les options d'origine (en JavaScript) de `openRemoteModal` sont en camelCase. Pour leur écriture par des attributs HTML, remplacer chaque nouveau mot en majuscule par un tiret.
  Exemple: L'équivalent de l'option `onOpen` est `data-ec-crud-modal-on-open` en attribut HTML.

Exemple avec un bouton :

```html
<button class="ec-crud-remote-modal-auto" data-ec-crud-modal-element="#main_modal" data-ec-crud-modal-element-content="#main_modal .modal-content" data-ec-crud-modal-url="/goodRequest">Go !</button>
```

Exemple avec un lien :

```html
<a href="/goodRequest" class="ec-crud-remote-modal-auto" data-ec-crud-modal-element="#main_modal" data-ec-crud-modal-element-content="#main_modal .modal-content">Go !</a>
```

Avec un lien, l'URL utilisée pour la requête Ajax est:
* La valeur de l'attribut `data-ec-crud-modal-url` (si présent)
* Ou la valeur de `href`


## Fermeture fenêtre modale

```js
import * as modalManager from '@ecommit/crud-bundle/js/modal/modal-manager';

modalManager.closeModal('#main_modal');
```
