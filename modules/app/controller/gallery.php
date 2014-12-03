<?php

use App\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;

/**
 * 
 */
class App_Controller_Gallery extends Controller
{

    /**
     * 
     */
    public function index()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $canonical = 'http://' . $this->getServerHost() . '/gallerie';
        $content = $this->getCache()->get('galerie');

        if ($content !== null) {
            $galleries = $content;
            unset($content);
        } else {
            $galleries = App_Model_Gallery::fetchPublicActiveGalleries();
            $this->getCache()->set('galerie', $galleries);
        }

        $view->set('galleries', $galleries);

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

        $canonical = 'http://' . $this->getServerHost() . '/gallerie/r/' . $gallery->getUrlKey();

        $view->set('gallery', $gallery);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Galerie - ' . $gallery->getTitle());
    }

}
