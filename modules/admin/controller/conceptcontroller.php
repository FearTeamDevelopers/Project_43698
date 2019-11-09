<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use Admin\Model\ConceptModel;
use THCFrame\Events\Events as Event;
use THCFrame\Model\Exception\Connector;
use THCFrame\Model\Exception\Implementation;
use THCFrame\Model\Exception\Validation;
use THCFrame\Request\RequestMethods;

/**
 *
 */
class ConceptController extends Controller
{

    /**
     * @before _secured, _participant
     * @throws Connector
     * @throws Implementation
     * @throws Validation
     */
    public function store(): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), 403, true);
        }

        $conceptId = RequestMethods::post('conceptid', 0);

        if ((int)$conceptId === 0) {
            $concept = new ConceptModel([
                'userId' => $this->getUser()->getId(),
                'type' => RequestMethods::post('type'),
                'title' => RequestMethods::post('title'),
                'shortBody' => RequestMethods::post('shorttext'),
                'body' => RequestMethods::post('text'),
                'keywords' => RequestMethods::post('keywords'),
                'metaTitle' => RequestMethods::post('metatitle'),
                'metaDescription' => RequestMethods::post('metadescription'),
                'created' => date('Y-m-d H:i'),
                'modified' => date('Y-m-d H:i'),
            ]);
            if ($concept->validate()) {
                $id = $concept->save();

                Event::fire('admin.log', ['success', 'Concept id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'), false, 200, ['conceptid' => $id]);
            } else {
                Event::fire('admin.log', [
                    'fail',
                    'Concept id: new concept' .
                    ' Errors: ' . json_encode($concept->getErrors()),
                ]);

                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        } else {
            $concept = ConceptModel::first(['id = ?' => (int)$conceptId]);

            $concept->title = RequestMethods::post('title');
            $concept->shortBody = RequestMethods::post('shorttext');
            $concept->body = RequestMethods::post('text');
            $concept->keywords = RequestMethods::post('keywords');
            $concept->metaTitle = RequestMethods::post('metatitle');
            $concept->metaDescription = RequestMethods::post('metadescription');

            if ($concept->validate()) {
                $concept->save();

                Event::fire('admin.log', ['success', 'Concept id: ' . $concept->getId()]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'), false, 200, ['conceptid' => $concept->getId()]);
            } else {
                Event::fire('admin.log', [
                    'fail',
                    'Concept id: ' . $conceptId .
                    ' Errors: ' . json_encode($concept->getErrors()),
                ]);

                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * @before _secured, _participant
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

        $concept = ConceptModel::first(['id = ?' => (int)$id, 'userId = ?' => $this->getUser()->getId()]);

        if (null === $concept) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } elseif ($concept->delete()) {
            Event::fire('admin.log', ['success', 'Concept id: ' . $id]);
            $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
        } else {
            Event::fire('admin.log', [
                'fail',
                'Concept id: ' . $id,
                'Errors: ' . json_encode($concept->getErrors()),
            ]);
            $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
        }
    }

}
