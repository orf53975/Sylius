<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Tests\Controller;

use Lakion\ApiTestCase\JsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Anna Walasek <anna.walasek@lakion.com>
 */
class TaxonApiTest extends JsonApiTestCase
{
    /**
     * @var array
     */
    private static $authorizedHeaderWithContentType = [
        'HTTP_Authorization' => 'Bearer SampleTokenNjZkNjY2MDEwMTAzMDkxMGE0OTlhYzU3NzYyMTE0ZGQ3ODcyMDAwM2EwMDZjNDI5NDlhMDdlMQ',
        'CONTENT_TYPE' => 'application/json',
    ];

    /**
     * @var array
     */
    private static $authorizedHeaderWithAccept = [
        'HTTP_Authorization' => 'Bearer SampleTokenNjZkNjY2MDEwMTAzMDkxMGE0OTlhYzU3NzYyMTE0ZGQ3ODcyMDAwM2EwMDZjNDI5NDlhMDdlMQ',
        'ACCEPT' => 'application/json',
    ];

    /**
     * @test
     */
    public function it_does_not_allow_to_show_taxon_list_when_access_is_denied()
    {
        $this->loadFixturesFromFile('resources/taxons.yml');
        $this->client->request('GET', '/api/v1/taxons/');

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'authentication/access_denied_response', Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_show_taxon_when_it_does_not_exist()
    {
        $this->loadFixturesFromFile('authentication/api_administrator.yml');

        $this->client->request('GET', '/api/v1/taxons/-1', [], [], static::$authorizedHeaderWithAccept);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'error/not_found_response', Response::HTTP_NOT_FOUND);
    }

    /**
     * @test
     */
    public function it_allows_indexing_taxons()
    {
        $this->loadFixturesFromFile('authentication/api_administrator.yml');
        $this->loadFixturesFromFile('resources/taxons.yml');


        $this->client->request('GET', '/api/v1/taxons/', [], [], static::$authorizedHeaderWithAccept);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'taxon/index_response', Response::HTTP_OK);
    }

    /**
     * @test
     */
    public function it_allows_showing_taxon()
    {
        $this->loadFixturesFromFile('authentication/api_administrator.yml');
        $taxons = $this->loadFixturesFromFile('resources/taxons.yml');

        $this->client->request('GET', '/api/v1/taxons/'.$taxons['Women']->getId(), [], [], static::$authorizedHeaderWithAccept);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'taxon/show_response', Response::HTTP_OK);
    }

    /**
     * @test
     */
    public function it_does_not_allow_delete_taxon_if_it_does_not_exist()
    {
        $this->loadFixturesFromFile('authentication/api_administrator.yml');

        $this->client->request('DELETE', '/api/v1/taxons/-1', [], [], static::$authorizedHeaderWithAccept);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'error/not_found_response', Response::HTTP_NOT_FOUND);
    }

    /**
     * @test
     */
    public function it_allows_delete_taxon()
    {
        $this->loadFixturesFromFile('authentication/api_administrator.yml');
        $taxons = $this->loadFixturesFromFile('resources/taxons.yml');

        $this->client->request('DELETE', '/api/v1/taxons/'.$taxons['Men']->getId(), [], [], static::$authorizedHeaderWithContentType, []);

        $response = $this->client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_NO_CONTENT);

        $this->client->request('GET', '/api/v1/taxons/'.$taxons['Men']->getId(), [], [], static::$authorizedHeaderWithAccept);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'error/not_found_response', Response::HTTP_NOT_FOUND);
    }

    /**
     * @test
     */
    public function it_allows_create_taxon_with_multiple_translations()
    {
        $this->loadFixturesFromFile('authentication/api_administrator.yml');
        $this->loadFixturesFromFile('resources/taxons.yml');
        $this->loadFixturesFromFile('resources/locales.yml');

        $data =
<<<EOT
        {
            "code": "fluffy_pets",
            "translations": {
                "en_US": {
                    "name": "Fluffy Pets",
                    "slug": "fluffy-pets"
                },
                "nl_NL": {
                    "name": "Pluizige Huisdieren",
                    "slug": "pluizige-huisdieren"
                }
            }
        }
EOT;

        $this->client->request('POST', '/api/v1/taxons/', [], [], static::$authorizedHeaderWithContentType, $data);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'taxon/create_response', Response::HTTP_CREATED);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_create_taxon_without_required_fields()
    {
        $this->loadFixturesFromFile('authentication/api_administrator.yml');

        $this->client->request('POST', '/api/v1/taxons/', [], [], static::$authorizedHeaderWithContentType, []);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'taxon/create_validation_fail_response', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     */
    public function it_allows_updating_taxon()
    {
        $this->loadFixturesFromFile('authentication/api_administrator.yml');
        $taxons = $this->loadFixturesFromFile('resources/taxons.yml');
        $this->loadFixturesFromFile('resources/locales.yml');

        $data =
<<<EOT
        {
            "translations": {
                "en__US": {
                  "name": "Women",
                  "slug": "t-shirts/women"
                }
            },
            "parent": "books"
        }
EOT;
        $this->client->request('PUT', '/api/v1/taxons/'. $taxons["Women"]->getId(), [], [], static::$authorizedHeaderWithContentType, $data);
        $response = $this->client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_NO_CONTENT);
    }

    /**
     * @test
     */
    public function it_allows_updating_partial_information_about_taxon()
    {
        $this->loadFixturesFromFile('authentication/api_administrator.yml');
        $taxons = $this->loadFixturesFromFile('resources/taxons.yml');
        $this->loadFixturesFromFile('resources/locales.yml');

        $data =
<<<EOT
        {
            "translations": {
                "en_US": {
                    "name": "Girl",
                    "slug": "girl"
                }
            }
        }
EOT;
        $this->client->request('PATCH', '/api/v1/taxons/'. $taxons["Women"]->getId(), [], [], static::$authorizedHeaderWithContentType, $data);
        $response = $this->client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_NO_CONTENT);
    }

    /**
     * @test
     */
    public function it_allows_paginating_the_index_of_taxons()
    {
        $this->loadFixturesFromFile('authentication/api_administrator.yml');
        $this->loadFixturesFromFile('resources/taxons.yml');
        $this->loadFixturesFromFile('resources/many_taxons.yml');

        $this->client->request('GET', '/api/v1/taxons/', ['page' => 2], [], static::$authorizedHeaderWithAccept);
        $response = $this->client->getResponse();
        $this->assertResponse($response, 'taxon/paginated_index_response');
    }
}
