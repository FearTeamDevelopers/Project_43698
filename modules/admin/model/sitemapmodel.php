<?php
namespace Admin\Model;

/**
 * 
 */
class SitemapModel
{

    /**
     *
     */
    public static function generateSitemap()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <urlset
            xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . PHP_EOL;

        $xmlEnd = '</urlset>';

        $host = RequestMethods::server('HTTP_HOST');

        $pageContent = \App\Model\PageContentModel::all(['active = ?' => true]);
        $redirects = RedirectModel::all(['module = ?' => 'app']);
        $news = \App\Model\NewsModel::all(['active = ?' => true, 'approved = ?' => 1], ['urlKey']);
        $reports = \App\Model\ReportModel::all(['active = ?' => true, 'approved = ?' => 1], ['urlKey']);
        $actions = \App\Model\ActionModel::all(['active = ?' => true, 'approved = ?' => 1], ['urlKey']);

        $redirectArr = [];
        if (null !== $redirects) {
            foreach ($redirects as $redirect) {
                $redirectArr[$redirect->getToPath()] = $redirect->getFromPath();
            }
        }

        $articlesXml = '';
        $pageContentXml = "<url><loc>http://{$host}</loc></url>" . PHP_EOL
            . "<url><loc>http://{$host}/akce</loc></url>"
            . "<url><loc>http://{$host}/probehle-akce</loc></url>"
            . "<url><loc>http://{$host}/archiv-akci</loc></url>"
            . "<url><loc>http://{$host}/archiv-novinek</loc></url>"
            . "<url><loc>http://{$host}/archiv-reportazi</loc></url>"
            . "<url><loc>http://{$host}/reportaze</loc></url>"
            . "<url><loc>http://{$host}/novinky</loc></url>"
            . "<url><loc>http://{$host}/galerie</loc></url>"
            . "<url><loc>http://{$host}/bazar</loc></url>" . PHP_EOL;

        $linkCounter = 10;

        if (null !== $pageContent) {
            foreach ($pageContent as $content) {
                $pageUrl = '/page/' . $content->getUrlKey();
                if (array_key_exists($pageUrl, $redirectArr)) {
                    $pageUrl = $redirectArr[$pageUrl];
                }
                $pageContentXml .= "<url><loc>http://{$host}{$pageUrl}</loc></url>" . PHP_EOL;
                $linkCounter += 1;
            }
        }

        if (null !== $news) {
            foreach ($news as $_news) {
                $articlesXml .= "<url><loc>http://{$host}/novinky/r/{$_news->getUrlKey()}</loc></url>" . PHP_EOL;
                $linkCounter += 1;
            }
        }

        if (null !== $actions) {
            foreach ($actions as $action) {
                $articlesXml .= "<url><loc>http://{$host}/akce/r/{$action->getUrlKey()}</loc></url>" . PHP_EOL;
                $linkCounter += 1;
            }
        }

        if (null !== $reports) {
            foreach ($reports as $report) {
                $articlesXml .= "<url><loc>http://{$host}/reportaze/r/{$report->getUrlKey()}</loc></url>" . PHP_EOL;
                $linkCounter += 1;
            }
        }

        file_put_contents('./sitemap.xml', $xml . $pageContentXml . $articlesXml . $xmlEnd);
    }
}
