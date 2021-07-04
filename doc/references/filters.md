# Filtres

## Options générales pour tous les filtres

| Option | Description | Requis  | Valeur par défaut |
| ------ | ----------- | --------| ----------------- |
| column_id | ID de la colonne du CRUD associée | Non | Nom du filtre 
| autovalidate | Si `true`, active la validation automatique (en fonction du filtre utilisé) | Non | [Valeur de autovalidate du formulaire](searcher.md) |
| validation_groups | Groupe de validation | Non | [Valeur de validation_groups du formulaire](searcher.md) |
| required | Si `true`, filtre obligatoire | Non | Non |
| type_options | Options du champs du formulaire Symfony généré | Non | [ ] |


## BooleanFilter

**Description** : Case à cocher pour filtrer un booléen.

**Classe** : `Ecommit\CrudBundle\Form\Filter\BooleanFilter`

**Options** :

| Option | Description | Requis  | Valeur par défaut |
| ------ | ----------- | --------| ----------------- |
| value_true | Valeur du booléen (pour la recherche) dans sa valeur "vrai" | Non | 1 |
| value_false | Valeur du booléen (pour la recherche) dans sa valeur "faux" | Non | 0 |
| not_null_is_true | Si `true`, toute valeur non nulle (pour la recherche) est considérée comme "vrai" | Non | false |
| null_is_false | Si `true`, toute valeur nulle (pour la recherche) est considérée comme "faux" | Non | true |

Voir aussi les [options générales](#options-générales-pour-tous-les-filtres) 


## ChoiceFilter

**Description** : Choix de valeur(s) parmi une liste

**Classe** : `Ecommit\CrudBundle\Form\Filter\ChoiceFilter`

**Options** :

| Option | Description | Requis  | Valeur par défaut |
| ------ | ----------- | --------| ----------------- |
| multiple | Si `true`, autorise le choix multiple | Non | false |
| min | Si défini et option multiple à `true`, nombre mini d'éléments à sélectionner | Non | null |
| max | Nombre mini d'éléments sélectionnables | Non | 1000 |
| choices | Valeurs à proposer | Non | [ ] |
| type | "Type" de formulaire Symfony à utiliser | Non | Symfony\Component\Form\Extension\Core\Type\ChoiceType |

Voir aussi les [options générales](#options-générales-pour-tous-les-filtres) 


## DateFilter

**Description** : Filtre d'une date 

**Classe** : `Ecommit\CrudBundle\Form\Filter\DateFilter`

**Options** :

| Option | Description | Requis  | Valeur par défaut |
| ------ | ----------- | --------| ----------------- |
| comparator | Signe de comparaison (>, >= <, <=, =) à utiliser pour filtrer la date. Utiliser une constante de `Ecommit\CrudBundle\Form\Filter\DateFilter` | Oui | |
| with_time | Si `true`, gère aussi l'heure | Non | false|

Voir aussi les [options générales](#options-générales-pour-tous-les-filtres)


## EntityFilter

**Description** : Filtre une entité Doctrine

**Classe** : `Ecommit\CrudBundle\Form\Filter\EntityFilter`

**Options** :

| Option | Description | Requis  | Valeur par défaut |
| ------ | ----------- | --------| ----------------- |
| multiple | Si `true`, autorise le choix multiple | Non | false |
| min | Si défini et option multiple à `true`, nombre mini d'éléments à sélectionner | Non | null |
| max | Nombre mini d'éléments sélectionnables | Non | 1000 |
| class | Classe de l'entité Doctrine | Oui | |


Voir aussi les [options générales](#options-générales-pour-tous-les-filtres)


## EntityAjaxFilter

**Description** : Filtre une entité Doctrine en utilisant [`Ecommit\CrudBundle\Form\Type\EntityAjaxType`](entity_ajax_type.md)

**Classe** : `Ecommit\CrudBundle\Form\Filter\EntityAjaxFilter`

**Options** :

| Option | Description | Requis  | Valeur par défaut |
| ------ | ----------- | --------| ----------------- |
| multiple | Si `true`, autorise le choix multiple | Non | false |
| min | Si défini et option multiple à `true`, nombre mini d'éléments à sélectionner | Non | null |
| max | Nombre mini d'éléments sélectionnables | Non | 1000 |
| class | Classe de l'entité Doctrine | Oui | |
| route_name | Route à utiliser pour la recherche Ajax | Oui | |
| route_params | Paramètres à utiliser pour la recherche Ajax | Non | [ ] |

Voir aussi les [options générales](#options-générales-pour-tous-les-filtres) 

Voir la [documentation](entity_ajax_type.md) de `Ecommit\CrudBundle\Form\Type\EntityAjaxType`


## IntegerFilter

**Description** : Filtre un entier

**Classe** : `Ecommit\CrudBundle\Form\Filter\IntegerFilter`

**Options** :

| Option | Description | Requis  | Valeur par défaut |
| ------ | ----------- | --------| ----------------- |
| comparator | Signe de comparaison (>, >= <, <=, =) à utiliser pour filtrer la valeur. Utiliser une constante de `Ecommit\CrudBundle\Form\Filter\IntegerFilter` | Oui | |

Voir aussi les [options générales](#options-générales-pour-tous-les-filtres)


## NotNullFilter

**Description** : Filtre toute valeur non nulle

**Classe** : `Ecommit\CrudBundle\Form\Filter\NotNullFilter`

**Options** : [Options générales](#options-générales-pour-tous-les-filtres)


## NullFilter

**Description** : Filtre toute valeur nulle

**Classe** : `Ecommit\CrudBundle\Form\Filter\NullFilter`

**Options** : [Options générales](#options-générales-pour-tous-les-filtres)


## NumberFilter

**Description** : Filtre une valeur numérique

**Classe** : `Ecommit\CrudBundle\Form\Filter\NumberFilter`

**Options** :

| Option | Description | Requis  | Valeur par défaut |
| ------ | ----------- | --------| ----------------- |
| comparator | Signe de comparaison (>, >= <, <=, =) à utiliser pour filtrer la valeur. Utiliser une constante de `Ecommit\CrudBundle\Form\Filter\NumberFilter` | Oui | |

Voir aussi les [options générales](#options-générales-pour-tous-les-filtres)



## TextFilter

**Description** : Filtre un texte

**Classe** : `Ecommit\CrudBundle\Form\Filter\TextFilter`

**Options** :

| Option | Description | Requis  | Valeur par défaut |
| ------ | ----------- | --------| ----------------- |
| must_begin | Si `true`, utilise le filtre par "qui commence par" | Non | false |
| must_end | Si `true`, utilise le filtre par "qui se termine par" | Non | false |
| min_length | Si définie, longueur minimale de la longueur du terme recherché | Non | null |
| max_length | Si définie, longueur maximale de la longueur du terme recherché | Non | 255 |
| type | "Type" de formulaire Symfony à utiliser | Non | Symfony\Component\Form\Extension\Core\Type\TextType |


Voir aussi les [options générales](#options-générales-pour-tous-les-filtres)
