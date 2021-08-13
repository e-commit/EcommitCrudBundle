# Utilisation d'un CRUD avec une API HTTP

Exemple avec [l'API Opendatasoft Correspondance Code INSEE - Code Postal](https://public.opendatasoft.com/explore/dataset/correspondance-code-insee-code-postal/api/) :

```php
<?php
//src/Controller/MyHttpController
namespace App\Controller;

use App\Form\Searcher\CitySearcher;
use Ecommit\CrudBundle\Controller\AbstractCrudController;
use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Crud\Http\QueryBuilder;
use Ecommit\CrudBundle\Crud\Http\QueryBuilderQueryParameter;
use Ecommit\CrudBundle\Paginator\ArrayPaginator;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MyHttpController extends AbstractCrudController
{
    protected function getCrud(): Crud
    {
        /*
         * Demo de l'API : http://public.opendatasoft.com/api/records/1.0/search?dataset=correspondance-code-insee-code-postal&q=annecy
         *
         * Voir https://public.opendatasoft.com/explore/dataset/correspondance-code-insee-code-postal/api/
         *
         * L'API REST nous retourne un JSON
         * Propriétés qui nous intéressent :
         *      nhits (nombre de résultats trouvés)
         *      records (résultats trouvés)
         *          Pour chaque résultat trouvé :
         *              fields.nom_comm (nom de la ville)
         *              fields.postal_code (code postal)
         *              population (population)
         *              record_timestamp (timestamp de l'enregistrement)
         */

        //Création du QueryBuilder
        //Définition de l'URL REST, appelée en GET
        $queryBuilder = new QueryBuilder('http://public.opendatasoft.com/api/records/1.0/search', 'GET', $this->get(HttpClientInterface::class));

        /*
         * Définition du paramètre "dataset" ajouté en GET à la requête.
         *
         * A différents moments (dans cette méthode et dans la classe du formulaire de recherche), on peut modifier l'objet $queryBuilder :
         *
         * Pour ajouter des éléments à la requête HTTP, on peut appeler la méthode "addParameter" en lui passant en paramètre
         * une instance de l'une des classes suivantes :
         *      Ecommit\CrudBundle\Crud\Http\QueryBuilderQueryParameter : Paramètre passé en GET (query)
         *      Ecommit\CrudBundle\Crud\Http\QueryBuilderBodyParameter : Paramètre passé dans le body
         *      Ecommit\CrudBundle\Crud\Http\QueryBuilderBody : Paramètre unique (sans nom) passé dans le body
         *      Ecommit\CrudBundle\Crud\Http\QueryBuilderHeaderParameter : Paramètre passé dans l'entête
         *
         * La méthode "setBodyIsJson" peut être appelée pour définir une requête au format JSON.
         */
        $queryBuilder->addParameter(new QueryBuilderQueryParameter('dataset', 'correspondance-code-insee-code-postal'));

        //FACULTATIF - Ajout dans la requête HTTP la gestion de la pagination
        $queryBuilder->setPaginationBuilder(function (QueryBuilder $queryBuilder, $page, $resultsPerPage): void {
            $start = ($page - 1) * $resultsPerPage;

            $queryBuilder->addParameter(new QueryBuilderQueryParameter('rows', $resultsPerPage));
            $queryBuilder->addParameter(new QueryBuilderQueryParameter('start', $start));
        });

        //FACULTATIF - Ajout dans la requête HTTP la gestion du tri
        $queryBuilder->setOrderBuilder(function (QueryBuilder $queryBuilder, $orders): void {
            foreach ($orders as $sort => $sense) {
                $senseParameter = ($sense === Crud::ASC)? '-' : '';
                $queryBuilder->addParameter(new QueryBuilderQueryParameter('sort', $senseParameter.$sort));
            }
        });

        $crud = $this->createCrud('my_http_crud');
        $crud->addColumn('name', 'fields.nom_comm', 'Nom', ['sortable' => false])
            ->addColumn('postal_code', 'fields.postal_code', 'Postal code', ['sortable' => false])
            ->addColumn('population', 'fields.population', 'Population', ['alias_sort' => 'population'])
            ->addColumn('timestamp', 'record_timestamp', 'Timestamp', ['sortable' => false])
            ->setQueryBuilder($queryBuilder)
            ->setAvailableResultsPerPage([2, 5, 10], 5)
            ->setDefaultSort('population', Crud::DESC)
            ->setRoute('my_http_crud_ajax')
            ->setBuildPaginator(function (QueryBuilder $queryBuilder, $page, $resultsPerPage) {
                //Appel HTTP + décode du JSON de la réponse
                $response = json_decode($queryBuilder->getResponse($page, $resultsPerPage)->getContent());

                //Création du paginator
                $paginator = new ArrayPaginator($resultsPerPage);
                $paginator->setDataWithoutSlice($response->records, $response->nhits);
                $paginator->setPage($page);
                $paginator->init();

                return $paginator;
            })
            ->createSearchForm(new CitySearcher())
            ->setPersistentSettings(true)
            ->init();

        return $crud;
    }

    protected function getTemplateName(string $action): string
    {
        return sprintf('my_http_crud/%s.html.twig', $action);
    }

    /**
     * @Route("/my-http-crud", name="my_http_crud")
     */
    public function crudAction()
    {
        return $this->getCrudResponse();
    }

    /**
     * @Route("/my-http-crud/ajax", name="my_http_crud_ajax")
     */
    public function ajaxCrudAction()
    {
        return $this->getAjaxCrudResponse();
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            HttpClientInterface::class,
        ]);
    }
}
```


```php
<?php
//src/Form/Searcher/CitySearcher
namespace App\Form\Searcher;

use Ecommit\CrudBundle\Crud\Http\QueryBuilderQueryParameter;
use Ecommit\CrudBundle\Crud\SearchFormBuilder;
use Ecommit\CrudBundle\Form\Searcher\AbstractSearcher;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

class CitySearcher extends AbstractSearcher
{
    /**
     * @Assert\Length(max=50)
     */
    public $text;

    public function buildForm(SearchFormBuilder $builder, array $options): void
    {
        //Ajout d'un champ de recherche

        $builder->addField('text', TextType::class, [
            'label' => 'Query',
            'required' => false,
        ]);
    }

    public function updateQueryBuilder($queryBuilder, array $options): void
    {
        //Traitement de la recherche

        if (null !== $this->text && is_scalar($this->text)) {
            $queryBuilder->addParameter(new QueryBuilderQueryParameter('q', $this->text));
        }
    }
}
```
