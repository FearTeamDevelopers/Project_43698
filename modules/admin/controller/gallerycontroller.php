<?php
namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Registry\Registry;
use THCFrame\Core\Core;
use THCFrame\Core\StringMethods;
use THCFrame\Core\ArrayMethods;
use App\Model\GalleryModel;

/**
 *
 */
class GalleryController extends Controller
{

    /**
     * Check whether user has access to gallery or not.
     *
     * @param GalleryModel $gallery
     *
     * @return bool
     */
    private function _checkAccess(GalleryModel $gallery)
    {
        if ($this->isAdmin() === true ||
            $gallery->getUserId() == $this->getUser()->getId()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get list of all gelleries.
     *
     * @before _secured, _participant
     */
    public function index()
    {
        $view = $this->getActionView();

        $galleries = GalleryModel::all();

        $view->set('galleries', $galleries);
    }

    /**
     * Create new gallery.
     *
     * @before _secured, _participant
     */
    public function add()
    {
        $view = $this->getActionView();

        $view->set('gallery', null);

        if (RequestMethods::post('submitAddGallery')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/gallery/');
            }

            $errors = [];
            $urlKey = StringMethods::createUrlKey(RequestMethods::post('title'));

            if (!GalleryModel::checkUrlKey($urlKey)) {
                $errors['title'] = [$this->lang('ARTICLE_TITLE_IS_USED')];
            }

            $gallery = new GalleryModel([
                'title' => RequestMethods::post('title'),
                'userId' => $this->getUser()->getId(),
                'userAlias' => $this->getUser()->getWholeName(),
                'isPublic' => RequestMethods::post('public', 1),
                'urlKey' => $urlKey,
                'avatarPhotoId' => 0,
                'description' => RequestMethods::post('description'),
                'rank' => RequestMethods::post('rank', 1),
            ]);

            if (empty($errors) && $gallery->validate()) {
                $id = $gallery->save();
                $this->getCache()->erase('gallery');

                Event::fire('admin.log', ['success', 'Gallery id: ' . $id]);
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/admin/gallery/detail/' . $id);
            } else {
                Event::fire('admin.log', ['fail', 'Errors: ' . json_encode($errors + $gallery->getErrors())]);
                $view->set('gallery', $gallery)
                    ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                    ->set('errors', $errors + $gallery->getErrors());
            }
        }
    }

    /**
     * Show detail of existing gallery.
     *
     * @before _secured, _participant
     *
     * @param int $id gallery id
     */
    public function detail($id)
    {
        $view = $this->getActionView();

        $gallery = GalleryModel::fetchGalleryById((int) $id);

        if (null === $gallery) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->willRenderActionView = false;
            self::redirect('/admin/gallery/');
        }

        $view->set('gallery', $gallery)
            ->set('video', null);
    }

    /**
     * Edit existing gallery.
     *
     * @before _secured, _participant
     *
     * @param int $id gallery id
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $gallery = GalleryModel::fetchGalleryById((int) $id);

        if (null === $gallery) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->willRenderActionView = false;
            self::redirect('/admin/gallery/');
        }

        if (!$this->_checkAccess($gallery)) {
            $view->warningMessage($this->lang('LOW_PERMISSIONS'));
            $this->willRenderActionView = false;
            self::redirect('/admin/gallery/');
        }

        $view->set('gallery', $gallery);

        if (RequestMethods::post('submitEditGallery')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/gallery/');
            }

            $errors = [];
            $urlKey = StringMethods::createUrlKey(RequestMethods::post('title'));

            if ($gallery->getUrlKey() !== $urlKey && !GalleryModel::checkUrlKey($urlKey)) {
                $errors['title'] = [$this->lang('ARTICLE_TITLE_IS_USED')];
            }

            if (null === $gallery->userId) {
                $gallery->userId = $this->getUser()->getId();
                $gallery->userAlias = $this->getUser()->getWholeName();
            }

            $gallery->title = RequestMethods::post('title');
            $gallery->isPublic = RequestMethods::post('public');
            $gallery->active = RequestMethods::post('active');
            $gallery->urlKey = $urlKey;
            $gallery->rank = RequestMethods::post('rank', 1);
            $gallery->description = RequestMethods::post('description');
            $gallery->avatarPhotoId = RequestMethods::post('avatar');

            if (empty($errors) && $gallery->validate()) {
                $gallery->save();
                $this->getCache()->erase('gallery');

                Event::fire('admin.log', ['success', 'Gallery id: ' . $id]);
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/gallery/detail/' . $id);
            } else {
                Event::fire('admin.log', [
                    'fail',
                    'Gallery id: ' . $id,
                    'Errors: ' . json_encode($errors + $gallery->getErrors())
                ]);
                $view->set('errors', $gallery->getErrors());
            }
        }
    }

    /**
     * Delete existing gallery and all photos (files and db)
     *
     * @before _secured, _participant
     * @param int $id gallery id
     */
    public function delete($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $gallery = GalleryModel::first(
                ['id = ?' => (int) $id], ['id', 'title', 'created', 'userId', 'urlKey']
        );

        if (null === $gallery) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            if ($this->_checkAccess($gallery)) {
                GalleryModel::deleteAllPhotos($gallery->getId());

                if ($gallery->delete()) {
                    $this->getCache()->erase('gallery');
                    Event::fire('admin.log', ['success', 'Gallery id: ' . $id]);
                    $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
                } else {
                    Event::fire('admin.log', [
                        'fail',
                        'Gallery id: ' . $id,
                        'Errors: ' . json_encode($gallery->getErrors())
                    ]);
                    $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
                }
            } else {
                $this->ajaxResponse($this->lang('LOW_PERMISSIONS'), true, 401);
            }
        }
    }

    /**
     * Delete all photos (files and db) in gallery
     *
     * @before _secured, _participant
     * @param int $id gallery id
     */
    public function deleteAllPhotos($id)
    {
        $this->disableView();
        $view = $this->getActionView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $gallery = GalleryModel::first(
                ['id = ?' => (int) $id], ['id', 'title', 'created', 'userId', 'urlKey']
        );

        if (null === $gallery) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            if ($this->_checkAccess($gallery)) {
                try {
                    GalleryModel::deleteAllPhotos($gallery->getId(), true);

                    Event::fire('admin.log', ['success', 'Delete all photos in gallery id: ' . $id]);
                    $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
                } catch (\Exception $ex) {
                    Event::fire('admin.log', [
                        'fail',
                        'Gallery id: ' . $id,
                        'Errors: ' . json_encode($gallery->getErrors()),
                        'Exception: ' . $ex->getMessage(),
                    ]);
                    $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
                }
            } else {
                $this->ajaxResponse($this->lang('LOW_PERMISSIONS'), true, 401);
            }
        }
    }

    /**
     * Return list of galleries to insert gallery link to content.
     *
     * @before _secured, _participant
     */
    public function insertToContent()
    {
        $view = $this->getActionView();
        $this->willRenderLayoutView = false;

        $galleries = GalleryModel::all([], ['urlKey', 'title']);

        $view->set('galleries', $galleries);
    }

    /**
     * Upload photo into gallery.
     *
     * @before _secured, _participant
     *
     * @param int $id gallery id
     */
    public function upload()
    {
        $this->disableView();

        if (RequestMethods::post('submitUpload')) {
            $galleryId = RequestMethods::post('galleryid');
            $gallery = GalleryModel::first(
                    [
                    'id = ?' => (int) $galleryId,
                    'active = ?' => true,
                    ], ['id', 'title', 'userId', 'urlKey']
            );

            if (null === $gallery) {
                header('HTTP/1.0 404 Not Found');
                echo $this->lang('NOT_FOUND');
                exit;
            }

            if (!$this->_checkAccess($gallery)) {
                header('HTTP/1.0 401 Unauthorized');
                echo $this->lang('LOW_PERMISSIONS');
                exit;
            }

            $errors = $uploadErrors = [];

            $fileManager = new FileManager([
                'thumbWidth' => $this->getConfig()->thumb_width,
                'thumbHeight' => $this->getConfig()->thumb_height,
                'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
                'maxImageWidth' => $this->getConfig()->photo_maxwidth,
                'maxImageHeight' => $this->getConfig()->photo_maxheight,
            ]);

            $fileErrors = $fileManager->uploadImage('file', 'gallery/' . $gallery->getUrlKey(), time() . '_')->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            if (!empty($fileErrors)) {
                header('HTTP/1.0 400 Bad Request');
                Core::getLogger()->error('Gallery image upload fail: {error}', ['error' => print_r($fileErrors, true)]);
                echo implode('<br/>', $fileErrors);
                exit;
            }

            if (!empty($files)) {
                foreach ($files as $i => $file) {
                    if ($file instanceof \THCFrame\Filesystem\Image) {
                        $info = $file->getOriginalInfo();

                        $photo = new \App\Model\PhotoModel([
                            'galleryId' => $gallery->getId(),
                            'imgMain' => trim($file->getFilename(), '.'),
                            'imgThumb' => trim($file->getThumbname(), '.'),
                            'description' => RequestMethods::post('description'),
                            'rank' => RequestMethods::post('rank', 1),
                            'photoName' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                            'mime' => $info['mime'],
                            'format' => $info['format'],
                            'width' => $file->getWidth(),
                            'height' => $file->getHeight(),
                            'size' => $file->getSize(),
                        ]);

                        if ($photo->validate()) {
                            $aid = $photo->save();

                            Event::fire('admin.log', ['success', 'Photo id: ' . $aid . ' in gallery ' . $gallery->getUrlKey()]);
                        } else {
                            Event::fire('admin.log', [
                                'fail',
                                'Photo in gallery ' . $gallery->getUrlKey(),
                                'Errors: ' . json_encode($photo->getErrors())
                            ]);
                            Core::getLogger()->error('Gallery image create db record fail: {error}', ['error' => print_r($photo->getErrors(), true)]);
                            $error = ArrayMethods::flatten($photo->getErrors());

                            header('HTTP/1.0 400 Bad Request');
                            echo implode('<br/>', $error);
                            exit;
                        }
                    }
                }
            }

            $errors['uploadfile'] = $uploadErrors;

            if (empty($errors['uploadfile'])) {
                $this->getCache()->erase('gallery');
                header('HTTP/1.0 200 OK');
                echo $this->lang('UPLOAD_SUCCESS');
                exit;
            } else {
                header('HTTP/1.0 400 Bad Request');
                echo $this->lang('UPLOAD_FAIL');
                exit;
            }
        }
    }

    /**
     * Delete photo.
     *
     * @before _secured, _participant
     *
     * @param int $id photo id
     */
    public function deletePhoto($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $photo = \App\Model\PhotoModel::first(
                ['id = ?' => $id], ['id', 'imgMain', 'imgThumb', 'galleryId']
        );

        if (null === $photo) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $gallery = GalleryModel::first(
                    ['id = ?' => (int) $photo->getGalleryId()], ['id', 'userId']
            );

            if (null === $gallery) {
                $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
            } else {
                if ($this->_checkAccess($gallery)) {
                    if ($photo->delete()) {
                        $this->getCache()->erase('gallery');
                        Event::fire('admin.log', ['success', 'Photo id: ' . $id]);
                        $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
                    } else {
                        Event::fire('admin.log', [
                            'fail',
                            'Photo id: ' . $id,
                            'Errors: ' . json_encode($photo->getErrors())
                        ]);
                        $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
                    }
                } else {
                    $this->ajaxResponse($this->lang('LOW_PERMISSIONS'), true, 401);
                }
            }
        }
    }

    /**
     * Change photo state (active/inactive).
     *
     * @before _secured, _participant
     *
     * @param int $id photo id
     */
    public function changePhotoStatus($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $photo = \App\Model\PhotoModel::first(['id = ?' => (int) $id]);

        if (null === $photo) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $gallery = GalleryModel::first(
                    ['id = ?' => (int) $photo->getGalleryId()], ['id', 'userId']
            );

            if (null === $gallery) {
                $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
            } else {
                if ($this->_checkAccess($gallery)) {
                    if (!$photo->active) {
                        $photo->active = true;

                        if ($photo->validate()) {
                            $photo->save();
                            $this->getCache()->erase('gallery');

                            Event::fire('admin.log', ['success', 'Photo id: ' . $id]);
                            $this->ajaxResponse($this->lang('COMMON_SUCCESS'), false, 200, ['status' => 'active']);
                        } else {
                            $this->ajaxResponse(implode('<br/>', $photo->getErrors()), true);
                        }
                    } elseif ($photo->active) {
                        $photo->active = false;

                        if ($photo->validate()) {
                            $photo->save();
                            $this->getCache()->erase('gallery');

                            Event::fire('admin.log', ['success', 'Photo id: ' . $id]);
                            $this->ajaxResponse($this->lang('COMMON_SUCCESS'), false, 200, ['status' => 'inactive']);
                        } else {
                            Event::fire('admin.log', [
                                'fail',
                                'Photo id: ' . $id,
                                'Errors: ' . json_encode($photo->getErrors())
                            ]);
                            $this->ajaxResponse(implode('<br/>', $photo->getErrors()), true);
                        }
                    }
                } else {
                    $this->ajaxResponse($this->lang('LOW_PERMISSIONS'), true, 401);
                }
            }
        }
    }

    /**
     * @before _secured, _participant
     *
     * @param int $id photo id
     */
    public function changePhotoPosition($id)
    {
        
    }

    /**
     * Connect video from youtube.com to gallery
     *
     * @before _secured, _participant
     */
    public function connectVideo()
    {
        $view = $this->getActionView();

        if (RequestMethods::post('submitUploadVideo')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/gallery/');
            }

            $galleryId = RequestMethods::post('galleryid');

            if (empty($galleryId)) {
                $view->errorMessage('Galerie nebyla nalezena');
                self::redirect('/admin/gallery/');
            }

            list($video, $errors) = \App\Model\VideoModel::createFromPost(
                    RequestMethods::getPostDataBag(), ['user' => $this->getUser()]
            );

            if (empty($errors) && $video->validate()) {
                $id = $video->save();
                $this->getCache()->erase('gallery');

                Event::fire('admin.log', ['success', 'Video id: ' . $id . ' into gallery id: ' . $galleryId]);
                $view->successMessage('Video bylo úspěšně připojeno');
            } else {
                Event::fire('admin.log', ['fail', 'Errors: ' . json_encode($errors + $video->getErrors())]);
                $view->errorMessage('Během připojování videa do galerie se vyskytla chyba');
            }

            self::redirect('/admin/gallery/detail/' . $galleryId);
        }
    }

    /**
     * Change photo state (active/inactive).
     *
     * @before _secured, _participant
     *
     * @param int $id photo id
     */
    public function changeVideoStatus($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $video = \App\Model\VideoModel::first(['id = ?' => (int) $id]);

        if (null === $video) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $gallery = GalleryModel::first(
                    ['id = ?' => (int) $video->getGalleryId()], ['id', 'userId']
            );

            if (null === $gallery) {
                $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
            } else {
                if ($this->_checkAccess($gallery)) {
                    if (!$video->active) {
                        $video->active = true;

                        if ($video->validate()) {
                            $video->save();
                            $this->getCache()->erase('gallery');

                            Event::fire('admin.log', ['success', 'Video id: ' . $id]);
                            $this->ajaxResponse($this->lang('COMMON_SUCCESS'), false, 200, ['status' => 'active']);
                        } else {
                            $this->ajaxResponse(implode('<br/>', $video->getErrors()), true);
                        }
                    } elseif ($video->active) {
                        $video->active = false;

                        if ($video->validate()) {
                            $video->save();
                            $this->getCache()->erase('gallery');

                            Event::fire('admin.log', ['success', 'Video id: ' . $id]);
                            $this->ajaxResponse($this->lang('COMMON_SUCCESS'), false, 200, ['status' => 'inactive']);
                        } else {
                            Event::fire('admin.log', [
                                'fail',
                                'Video id: ' . $id,
                                'Errors: ' . json_encode($video->getErrors())
                            ]);
                            $this->ajaxResponse(implode('<br/>', $video->getErrors()), true);
                        }
                    }
                } else {
                    $this->ajaxResponse($this->lang('LOW_PERMISSIONS'), true, 401);
                }
            }
        }
    }

    /**
     * Delete photo.
     *
     * @before _secured, _participant
     *
     * @param int $id video id
     */
    public function deleteVideo($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $video = \App\Model\VideoModel::first(['id = ?' => (int) $id]);

        if (null === $video) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $gallery = GalleryModel::first(
                    ['id = ?' => (int) $video->getGalleryId()], ['id', 'userId']
            );

            if (null === $gallery) {
                $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
            } else {
                if ($this->_checkAccess($gallery)) {
                    if ($video->delete()) {
                        $this->getCache()->erase('gallery');
                        Event::fire('admin.log', ['success', 'Video id: ' . $id]);
                        $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
                    } else {
                        Event::fire('admin.log', [
                            'fail',
                            'Video id: ' . $id,
                            'Errors: ' . json_encode($video->getErrors())
                        ]);
                        $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
                    }
                } else {
                    $this->ajaxResponse($this->lang('LOW_PERMISSIONS'), true, 401);
                }
            }
        }
    }
}
