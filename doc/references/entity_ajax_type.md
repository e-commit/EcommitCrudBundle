# EntityAjaxType

**Classe / Service** : `Ecommit\CrudBundle\Form\Type\EntityAjaxType`

Type de formulaire qui permet de sélectionner une entité Doctrine dans une liste déroulante dont les résultats sont
fournis en Ajax (idéal pour les longues listes déroulantes).

Peut être par exemple utilisé avec [Select2](https://select2.org/)


## Options

| Option | Description | Requis  | Valeur par défaut |
| ------ | ----------- | --------| ----------------- |
| class | Classe de l'entité Doctrine | Oui | |
| route_name | Route Symfony pour la requête Ajax | Oui | |
| multiple | Choix multiple nou non | Non | Non |
| em | Entity Manager Doctrine à utiliser | Non | Entity Manager par défaut en fonction de l'option `class` |
| query_builder | Query Builder à utiliser | Non | |
| choice_label | Rendu des labels | Non | |
| route_parameters | Paramètres de la route Symfony pour la requête Ajax | Non | [ ] |
| max_elements | Nombre d'éléments maxi pouvant être sélectionnés | Non | 10000 |
