<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Events\Events as Event;

/**
 *
 */
class FeedbackController extends Controller
{

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();

        $feedbacks = \App\Model\FeedbackModel::all([], ['*'], ['created' => 'desc'], 100);

        $view->set('feedbacks', $feedbacks);
    }

    /**
     * @before _secured, _admin
     * @param int $id       feedbakc id
     */
    public function delete($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $feedback = \App\Model\FeedbackModel::first(['id = ?' => (int) $id]);

        if (null === $feedback) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            if (\App\Model\FeedbackModel::deleteAll(['id = ?' => $feedback->getId()]) != -1) {
                Event::fire('admin.log', ['success', 'Delete feedback: ' . $feedback->getId()]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', ['fail', 'Delete feedback: ' . $feedback->getId()]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

}
