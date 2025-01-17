<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @method \Pimcore\Model\WebsiteSetting\Dao getDao()
 * @method void save()
 */
final class WebsiteSetting extends AbstractModel
{
    /**
     * @var int|null
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $language;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var int|null
     */
    protected $siteId;

    /**
     * @var int|null
     */
    protected $creationDate;

    /**
     * @var int|null
     */
    protected $modificationDate;

    /**
     * this is a small per request cache to know which website setting is which is, this info is used in self::getByName()
     *
     * @var array
     */
    protected static $nameIdMappingCache = [];

    /**
     * @param string $name
     * @param int|null $siteId
     * @param string|null $language
     *
     * @return string
     */
    protected static function getCacheKey($name, $siteId = null, $language = null): string
    {
        return $name . '~~~' . $siteId . '~~~' . $language;
    }

    /**
     * @param int $id
     *
     * @return WebsiteSetting|null
     */
    public static function getById($id)
    {
        $cacheKey = 'website_setting_' . $id;

        try {
            $setting = \Pimcore\Cache\RuntimeCache::get($cacheKey);
            if (!$setting) {
                throw new \Exception('Website setting in registry is null');
            }
        } catch (\Exception $e) {
            try {
                $setting = new self();
                $setting->getDao()->getById((int)$id);
                \Pimcore\Cache\RuntimeCache::set($cacheKey, $setting);
            } catch (NotFoundException $e) {
                return null;
            }
        }

        return $setting;
    }

    /**
     * @param string $name name of the config
     * @param int|null $siteId site ID
     * @param string|null $language language, if property cannot be found the value of property without language is returned
     * @param string|null $fallbackLanguage fallback language
     *
     * @return null|WebsiteSetting
     *
     * @throws \Exception
     */
    public static function getByName($name, $siteId = null, $language = null, $fallbackLanguage = null)
    {
        $nameCacheKey = static::getCacheKey($name, $siteId, $language);

        // check if pimcore already knows the id for this $name, if yes just return it
        if (array_key_exists($nameCacheKey, self::$nameIdMappingCache)) {
            return self::getById(self::$nameIdMappingCache[$nameCacheKey]);
        }

        // create a tmp object to obtain the id
        $setting = new self();

        try {
            $setting->getDao()->getByName($name, $siteId, $language);
        } catch (NotFoundException $e) {
            if ($language != $fallbackLanguage) {
                $result = self::getByName($name, $siteId, $fallbackLanguage, $fallbackLanguage);

                return $result;
            }

            return null;
        }

        // to have a singleton in a way. like all instances of Element\ElementInterface do also, like DataObject\AbstractObject
        if ($setting->getId() > 0) {
            // add it to the mini-per request cache
            self::$nameIdMappingCache[$nameCacheKey] = $setting->getId();

            return self::getById($setting->getId());
        }

        return $setting;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $creationDate
     *
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param mixed $data
     *
     * @return $this
     */
    public function setData($data)
    {
        if ($data instanceof ElementInterface) {
            $this->setType(Service::getElementType($data));
            $data = $data->getId();
        }

        $this->data = $data;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        // lazy-load data of type asset, document, object
        if (in_array($this->getType(), ['document', 'asset', 'object']) && !$this->data instanceof ElementInterface && is_numeric($this->data)) {
            return Element\Service::getElementById($this->getType(), $this->data);
        }

        return $this->data;
    }

    /**
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $siteId
     *
     * @return $this
     */
    public function setSiteId($siteId)
    {
        $this->siteId = (int) $siteId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @internal
     */
    public function clearDependentCache()
    {
        \Pimcore\Cache::clearTag('website_config');
    }

    public function delete(): void
    {
        $nameCacheKey = self::getCacheKey($this->getName(), $this->getSiteId(), $this->getLanguage());

        // Remove cached element to avoid returning it with e.g. getByName() after if it is deleted
        if (array_key_exists($nameCacheKey, self::$nameIdMappingCache)) {
            unset(self::$nameIdMappingCache[$nameCacheKey]);
        }

        $this->getDao()->delete();
    }
}
