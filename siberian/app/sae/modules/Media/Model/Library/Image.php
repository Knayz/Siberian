<?php

/**
 * Class Media_Model_Library_Image
 */
class Media_Model_Library_Image extends Core_Model_Default
{

    const PATH = '/images/library';
    const APPLICATION_PATH = '/images/application/%d/icons';

    /**
     * @var string
     */
    protected $_db_table = Media_Model_Db_Table_Library_Image::class;

    /**
     * @param string $path
     * @param null $app_id
     * @return string
     */
    public static function getImagePathTo($path = '', $app_id = null)
    {

        if (!empty($path) AND substr($path, 0, 1) != '/') {
            $path = '/' . $path;
        }

        if (!is_null($app_id)) {
            $path = sprintf(self::APPLICATION_PATH . $path, $app_id);
        } else if (strpos($path, "/app") === 0) {
            # Do nothing for /app/* from modules
        } else {
            $path = self::PATH . $path;
        }

        return Core_Model_Directory::getPathTo($path);
    }

    public static function getBaseImagePathTo($path = '', $app_id = null)
    {

        if (!empty($path) AND substr($path, 0, 1) != '/') $path = '/' . $path;

        if (!is_null($app_id)) {
            $path = sprintf(self::APPLICATION_PATH . $path, $app_id);
        } else if (strpos($path, "/app") === 0) {
            # Do nothing for /app/* from modules
        } else {
            $path = self::PATH . $path;
        }

        return Core_Model_Directory::getBasePathTo($path);

    }

    /**
     * The params are prefixed with __ to avoid conflict with internal params.
     *
     * @deprecated will be deprecated in 4.2.x `
     *
     * @param string $__url
     * @param array $__params
     * @param null $__locale
     * @return string
     */
    public function getUrl($__url = '', array $__params = array(), $__locale = null)
    {
        $url = '';
        if ($this->getLink()) {
            $url = self::getImagePathTo($this->getLink(), $this->getAppId());
            $base_url = self::getBaseImagePathTo($this->getLink(), $this->getAppId());
            if (!file_exists($base_url)) {
                $url = '';
            }
        }

        if (empty($url)) {
            $url = $this->getNoImage();
        }
        return $url;

    }

    public function getSecondaryUrl()
    {
        $url = '';
        if ($this->getSecondaryLink()) {
            $url = self::getImagePathTo($this->getSecondaryLink());
            if (!file_exists(self::getBaseImagePathTo($this->getSecondaryLink()))) $url = '';
        }

        if (empty($url)) {
            $url = $this->getNoImage();
        }

        return $url;

    }

    public function getThumbnailUrl()
    {
        $url = '';
        if ($this->getThumbnail()) {
            $url = self::getImagePathTo($this->getThumbnail());
            if (!file_exists(self::getBaseImagePathTo($this->getThumbnail()))) $url = '';
        }

        if (empty($url)) {
            $url = $this->getUrl();
        }

        return $url;
    }

    public function updatePositions($positions)
    {
        $this->getTable()->updatePositions($positions);

        return $this;
    }

    /**
     * Keywords for icon library filters
     * @return string
     */
    public function getFilterKeywords()
    {
        $link = $this->getLink();

        // Link must be filtered off from regular words
        $link = str_replace([
            'app/',
            'sae/',
            'mae/',
            'pe/',
            'local/',
            'modules/',
            'resources/',
            'features/',
            'icons/',
            'media/',
            'library/',
            '.png',
            '.jpg',
            '.jpeg',
            '.bmp',
            '.gif',
        ], '', $link);
        $link = str_replace('/', ',', $link);
        $link = preg_replace("/\d/", '', $link); // Also replace numbers
        $link = strtolower(trim(preg_replace("/,+/", ',', $link), ','));

        $keywords = $link.','.$this->getKeywords();

        $list = explode(',', $keywords);
        $list = array_keys(array_flip($list));

        // Automatically adds keywords to the translation file
        foreach ($list as $l) {
            extract_p__('keywords', $l, null, true);
        }

        $withTranslation = $list;
        foreach ($list as $l) {
            $withTranslation[] = strtolower(p__('keywords', $l));
        }
        // Again, remove dupes*
        $withTranslation = array_keys(array_flip($withTranslation));

        return implode(',', $withTranslation);
    }
}
