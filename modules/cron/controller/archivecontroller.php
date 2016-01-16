<?php

namespace Cron\Controller;

use Cron\Etc\Controller;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Registry\Registry;

/**
 *
 */
class ArchiveController extends Controller
{

    /**
     *
     */
    public function archivateActions()
    {
        $this->disableView();

        $articles = \App\Model\ActionModel::all(array('created <= ?' => date('Y-m-d H:i:s', strtotime('-2 year')), 'archive = ?' => false), array('id', 'archive'));

        if (!empty($articles)) {
            foreach ($articles as $article) {
                $article->archive = 1;

                if ($article->update() > 0) {
                    Event::fire('cron.log', array('success', 'Archivating action id: ' . $article->getId()));
                } else {
                    Event::fire('cron.log', array('fail', 'An error occured while archivating action id: ' . $article->getId()));
                }
            }
            $this->getCache()->erase('arch');
        }
    }

    /**
     *
     */
    public function archivateNews()
    {
        $this->disableView();

        $articles = \App\Model\NewsModel::all(array('created <= ?' => date('Y-m-d H:i:s', strtotime('-2 year')), 'archive = ?' => false), array('id', 'archive'));

        if (!empty($articles)) {
            foreach ($articles as $article) {
                $article->archive = 1;

                if ($article->update() > 0) {
                    Event::fire('cron.log', array('success', 'Archivating news id: ' . $article->getId()));
                } else {
                    Event::fire('cron.log', array('fail', 'An error occured while archivating news id: ' . $article->getId()));
                }
            }
            $this->getCache()->erase('arch');
        }
    }

    /**
     *
     */
    public function archivateReports()
    {
        $this->disableView();

        $articles = \App\Model\ReportModel::all(array('created <= ?' => date('Y-m-d H:i:s', strtotime('-2 year')), 'archive = ?' => false), array('id', 'archive'));

        if (!empty($articles)) {
            foreach ($articles as $article) {
                $article->archive = 1;

                if ($article->update() > 0) {
                    Event::fire('cron.log', array('success', 'Archivating report id: ' . $article->getId()));
                } else {
                    Event::fire('cron.log', array('fail', 'An error occured while archivating report id: ' . $article->getId()));
                }
            }
        }
        $this->getCache()->erase('arch');
    }

}
