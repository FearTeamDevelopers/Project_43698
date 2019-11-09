<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use App\Model\ActionModel;
use App\Model\NewsModel;
use App\Model\PageContentModel;
use App\Model\ReportModel;
use DateInterval;
use DateTime;
use Exception;
use THCFrame\Mailer\Mailer;

/**
 *
 */
class DevController extends Controller
{

    /**
     * Fill database tables tb_action, tb_news and tb_report with testing data
     * For database filling use these urls:
     *      /admin/dev/filldatabase/1    - for tb_news
     *      /admin/dev/filldatabase/2    - for tb_action
     *      /admin/dev/filldatabase/3    - for tb_report.
     *
     * @before _secured, _superadmin
     * @param $type
     * @throws Exception
     */
    public function fillDatabase($type): void
    {
        if (ENV !== 'dev') {
            exit;
        }

        $this->disableView();

        ini_set('max_execution_time', 1800);
        ini_set('memory_limit', '256M');

        $ROW_COUNT = 50;

        $content = PageContentModel::first(['urlKey = ?' => 'kurzy-sdi'], ['body']);

        $SHORT_TEXT = 'Vedle používání zdravého rozumu, dostatečné kvalifikace i praxe je kvalitní a spolehlivá
            potápěčská technika jednou z podmínek dosažení nejvyšší míry bezpečnosti vašich ponorů.
            Kupujte jen takovou výstroj, která tato kriteria splňuje! Pamatujte, že cena je až
            druhotným ukazatelem ... nebo váš život stojí za pár ušetřených stokorun?';

        $LARGE_TEXT = str_replace('h1', 'h2', $content->getBody());
        unset($content);

        if ((int)$type == 1) {
            for ($i = 0; $i < $ROW_COUNT; $i += 1) {
                $news = new NewsModel([
                    'title' => 'News-' . $i . '-' . time(),
                    'userId' => 1,
                    'userAlias' => 'System',
                    'urlKey' => 'news-' . $i . '-' . time(),
                    'approved' => 1,
                    'archive' => 0,
                    'shortBody' => $SHORT_TEXT,
                    'body' => $LARGE_TEXT,
                    'rank' => 1,
                    'keywords' => 'news',
                    'metaTitle' => 'News-' . $i . '-' . time(),
                    'metaDescription' => $SHORT_TEXT,
                    'created' => date('Y-m-d H:i'),
                    'modified' => date('Y-m-d H:i'),
                ]);

                $news->save();
                unset($news);
            }
            self::redirect('/admin/system/');
        }

        if ((int)$type == 2) {
            for ($i = 0; $i < $ROW_COUNT; $i += 1) {
                $date = new DateTime();
                $date->add(new DateInterval('P' . $i . 'D'));
                $startDate = $date->format('Y-m-d');

                $action = new ActionModel([
                    'title' => 'Action-' . $i . '-' . time(),
                    'userId' => 1,
                    'userAlias' => 'System',
                    'urlKey' => 'action-' . $i . '-' . time(),
                    'approved' => 1,
                    'archive' => 0,
                    'shortBody' => $SHORT_TEXT,
                    'body' => $LARGE_TEXT,
                    'rank' => 1,
                    'startDate' => $startDate,
                    'endDate' => $startDate,
                    'startTime' => '',
                    'endTime' => '',
                    'keywords' => 'action',
                    'metaTitle' => 'Action-' . $i . '-' . time(),
                    'metaDescription' => $SHORT_TEXT,
                    'created' => date('Y-m-d H:i'),
                    'modified' => date('Y-m-d H:i'),
                ]);

                $action->save();
                unset($action);
            }
            self::redirect('/admin/system/');
        }

        if ((int)$type == 3) {
            for ($i = 0; $i < $ROW_COUNT; $i += 1) {
                $report = new ReportModel([
                    'title' => 'Report-' . $i . '-' . time(),
                    'userId' => 1,
                    'userAlias' => 'System',
                    'urlKey' => 'report-' . $i . '-' . time(),
                    'approved' => 1,
                    'archive' => 0,
                    'shortBody' => $SHORT_TEXT,
                    'body' => $LARGE_TEXT,
                    'rank' => 1,
                    'keywords' => 'report',
                    'metaTitle' => 'Report-' . $i . '-' . time(),
                    'metaDescription' => $SHORT_TEXT,
                    'metaImage' => '',
                    'photoName' => '',
                    'imgMain' => '',
                    'imgThumb' => '',
                    'created' => date('Y-m-d H:i'),
                    'modified' => date('Y-m-d H:i'),
                ]);

                $report->save();
                unset($report);
            }
            self::redirect('/admin/system/');
        }
    }

    /**
     * @before _secured, _superadmin
     */
    public function testSendEmail(): void
    {
        $this->disableView();

        $mailer = new Mailer();
        $mailer->setBody('Test message')
            ->setSubject('Hastrman test email');

        $mailer->send();

        print('<pre>' . print_r('Send', true) . '</pre>');
        die;
    }

    protected function checkUrlKey($key, $keys, $type)
    {
        if (in_array($key, $keys)) {
            return true;
        }

        if ($type == 'action') {
            $action = ActionModel::first(['urlKey = ?' => $key]);

            if (null !== $action) {
                return 5;
            }
        } elseif ($type == 'report') {
            $report = ReportModel::first(['urlKey = ?' => $key]);

            if (null !== $report) {
                return 5;
            }
        } elseif ($type == 'news') {
            $news = NewsModel::first(['urlKey = ?' => $key]);

            if (null !== $news) {
                return 5;
            }
        }

        return false;
    }
}
