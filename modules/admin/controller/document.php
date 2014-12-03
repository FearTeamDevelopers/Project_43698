<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;

/**
 * 
 */
class Admin_Controller_Document extends Controller
{

    /**
     * @before _secured, _participant
     */
    public function index()
    {
        $view = $this->getActionView();
        $documents = App_Model_Document::all();
        $view->set('documents', $documents);
    }

    /**
     * @before _secured, _participant
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $doc = App_Model_Document::first(
                        array('id = ?' => $id), array('id', 'filepath', 'userId')
        );

        if (NULL === $doc) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if ($this->_security->isGranted('role_admin') === true ||
                    $doc->getUserId() == $this->getUser()->getId()) {

                $filepath = $doc->getUnlinkPath();

                if ($doc->delete()) {
                    @unlink($filepath);
                    Event::fire('admin.log', array('success', 'Document id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Document id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            } else {
                echo self::ERROR_MESSAGE_4;
            }
        }
    }

    /**
     * @before _secured, _participant
     */
    public function changeStatus($id)
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;

        $doc = App_Model_Document::first(array('id = ?' => $id));

        if (null === $doc) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if (!$doc->active) {
                $doc->active = true;

                if ($doc->validate()) {
                    $doc->save();
                    Event::fire('admin.log', array('success', 'Document id: ' . $id));
                    echo 'active';
                } else {
                    echo join('<br/>', $doc->getErrors());
                }
            } elseif ($doc->active) {
                $doc->active = false;

                if ($doc->validate()) {
                    $doc->save();
                    Event::fire('admin.log', array('success', 'Document id: ' . $id));
                    echo 'inactive';
                } else {
                    echo join('<br/>', $doc->getErrors());
                }
            }
        }
    }

    /**
     * @before _secured, _participant
     */
    public function upload()
    {
        $view = $this->getActionView();

        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddDocument')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/document/');
            }

            $errors = array();

            try {
                $fileManager = new FileManager();

                $fileErrors = $fileManager->upload('secondfile', $this->getUser()->getId(), time() . '_', false, true)->getUploadErrors();
                $files = $fileManager->getUploadedFiles();
            } catch (Exception $ex) {
                $errors['secondfile'][] = $ex->getMessage();
            }

            if (!empty($files)) {
                foreach ($files as $i => $file) {
                    if ($file instanceof \THCFrame\Filesystem\File) {
                        $doc = new App_Model_Document(array(
                            'userId' => $this->getUser()->getId(),
                            'userAlias' => $this->getUser()->getWholeName(),
                            'filepath' => trim($file->getFilename(), '.'),
                            'description' => RequestMethods::post('description'),
                            'rank' => RequestMethods::post('rank', 1),
                            'filename' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                            'format' => $file->getFormat(),
                            'size' => $file->getSize()
                        ));

                        if ($doc->validate()) {
                            $aid = $doc->save();

                            Event::fire('admin.log', array('success', 'Document id: ' . $aid));
                        } else {
                            Event::fire('admin.log', array('fail'));
                            $errors['secondfile'][] = $doc->getErrors();
                        }
                    }
                }
            }

            if (!empty($fileErrors)) {
                $errors['secondfile'] = $fileErrors;
            }

            if (empty($errors['secondfile'])) {
                $view->successMessage(self::SUCCESS_MESSAGE_7);
                self::redirect('/admin/document/');
            } else {
                $view->set('errors', $errors)
                        ->set('file', $doc);
            }
        }
    }

}
