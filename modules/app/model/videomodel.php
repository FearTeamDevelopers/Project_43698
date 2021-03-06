<?php

namespace App\Model;

use App\Model\Basic\BasicVideoModel;

/**
 * Email template ORM class.
 */
class VideoModel extends BasicVideoModel
{

    /**
     *
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setActive(true);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * Check whether action unique identifier already exist or not.
     *
     * @param string $key
     *
     * @return bool
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function checkUrlKey($key)
    {
        $status = self::first(['urlKey = ?' => $key]);

        return null === $status;
    }

    public static function fetchAll()
    {
        return self::all();
    }

    public static function fetchActiveByGalleryId($galleryId)
    {
        $videos = self::all(['galleryId = ?' => (int) $galleryId, 'active = ?' => true],
                ['*'],
                ['created' => 'desc']
        );

        return $videos;
    }

    public static function fetchAllByGalleryId($galleryId)
    {
        $videos = self::all(['galleryId = ?' => (int) $galleryId],
                ['*'],
                ['created' => 'desc']
        );

        return $videos;
    }

    public static function createFromPost(\THCFrame\Bag\BagInterface $post, array $options = [])
    {
        $errors = [];
        $urlParts = parse_url($post->get('url'));
        $youTubeVideoCode = '';

        if (isset($urlParts['host']) && $urlParts['host'] === 'www.youtube.com') {
            if (isset($urlParts['query']) && !empty($urlParts['query'])) {
                parse_str($urlParts['query'], $query);
                $youTubeVideoCode = $query['v'];
            } else {
                $errors['url'] = ['V url videa není přítomen kód videa (?v=xxxxxx)'];
            }
        } else {
            $errors['url'] = ['Video musí být z www.youtube.com'];
        }

        $video = new static([
            'galleryId' => $post->get('galleryid'),
            'active' => 1,
            'url' => $post->get('url'),
            'videoCode' => $youTubeVideoCode,
            'created' => date('Y-m-d H:i'),
            'modified' => date('Y-m-d H:i'),
        ]);

        return [$video, $errors];
    }

}
