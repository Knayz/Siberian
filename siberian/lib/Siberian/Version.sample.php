<?php

namespace Siberian;

/**
 * Class Version
 * @package Siberian
 */
class Version
{
    const TYPE = 'SAE';
    const NAME = 'Single App Edition';
    const VERSION = '4.20.39';
    const PREVIOUS_VERSION = '4.20.38';
    const NATIVE_VERSION = '20';
    const API_VERSION = '4';

    /**
     * @param string|array $type
     * @return bool
     */
    static function is($type)
    {
        if (is_array($type)) {
            foreach ($type as $t) {
                if (self::TYPE == strtoupper($t)) {
                    return true;
                }
            }
        }
        return self::TYPE == strtoupper($type);
    }
}
