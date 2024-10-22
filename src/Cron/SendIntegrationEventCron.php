<?php

/**
 * Maileon Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MaileonBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\FormModel;
use Doctrine\DBAL\Connection;
use numero2\MaileonBundle\API\MaileonApi;


#[AsCronJob('daily')]
class SendIntegrationEventCron {


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


    public function __invoke(): void {

        $tForm = FormModel::getTable();
        $forms = $this->connection->executeQuery(
            "SELECT * FROM $tForm WHERE sendToMaileon=:one AND maileon_api_key!=:empty"
        ,   ['one'=>1, 'empty'=>'']
        )->fetchAllAssociative();

        $keysDone = [];

        if( !empty($forms) ) {
            foreach( $forms as $form ) {

                if( empty($form['sendToMaileon']) || empty($form['maileon_api_key']) ) {
                    continue;
                }

                $key = $form['maileon_api_key'];
                $hash = md5($key);

                if( in_array($key, $keysDone) ) {
                    continue;
                }
                $keysDone[] = $key;

                $this->maileonApi->setApiKey($key);

                if( $form['maileon_intevt'] !== $hash ) {

                    $success = $this->maileonApi->sendIntegrationEvent('activated');

                    if( $success ) {
                        $tForm = FormModel::getTable();
                        $this->connection->executeStatement(
                            "UPDATE $tForm SET maileon_intevt=:hash WHERE id=:id"
                        ,   ['hash'=>$hash, 'id'=>$form['id']]
                        );
                    }

                } else {

                    $this->maileonApi->sendIntegrationEvent('heartbeat');
                }
            }
        }
    }
}
