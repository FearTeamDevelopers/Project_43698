<?php

use App\Etc\Controller;

/**
 * 
 */
class App_Controller_Gallery extends Controller
{

    /**
     * 
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        if($page <= 0){
            $page = 1;
        }
        
        if ($page == 1) {
            $canonical = 'http://' . $this->getServerHost() . '/galerie';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/galerie/p/' . $page;
        }
        
        $content = $this->getCache()->get('galerie-'.$page);

        if ($content !== null) {
            $galleries = $content;
            unset($content);
        } else {
            $galleries = App_Model_Gallery::fetchPublicActiveGalleries(30, $page);
            $this->getCache()->set('galerie-'.$page, $galleries);
        }
        
        $galleryCount = App_Model_Gallery::count(
                        array('active = ?' => true,
                            'isPublic = ?' => true)
        );
        $galleryPageCount = ceil($galleryCount / 30);

        if ($galleryPageCount > 1) {
            $prevPage = $page - 1;
            $nextPage = $page + 1;

            if ($nextPage > $galleryPageCount) {
                $nextPage = 0;
            }

            $layoutView
                    ->set('pagedprev', $prevPage)
                    ->set('pagedprevlink', '/galerie/p/' . $prevPage)
                    ->set('pagednext', $nextPage)
                    ->set('pagednextlink', '/galerie/p/' . $nextPage);
        }

        $view->set('galleries', $galleries)
                ->set('currentpage', $page)
                ->set('pagecount', $galleryPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Galerie');
    }

    /**
     * 
     * @param type $urlKey
     */
    public function detail($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $gallery = App_Model_Gallery::fetchPublicActiveGalleryByUrlkey($urlKey);

        if ($gallery === null) {
            self::redirect('/nenalezeno');
        }

        $canonical = 'http://' . $this->getServerHost() . '/galerie/r/' . $gallery->getUrlKey();

        $view->set('gallery', $gallery);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Galerie - ' . $gallery->getTitle());
    }

}
