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
            'Shopware_CronJob_ExtDocumentCreate' => 'checkNumberRange',
            #'Shopware_CronJob_SwagBonusSystemCron' => 'checkNumberRange',
        ];
    }

    public function checkNumberRange()
    {

        // datei vorhanden?
        // nein:
        //// datenbank nach neuester ordernumber fragen
        //// in datei schreiben        
        $pathToFile = __DIR__ . "/alive.txt";
        $lastOrdernNumberInFile = $this->getLastOrderNumbers()[0]['number'];
        
        if (!file_exists($pathToFile)) {
            $this->writeLastOrderNumberToFile($lastOrdernNumberInFile);
        }
        else{
            $lastOrdernNumberInFile = file_get_contents($pathToFile);
            $lastOrderNumberInDatabase = $this->getLastOrderNumbers()[0]['number'];
            if($lastOrdernNumberInFile !=  $lastOrderNumberInDatabase){
                $this->writeLastOrderNumberToFile($lastOrderNumberInDatabase);
                $this->sendInfoMail();
            }
        }



        // ja:
        // ordernumber aus datei holen
        // datenbank nach neuester ordernumber fragen ---> funktion nach ordernumber fragen
        // bei zahlen vergleichen
        // sind gleich:
        // nichts tun
        // sind unterschiedlich:
        // neue ordernumber in dadatei schreiben ---> funktion in datei schreiben
        // E-Mail schicken






    }


    public function getLastOrderNumbers()
    {

        // get the last numbers from the database
        $builder = Shopware()->Models()->createQueryBuilder();
        $data = $builder->select('orderNumber')
            ->from('Shopware\Models\Order\Number', 'orderNumber')
            ->where('orderNumber.id BETWEEN :fromId AND :toId')
            ->setParameter('fromId', 920)
            ->setParameter('toId', 924)
            ->getQuery()
            ->getArrayResult();

        return $data;

        // we need only the 'number' from the array
        // +-----+--------+---------+---------------+
        // | id  | number | name    | desc          |
        // +-----+--------+---------+---------------+
        // | 920 |  85879 | invoice | Bestellungen  |
        // | 921 |  85879 | doc_1   | Lieferscheine |
        // | 922 |  85879 | doc_2   | Gutschriften  |
        // | 924 |  85879 | doc_0   | Rechnungen    |
        // +-----+--------+---------+---------------+

    }

    public function writeLastOrderNumberToFile($lastNumber){
        $file = fopen($pathToFile, "w");
        fwrite($file, $lastNumber);
        fclose($file);
    }

    public function sendInfoMail(){
        foreach ($this->getLastOrderNumbers() as $item) {
            $numbers[] = $item['number'];
        }

        if (count(array_unique($numbers)) != 1) {

            $wrongData = "\r\n\r\n";
            foreach ($data as $d) {
                $wrongData .= $d['description'] . " => " . $d['number'] . "\n";
            }

            $mail = new Zend_Mail();
            $mail->setFrom('noreply@packing24.de', 'DateifabrikCheckNumberRange Plugin')
                ->addTo('jaeger@packing24.de', 'Packing24')
                // ->addBcc([
                //     'jaeger@packing24.de',
                //     'bruse@packing24.de',
                // ])
                ->setSubject('Achtung, Nummerkreise verschoben ' . date("H:i:s", time()) . " Uhr")
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
