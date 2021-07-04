# Options du formulaire de recherche

## Options disponibles

| Option | Description | Requis  | Valeur par défaut |
| ------ | ----------- | --------| ----------------- |
| autovalidate | Si `true`, active la validation automatique (en fonction des filtres utilisés) | Non | true |
| validation_groups | Groupe de validation | Non | null |
| form_options | Options du formulaire Symfony généré | Non | [ ] |
| alias_search | Alias Doctrine à utiliser pour la recherche | Non | Valeur définie dans la déclaration de la colonne du CRUD associée |
| label | Label | Non | Valeur définie dans la déclaration de la colonne du CRUD associée |


## Définition des options

Dans le contrôleur, passer les options dans le 3ème argument (tableau) de la méthode `createSearchForm`.
