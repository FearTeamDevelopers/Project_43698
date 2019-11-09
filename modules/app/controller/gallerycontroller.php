<?php

namespace App\Controller;

use App\Etc\Controller;
use App\Model\GalleryModel;
use App\Model\PhotoModel;
use App\Model\VideoModel;
use THCFrame\Model\Exception\Connector;
use THCFrame\Model\Exception\Implementation;
use THCFrame\View\Exception\Data;
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
     * @throws Connector
     * @throws Implementation
     * @throws Data
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
            $galleries = GalleryModel::fetchPublicActiveGalleries(30, $page);
            $this->getCache()->set('gallery-' . $page, $galleries);
        }

        $galleryCount = GalleryModel::count(
            [
                'active = ?' => true,
                'isPublic = ?' => true,
            ]
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
     * @param int $page
     * @throws Connector
     * @throws Implementation
     * @throws Data
     */
    public function detail($urlKey, $page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $limit = 30;

        $gallery = GalleryModel::fetchPublicActiveGalleryByUrlkey($urlKey);

        if ($gallery === null) {
            self::redirect('/nenalezeno');
        }

        $photos = PhotoModel::fetchPhotosByGalleryIdPaged($gallery->getId(), $limit, $page);
        $videos = VideoModel::fetchActiveByGalleryId($gallery->getId());

        $photosCount = PhotoModel::count(['active = ?' => true, 'galleryId = ?' => $gallery->getId()]);
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
     * @throws Data
     */
    public function slideShow($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $galleryNoPhotos = GalleryModel::fetchPublicActiveGalleryByUrlkey($urlKey);

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
