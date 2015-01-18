<?php

namespace App\Controller;

use App\Etc\Controller;

/**
 * 
 */
class GalleryController extends Controller
{

    /**
     * Get list of galleries
     * 
     * @param int $page
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

        if (null !== $content) {
            $galleries = $content;
            unset($content);
        } else {
            $galleries = \App\Model\GalleryModel::fetchPublicActiveGalleries(30, $page);
            $this->getCache()->set('galerie-'.$page, $galleries);
        }
        
        $galleryCount = \App\Model\GalleryModel::count(
                        array('active = ?' => true,
                            'isPublic = ?' => true)
        );
        $galleryPageCount = ceil($galleryCount / 30);

        $this->_pagerMetaLinks($galleryPageCount, $page, '/galerie/p/');
        
        $view->set('galleries', $galleries)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/galerie')
                ->set('pagecount', $galleryPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Galerie');
    }

    /**
     * Show gallery detail
     * 
     * @param string $urlKey
     */
    public function detail($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $gallery = \App\Model\GalleryModel::fetchPublicActiveGalleryByUrlkey($urlKey);

        if ($gallery === null) {
            self::redirect('/nenalezeno');
        }

        $canonical = 'http://' . $this->getServerHost() . '/galerie/r/' . $gallery->getUrlKey();

        $view->set('gallery', $gallery);

        $layoutView->set('canonical', $canonical)
                ->set('includejssorslider', 1)
                ->set('metatitle', 'Hastrman - Galerie - ' . $gallery->getTitle());
    }

}
