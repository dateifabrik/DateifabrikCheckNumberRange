<?php

namespace DateifabrikCheckNumberRange\Subscriber;

use Enlight\Event\SubscriberInterface;
use Zend_Mail;
use Zend_Mail_Transport_Smtp;

class CheckNumbers implements SubscriberInterface
{

    public $pathToFile = __DIR__ . "/alive.txt";

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_ExtDocumentCreate' => 'checkNumberRange',
            #'Shopware_CronJob_SwagBonusSystemCron' => 'checkNumberRange',
        ];
    }


    public function checkNumberRange(){
        $meinArray = array(79625,79625,79625,79625);
        //var_dump($meinArray);
        $dbNumbersArray = $this->getLastOrderNumbers();
        foreach($dbNumbersArray as $db){
            $dbArray[] = $db['number'];
        }
        // var_dump($dbArray);
        // var_dump($dbArray[0]);
        // var_dump($dbArray[1]);
        // var_dump($dbArray[2]);
        // var_dump($dbArray[3]);
        // var_dump(count($dbArray));

        print_r(array_diff(array_unique($dbArray), $meinArray));

        // $bla = array_diff($dbArray, $meinArray);
        // print_r(array_diff(array_unique($bla), $meinArray));


        //var_dump(array_diff($dbArray, $meinArray));
    }



    public function getLastOrderNumbers()
    {

        // get the last numbers from the database
        $builder = Shopware()->Models()->createQueryBuilder();
        $data = $builder->select('orderNumber.number')
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
    
    
}
