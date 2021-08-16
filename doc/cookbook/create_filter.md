# Création d'un filtre de recherche

> **_ALTERNATIVES :_**
>   
> * [Utilisation d'un filtre existant](../references/filters.md)
> * [Personnalisation avancée de la classe Searcher](../cookbook/advanced-searcher.md)


Un filtre doit être une classe qui hérite de `AbstractFilter` ou implémente `FilterInterface` :
* La méthode `buildForm` ajoute le filtre dans le fromulaire de recherche (utiliser `$builder->addField()`).
* La méthode `updateQueryBuilder` modifie le QueryBuilder en fonction de la valeur saisie dans le filtre.
* La méthode `configureOptions` définie les éventuelles options.

> **_REMARQUE :_** Il est conseillé de regarder le code source des filtres existants.


La classe doit être déclarée comme service ayant le [tag](https://symfony.com/doc/current/service_container/tags.html) `ecommit_crud.filter`.
