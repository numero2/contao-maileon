<?php

/**
 * Maileon Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MaileonBundle\EventListener\Hook;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\Form;
use Contao\FormFieldModel;
use Doctrine\DBAL\Connection;
use numero2\MaileonBundle\API\MaileonApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class FormListener {


    /**
     * @var Symfony\Component\HttpFoundation\RequestStack
     */
    private RequestStack $requestStack;

    /**
     * @var Doctrine\DBAL\Connection
     */
    private Connection $connection;

    /**
     * @var Contao\CoreBundle\InsertTag\InsertTagParser
     */
    private InsertTagParser $insertTagParser;

    /**
     * @var numero2\MaileonBundle\API\MaileonApi
     */
    private MaileonApi $maileonApi;

    /**
     * @var Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;


    public function __construct( RequestStack $requestStack, Connection $connection, InsertTagParser $insertTagParser, MaileonApi $maileonApi, LoggerInterface $logger ) {

        $this->requestStack = $requestStack;
        $this->connection = $connection;
        $this->insertTagParser = $insertTagParser;
        $this->maileonApi = $maileonApi;
        $this->logger = $logger;
    }


    /**
     * Process form data and create an contact at maileon
     *
     * @param array $submittedData
     * @param array $formData
     * @param array $files
     * @param array $labels
     * @param Contao\Form $form
     */
    #[AsHook('processFormData')]
    public function createContactAtMaileon( array $submittedData, array $formData, ?array $files, array $labels, Form $form ): void {

        if( empty($form->sendToMaileon) || empty($form->maileon_api_key) ) {
            return;
        }

        // match fields to maileon
        $maileonData = [];

        $t = FormFieldModel::getTable();

        $formFields = $this->connection->executeQuery(
            "SELECT * FROM $t AS field WHERE pid=:pid ORDER BY sorting ASC"
        ,   ['pid'=>$form->id]
        )->fetchAllAssociative();

        if( $formFields ) {
            foreach( $formFields as $field ) {
                if( empty($submittedData[$field['name']]) ) {
                    continue;
                }
                if( empty($field['maileon_field_name']) ) {
                    continue;
                }

                $maileonData[$field['maileon_field_name']] = $submittedData[$field['name']];
            }
        }

        if( empty($maileonData['LOCALE']) ) {
            $maileonData['LOCALE'] = $this->requestStack->getCurrentRequest()->getLocale();
        }

        $maileonSettings = [
            'permission' => intval($form->maileon_permission)
        ,   'sync_mode' => intval($form->maileon_sync_mode)
        ];

        if( !empty($form->maileon_src) ) {
            $maileonSettings['src'] = $this->insertTagParser->replaceInline($form->maileon_src);
        }

        if( !empty($form->maileon_use_doi) ) {

            $maileonSettings['doi'] = "true";

            if( !empty($form->maileon_doiplus) ) {
                $maileonSettings['doiplus'] = "true";
            }
            if( !empty($form->maileon_doimailing) ) {
                $maileonSettings['doimailing'] = $form->maileon_doimailing;
            }
        }

        $this->maileonApi->setApiKey($form->maileon_api_key);

        $id = $this->maileonApi->createContact($maileonData, $maileonSettings);
        if( $id ) {
            $this->logger->info('Created contact at Maileon, got id "'.$id.'" returned.');
        } else {
            $this->logger->info('Created contact at Maileon maybe failed, no id returned. Please check for other errors!');
        }
    }
}
