<?php

namespace Cron\Controller;

use Cron\Etc\Controller;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class AdvertisementController extends Controller
{
    /**
     * Check all bazar advertisements and notify their owner if it is going to 
     * expire in next 7 days
     * 
     * @before _cron
     */
    public function checkAdExpirations()
    {
        $this->_disableView();

        $ads = \App\Model\AdvertisementModel::expireInDays();

        foreach ($ads as $email => $adverts) {
            $adsString = '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin:0 auto; font-family:Arial; color:#394c5f; background-color:white;" width="700">'
                    . '<tbody>'
                    . '<tr><td>Název</td><td>Vytvořen</td><td>Vyprší během x dní</td><td>Žádost o prodloužení</td></tr>';
            
            foreach ($adverts as $key => $value) {
                $availabilityRequestToken = \THCFrame\Core\Rand::randStr(126);
                
                \App\Model\AdvertisementModel::updateAll(array('uniqueKey = ?' => $value['uniqueKey']), 
                        array('availabilityRequestToken' => $availabilityRequestToken,
                            'availabilityRequestTokenExpiration' => date('Y-m-d H:i:s', strtotime('+1 day'))));
                
                $adsString .= '<tr><td>'.$value['title'].'</td>'
                        . '<td>'. \THCFrame\Date\Date::getInstance()->format($value['created'], \THCFrame\Date\Date::CZ_BASE_DATETIME_FORMAT) .'</td>'
                        . '<td>'.$value['expireIn'].'</td>'
                        . '<td><a href="http://'.$this->getServerHost().'/bazar/prodlouzit/'.$value['uniqueKey'].'/'.$availabilityRequestToken.'" target=_blank>Prodloužit</a></td>'
                        . '</tr>';
            }

            $adsString .= '</tbody></table>';
            
            $data = array('{ADS}' => $adsString);
            $email = \Admin\Model\EmailModel::loadAndPrepare('ad-expiration-notification', $data);
            $email->setRecipient($email);

            if ($email->send(false, 'bazar@hastrman.cz')) {
                Event::fire('cron.log', array('success', 'Advertisement expiration check send to: ' . $email));
            } else {
                Event::fire('cron.log', array('fail', 'Advertisement expiration check send to: ' . $email));
            }
        }
    }

}
