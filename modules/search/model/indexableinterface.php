<?php

namespace Search\Model;

/**
 *
 * @author Tomy
 */
interface IndexableInterface
{

    public function getId();

    public function getBody();

    public function getMetaDescription();

    public function getKeywords();

    public function getUrlKey();

    public function getTitle();

    public function getCreated();
}
