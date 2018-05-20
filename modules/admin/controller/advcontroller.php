<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use App\Model\AdvertisementModel;
use THCFrame\Request\RequestMethods;

/**
 * Trida pro load dat do datatables protoze Advertisement je pak v url
 * moc dlouhy a datatables nic nenactou
 */
class AdvController extends Controller
{

    /**
     * Response for ajax call from datatables plugin.
     *
     * @before _secured, _participant
     */
    public function load()
    {
        $this->disableView();
        $maxRows = 100;

        $page = (int) RequestMethods::post('page', 0);
        $search = RequestMethods::issetpost('sSearch') ? RequestMethods::post('sSearch') : '';

        if ($search != '') {
            $whereCond = "adv.created LIKE '%%?%%' OR adv.userAlias LIKE '%%?%%' OR adv.title LIKE '%%?%%'";

            $query = AdvertisementModel::getQuery(
                            ['adv.id', 'adv.userId', 'adv.userAlias', 'adv.title',
                                'adv.active', 'adv.state', 'adv.expirationDate', 'adv.created',])
                    ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                    ->wheresql($whereCond, $search, $search, $search);

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $query->order('adv.id', $dir);
                } elseif ($column == 1) {
                    $query->order('adv.title', $dir);
                } elseif ($column == 2) {
                    $query->order('adv.userAlias', $dir);
                } elseif ($column == 3) {
                    $query->order('adv.created', $dir);
                }
            } else {
                $query->order('adv.id', 'desc');
            }

            $limit = min((int) RequestMethods::post('iDisplayLength'), $maxRows);
            $query->limit($limit, $page + 1);
            $ads = AdvertisementModel::initialize($query);

            $countQuery = AdvertisementModel::getQuery(['adv.id'])
                    ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                    ->wheresql($whereCond, $search, $search, $search);

            $adsCount = AdvertisementModel::initialize($countQuery);
            unset($countQuery);
            $count = count($adsCount);
            unset($adsCount);
        } else {
            $query = AdvertisementModel::getQuery(
                            ['adv.id', 'adv.userId', 'adv.userAlias', 'adv.title',
                                'adv.active', 'adv.state', 'adv.expirationDate', 'adv.created',])
                    ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname']);

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $query->order('adv.id', $dir);
                } elseif ($column == 1) {
                    $query->order('adv.title', $dir);
                } elseif ($column == 2) {
                    $query->order('adv.userAlias', $dir);
                } elseif ($column == 3) {
                    $query->order('adv.created', $dir);
                }
            } else {
                $query->order('adv.id', 'desc');
            }

            $limit = min((int) RequestMethods::post('iDisplayLength'), $maxRows);
            $query->limit($limit, $page + 1);
            $ads = AdvertisementModel::initialize($query);
            $count = AdvertisementModel::count();
        }

        $draw = $page + 1 + time();

        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $returnArr = [];
        if (null !== $ads) {
            foreach ($ads as $ad) {
                $label = '';
                if ($ad->active) {
                    $label .= "<span class='infoLabel infoLabelGreen'>Aktivní</span>";
                } else {
                    $label .= "<span class='infoLabel infoLabelRed'>Neaktivní</span>";
                }

                if ($ad->state == AdvertisementModel::STATE_SOLD) {
                    $label .= "<span class='infoLabel infoLabelOrange'>Prodáno</span>";
                }

                $arr = [];
                $arr [] = '[ "' . $ad->getId() . '"';
                $arr [] = '"' . htmlentities($ad->getTitle()) . '"';
                $arr [] = '"' . $ad->getUserAlias() . '"';
                $arr [] = '"' . $ad->getExpirationDate() . '"';
                $arr [] = '"' . $ad->getCreated() . '"';
                $arr [] = '"' . $label . '"';

                $tempStr = '"';
                if ($this->isAdmin()) {
                    $tempStr .= "<a href='/admin/advertisement/delete/" . $ad->getId() . "' class='btn btn3 btn_trash ajaxDelete' title='Smazat'></a>";
                }
                if ($this->isAdmin() || $ad->getUserId() == $this->getUser()->getId()) {
                    $tempStr .= "<a href='/admin/advertisement/detail/" . $ad->getId() . "' class='btn btn3 btn_search' title='Detail'></a>";
                }

                if ($this->isAdmin() && $ad->getHasAvailabilityRequest()) {
                    $tempStr .= "<a href='/admin/advertisement/extendavailability/" . $ad->getId() . "' class='btn btn3 btn_refresh ajaxReload' title='Prodloužit životnost'></a>";
                }

                $arr [] = $tempStr . '"]';
                $returnArr[] = implode(',', $arr);
            }

            $str .= implode(',', $returnArr) . ']}';

            echo $str;
        } else {
            $str .= '[ "","","","","","",""]]}';

            echo $str;
        }
    }

}
