<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use Admin\Model\ImessageModel;
use THCFrame\Events\Events as Event;
use THCFrame\Model\Exception\Connector;
use THCFrame\Model\Exception\Implementation;
use THCFrame\Model\Exception\Validation;
use THCFrame\Request\RequestMethods;
use THCFrame\View\Exception\Data;

/**
 *
 */
class ImessageController extends Controller
{
    /**
     * @before _secured, _superadmin
     * @throws Data
     */
    public function index(): void
    {
        $view = $this->getActionView();

        $imessages = ImessageModel::fetchAll();

        $view->set('imessages', $imessages);
    }

    /**
     * @before _secured, _superadmin
     * @throws Data
     * @throws Connector
     * @throws Implementation
     * @throws Validation
     */
    public function add(): void
    {
        $view = $this->getActionView();

        $view->set('imessage', null);

        if (RequestMethods::post('submitAddImessage')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/imessage/');
            }

            $imessage = new ImessageModel([
                'userId' => $this->getUser()->getId(),
                'messageType' => RequestMethods::post('mtype'),
                'userAlias' => $this->getUser()->getWholeName(),
                'title' => RequestMethods::post('title'),
                'body' => RequestMethods::post('text'),
                'displayFrom' => RequestMethods::post('dfrom'),
                'displayTo' => RequestMethods::post('dto'),
                'created' => date('Y-m-d H:i'),
                'modified' => date('Y-m-d H:i'),
            ]);

            if ($imessage->validate()) {
                $id = $imessage->save();

                Event::fire('admin.log', ['success', 'Imessage id: ' . $id]);
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/admin/imessage/');
            } else {
                Event::fire('admin.log', ['fail', 'Errors: ' . json_encode($imessage->getErrors())]);
                $view->set('errors', $imessage->getErrors())
                    ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                    ->set('imessage', $imessage);
            }
        }
    }

    /**
     * @before _secured, _superadmin
     *
     * @param type $id
     * @throws Data
     * @throws Connector
     * @throws Implementation
     */
    public function edit($id): void
    {
        $view = $this->getActionView();

        $imessage = ImessageModel::first(['id = ?' => (int)$id]);

        if (null === $imessage) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->willRenderActionView = false;
            self::redirect('/admin/imessage/');
        }

        $view->set('imessage', $imessage);

        if (RequestMethods::post('submitEditImessage')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/imessage/');
            }

            $imessage->messageType = RequestMethods::post('mtype');
            $imessage->title = RequestMethods::post('title');
            $imessage->body = RequestMethods::post('text');
            $imessage->displayFrom = RequestMethods::post('dfrom');
            $imessage->displayTo = RequestMethods::post('dto');
            $imessage->active = RequestMethods::post('active');

            if ($imessage->validate()) {
                $imessage->save();

                Event::fire('admin.log', ['success', 'Imessage id: ' . $id]);
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/imessage/');
            } else {
                Event::fire('admin.log', [
                    'fail',
                    'Imessage id: ' . $id,
                    'Errors: ' . json_encode($imessage->getErrors()),
                ]);
                $view->set('errors', $imessage->getErrors());
            }
        }
    }

    /**
     * @before _secured, _superadmin
     *
     * @param type $id
     * @throws Connector
     * @throws Implementation
     */
    public function delete($id): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $imessage = ImessageModel::first(['id = ?' => $id]);

        if (null === $imessage) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } elseif ($imessage->delete()) {
            Event::fire('admin.log', ['success', 'Imessage id: ' . $id]);
            $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
        } else {
            Event::fire('admin.log', ['fail', 'Imessage id: ' . $id]);
            $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
        }
    }
}
