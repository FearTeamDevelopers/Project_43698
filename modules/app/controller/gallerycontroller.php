<?php

namespace App\Controller;

use App\Etc\Controller;
use THCFrame\View\View;

/**
 *
 */
class GalleryController extends Controller
{

    /**
     * Get list of galleries.
     *
     * @param int $page
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = $this->getServerHost() . '/galerie';
        } else {
            $canonical = $this->getServerHost() . '/galerie/p/' . $page;
        }

        $content = $this->getCache()->get('gallery-' . $page);

        if (null !== $content) {
            $galleries = $content;
            unset($content);
        } else {
            $galleries = \App\Model\GalleryModel::fetchPublicActiveGalleries(30, $page);
            $this->getCache()->set('gallery-' . $page, $galleries);
        }

        $galleryCount = \App\Model\GalleryModel::count(
                        ['active = ?' => true,
                            'isPublic = ?' => true,]
        );
        $galleryPageCount = ceil($galleryCount / 30);

        $this->pagerMetaLinks($galleryPageCount, $page, '/galerie/p/');

        $view->set('galleries', $galleries)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/galerie')
                ->set('pagecount', $galleryPageCount);

        $layoutView->set(View::META_CANONICAL, $canonical)
                ->set(View::META_TITLE, 'Hastrman - Galerie');
    }

    /**
     * Show gallery detail with photos displayed in grid.
     *
     * @param string $urlKey
     */
    public function detail($urlKey, $page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $limit = 30;

        $gallery = \App\Model\GalleryModel::fetchPublicActiveGalleryByUrlkey($urlKey);

        if ($gallery === null) {
            self::redirect('/nenalezeno');
        }

        $photos = \App\Model\PhotoModel::fetchPhotosByGalleryIdPaged($gallery->getId(), $limit, $page);
        $videos = \App\Model\VideoModel::fetchActiveByGalleryId($gallery->getId());

        $photosCount = \App\Model\PhotoModel::count(['active = ?' => true, 'galleryId = ?' => $gallery->getId()]);
        $photosPageCount = ceil($photosCount / 30);

        $this->pagerMetaLinks($photosPageCount, $page, '/galerie/' . $gallery->getUrlKey() . '/p/');
        $canonical = $this->getServerHost() . '/galerie/r/' . $gallery->getUrlKey();

        $view->set('gallery', $gallery)
                ->set('photos', $photos)
                ->set('videos', $videos)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/galerie/' . $gallery->getUrlKey())
                ->set('pagecount', $photosPageCount);

        $layoutView->set(View::META_CANONICAL, $canonical)
                ->set(View::META_TITLE, 'Hastrman - Galerie - ' . $gallery->getTitle());
    }

    /**
     * Show gallery detail as slide show.
     *
     * @param string $urlKey
     */
    public function slideShow($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $galleryNoPhotos = \App\Model\GalleryModel::fetchPublicActiveGalleryByUrlkey($urlKey);

        if ($galleryNoPhotos === null) {
            self::redirect('/nenalezeno');
        }

        $gallery = $galleryNoPhotos->getActPhotosForGallery();

        $canonical = $this->getServerHost() . '/galerie/r/' . $gallery->getUrlKey();

        $view->set('gallery', $gallery);

        $layoutView->set(View::META_CANONICAL, $canonical)
                ->set('includejssorslider', 1)
                ->set(View::META_TITLE, 'Hastrman - Galerie - ' . $gallery->getTitle());
    }

}
