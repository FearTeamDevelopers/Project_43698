<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Events\Events as Event;

/**
 *
 */
class CommentController extends Controller
{
    /**
     * Delete existing comment.
     *
     * @before _secured, _admin
     * @param int $id comment id
     */
    public function delete($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $comment = \App\Model\CommentModel::first(['id = ?' => (int) $id]);

        if (null === $comment) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $comment->setDeleted(true);

            if ($comment->validate()) {
                $comment->save();
                Event::fire('admin.log', ['success', 'Comment id: '.$id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', ['fail', 'Comment id: '.$id,
                    'Errors: '.json_encode($comment->getErrors()), ]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }
}
