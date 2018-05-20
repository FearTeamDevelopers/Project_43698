<?php

namespace Cron\Controller;

use Cron\Etc\Controller;
use THCFrame\Events\Events as Event;

/**
 *
 */
class ArchiveController extends Controller
{

    /**
     * Mark actions older than 2 years as archivated
     */
    public function archivateActions()
    {
        $this->disableView();

        $articles = \App\Model\ActionModel::all(['created <= ?' => date('Y-m-d H:i:s', strtotime('-2 year')), 'archive = ?' => false], ['id', 'archive']);

        if (!empty($articles)) {
            foreach ($articles as $article) {
                $article->archive = 1;

                if ($article->update() > 0) {
                    Event::fire('cron.log', ['success', 'Archivating action id: ' . $article->getId()]);
                } else {
                    Event::fire('cron.log', ['fail', 'An error occured while archivating action id: ' . $article->getId()]);
                }
            }
            $this->getCache()->erase('arch');
        }
    }

    /**
     * Mark news older than 2 years as archivated
     */
    public function archivateNews()
    {
        $this->disableView();

        $articles = \App\Model\NewsModel::all(['created <= ?' => date('Y-m-d H:i:s', strtotime('-2 year')), 'archive = ?' => false], ['id', 'archive']);

        if (!empty($articles)) {
            foreach ($articles as $article) {
                $article->archive = 1;

                if ($article->update() > 0) {
                    Event::fire('cron.log', ['success', 'Archivating news id: ' . $article->getId()]);
                } else {
                    Event::fire('cron.log', ['fail', 'An error occured while archivating news id: ' . $article->getId()]);
                }
            }
            $this->getCache()->erase('arch');
        }
    }

    /**
     * Mark reports older than 2 years as archivated
     */
    public function archivateReports()
    {
        $this->disableView();

        $articles = \App\Model\ReportModel::all(['created <= ?' => date('Y-m-d H:i:s', strtotime('-2 year')), 'archive = ?' => false], ['id', 'archive']);

        if (!empty($articles)) {
            foreach ($articles as $article) {
                $article->archive = 1;

                if ($article->update() > 0) {
                    Event::fire('cron.log', ['success', 'Archivating report id: ' . $article->getId()]);
                } else {
                    Event::fire('cron.log', ['fail', 'An error occured while archivating report id: ' . $article->getId()]);
                }
            }
        }
        $this->getCache()->erase('arch');
    }

}
