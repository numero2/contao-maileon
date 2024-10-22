<?php

/**
 * Maileon Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MaileonBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\FormModel;
use Doctrine\DBAL\Connection;
use numero2\MaileonBundle\API\MaileonApi;


class FormListener {


    /**
     * @var Doctrine\DBAL\Connection
     */
    private Connection $connection;

    /**
     * @var numero2\MaileonBundle\API\MaileonApi
     */
    private MaileonApi $maileonApi;


    public function __construct( Connection $connection, MaileonApi $maileonApi ) {

        $this->connection = $connection;
        $this->maileonApi = $maileonApi;
    }


    /**
     * Get all configured fields at maileon based on settings in the form
     *
     * @param mixed $value
     * @param Contao\DataContainer $dc
     *
     * @return mixed
     */
    #[AsCallback(table: 'tl_form', target: 'fields.maileon_api_key.save')]
    public function getFieldsFromMaileon( $value, DataContainer $dc ) {

        if( $dc->activeRecord && !empty($dc->activeRecord->sendToMaileon) && !empty($dc->activeRecord->maileon_api_key) ) {

            $hash = md5($dc->activeRecord->maileon_api_key);

            if( $dc->activeRecord->maileon_intevt !== $hash ) {

                $this->maileonApi->setApiKey($dc->activeRecord->maileon_api_key);
                $success = $this->maileonApi->sendIntegrationEvent('activated');

                if( $success ) {
                    $tForm = FormModel::getTable();
                    $this->connection->executeStatement(
                        "UPDATE $tForm SET maileon_intevt=:hash WHERE id=:id"
                    ,   ['hash'=>$hash, 'id'=>$dc->id]
                    );
                }
            }
        }

        return $value;
    }
}
