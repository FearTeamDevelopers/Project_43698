<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use App\Model\FeedbackModel;
use THCFrame\Events\Events as Event;
use THCFrame\Model\Exception\Connector;
use THCFrame\Model\Exception\Implementation;
use THCFrame\View\Exception\Data;

/**
 *
 */
class FeedbackController extends Controller
{

    /**
     * @before _secured, _admin
     * @throws Connector
     * @throws Implementation
     * @throws Data
     */
    public function index(): void
    {
        $view = $this->getActionView();

        $feedbacks = FeedbackModel::all([], ['*'], ['created' => 'desc'], 100);

        $view->set('feedbacks', $feedbacks);
    }

    /**
     * @before _secured, _admin
     * @param int $id feedbakc id
     * @throws Connector
     * @throws Implementation
     */
    public function delete($id): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $feedback = FeedbackModel::first(['id = ?' => (int)$id]);

        if (null === $feedback) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } elseif (FeedbackModel::deleteAll(['id = ?' => $feedback->getId()]) != -1) {
            Event::fire('admin.log', ['success', 'Delete feedback: ' . $feedback->getId()]);
            $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
        } else {
            Event::fire('admin.log', ['fail', 'Delete feedback: ' . $feedback->getId()]);
            $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
        }
    }

}
