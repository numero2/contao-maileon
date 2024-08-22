<?php

/**
 * Maileon Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MaileonBundle\API;

use Contao\System;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class MaileonApi {

    CONST BASE_URI = 'https://api.maileon.com/1.0';

    CONST STANDARD_CONTACT_FIELDS = [
        'EMAIL', 'ADDRESS', 'BIRTHDAY', 'CITY', 'COUNTRY', 'FIRSTNAME', 'GENDER', 'HNR',
        'LASTNAME', 'FULLNAME', 'LOCALE', 'NAMEDAY', 'ORGANIZATION', 'REGION',
        'STATE', 'SALUTATION', 'TITLE', 'ZIP'
    ];


    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var Symfony\Contracts\HttpClient\HttpClientInterface
     */
    protected $client;

    /**
     * @var Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;


    public function __construct( HttpClientInterface $client, LoggerInterface $logger ) {

        $this->client = $client;
        $this->logger = $logger;

        $this->apiKey = null;
    }


    /**
     * Set the API key that will be used
     *
     * @param string $apiKey
     *
     * @return self
     */
    public function setApiKey( string $apiKey ): self {

        $this->apiKey = $apiKey;

        return $this;
    }


    /**
     * Get configured contacts custom fields
     *
     * @return array
     */
    public function getContactsFieldsCustom(): array {

        $fieldResult = $this->send('GET', '/contacts/fields/custom');

        if( $fieldResult['status'] === 200 ) {
            return $fieldResult['body']['custom_field'] ?? [];
        }

        return [];
    }


    /**
     * Create a contact
     *
     * @param array $data
     * @param array $settings
     *
     * @return null|string
     */
    public function createContact( array $data, array $settings ): ?string {

        $email = null;
        $maileonData = [];

        foreach( $data as $key => $value ) {

            if( $key === 'EMAIL' ) {
                $email = $value;
            } else if( in_array($key, self::STANDARD_CONTACT_FIELDS) ) {
                $maileonData['standard_fields'][$key] = $value;
            } else {
                $maileonData['custom_fields'][$key] = $value;
            }
        }

        if( empty($email) ) {
            $this->logger->error('Maileon API cannot create contact as of missing email.');
            return null;
        }

        $url = '/contacts/email/' . utf8_encode($email);
        $url .= '?' . http_build_query($settings);

        $createResult = $this->send('POST', $url, $maileonData, "application/vnd.maileon.api+json");

        if( $createResult['status'] === 201 ) {
            return $createResult['body'][0];
        }

        return null;
    }


    /**
     * Send request to Maileon
     *
     * @param string $method
     * @param string $url
     * @param array  $data
     * @param string $contentType
     *
     * @return array
     *
     * @throws RuntimeException If no API key was set
     */
    private function send( string $method, string $url, array $data=[], string $contentType="application/vnd.maileon.api+xml" ): array {

        if( $this->apiKey === null ) {
            $this->logger->error('Maileon API call cannot be performed without setting an API key.');
            throw new RuntimeException('Maileon API key not set');
        }

        $accept = 'application/json';

        if( strpos($url, '/contacts') === 0 ) {
            $accept = 'application/vnd.maileon.api+xml';
        }

        $oOptions = new HttpOptions();
        $oOptions->setHeaders([
            'Content-Type' => $contentType,
            'Accept' => $accept,
            'Authorization' => 'Basic '.base64_encode($this->apiKey),
        ]);

        if( !empty($data) ) {
            $content = json_encode($data);
            $oOptions->setBody($content);
        }

        $aOptions = [];
        $aOptions = $oOptions->toArray();

        $response = null;
        $response = $this->client->request($method, self::BASE_URI.$url, $aOptions);

        $return = [
            'url' => self::BASE_URI.$url
        ,   'status' => $response->getStatusCode()
        ];

        // log error status in system log
        if( $return['status'] >= 400 ) {
            $this->logger->error('Maileon API return status '. $return['status'] .' with body: '.$response->getContent(false));
        }

        if( $accept === 'application/vnd.maileon.api+xml' ) {

            $return['body'] = json_decode(json_encode(simplexml_load_string($response->getContent(false))), true);

        } else if ( $accept === 'application/json' ) {

            $return['body'] = json_decode($response->getContent(false), true);

        } else {

            $return['body'] = $response->getContent(false);
        }

        return $return;
    }
}
