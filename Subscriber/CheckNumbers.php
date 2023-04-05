<?php

namespace DateifabrikCheckNumberRange\Subscriber;

use Enlight\Event\SubscriberInterface;
use Zend_Mail;
use Zend_Mail_Transport_Smtp;

class CheckNumbers implements SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            #'Shopware_CronJob_ExtDocumentCreate' => 'checkNumberRange',
            'Shopware_CronJob_SwagBonusSystemCron' => 'checkNumberRange',
        ];
    }

    public function checkNumberRange()
    {

        // write control file
        $pathToFile = __DIR__ . "/alive.txt";
        $file = fopen($pathToFile, "w");
        fwrite($file, date("H:i:s d.m.Y", time()));
        fclose($file);

        // get the last numbers from the database
        $builder = Shopware()->Models()->createQueryBuilder();
        $data = $builder->select('orderNumber')
            ->from('Shopware\Models\Order\Number', 'orderNumber')
            ->where('orderNumber.id BETWEEN :fromId AND :toId')
            ->setParameter('fromId', 920)
            ->setParameter('toId', 924)
            ->getQuery()
            ->getArrayResult();

        // we need only the 'number' from the array
        // +-----+--------+---------+---------------+
        // | id  | number | name    | desc          |
        // +-----+--------+---------+---------------+
        // | 920 |  85879 | invoice | Bestellungen  |
        // | 921 |  85879 | doc_1   | Lieferscheine |
        // | 922 |  85879 | doc_2   | Gutschriften  |
        // | 924 |  85879 | doc_0   | Rechnungen    |
        // +-----+--------+---------+---------------+

        foreach ($data as $item) {
            $numbers[] = $item['number'];
        }

        if(count(array_unique($numbers)) != 1){

            $wrongData = "\r\n\r\n";
            foreach ($data as $d) {
                $wrongData .= $d['description'] . " => " . $d['number'] . "\n";
            }

            $mail = new Zend_Mail();
            $mail->setFrom('noreply@packing24.de', 'DateifabrikCheckNumberRange Plugin')
                ->addTo('info@packing24.de', 'Packing24')
                ->addBcc([
                    'jaeger@packing24.de',
                    'bruse@packing24.de',
                ])
                ->setSubject('Achtung, Nummerkreise verschoben ' . date("H:i:s", time()). " Uhr")
                ->setBodyText('Die Nummernkreise sind verschoben. ' . $wrongData);


            $transport = new Zend_Mail_Transport_Smtp('packing24s2.timmeserver.de', [
                'auth' => 'login',
                'username' => 'dateifabrikchecknumberrange@packing24.de',
                'password' => 'oopa7aa8rah4Eija',
                'ssl' => 'ssl',
                'port' => 465,
            ]);

            $mail->send($transport);

        }

    }
}
