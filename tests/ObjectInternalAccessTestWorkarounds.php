<?php

use PHPUnit\Framework\Assert;

/**
 * @author    Craig Gosman <craig@ingenerator.com>
 * @licence   proprietary
 *
 *
 * This is hideous so is self contained in a file and uses traits so we can find them and refactor in the future
 */
trait ObjectInternalAccessTestWorkarounds
{
    public static function assertAttributeNotSame(
        $expected,
        string $actualAttributeName,
        $actualClassOrObject,
        string $message = ''
    ): void {
        Assert::assertNotSame($expected, self::readAttribute($actualClassOrObject, $actualAttributeName), $message);
    }

    static function assertAttributeSame(
        $expected,
        string $actualAttributeName,
        $actualClassOrObject,
        string $message = ''
    ): void {
        Assert::assertSame($expected, self::readAttribute($actualClassOrObject, $actualAttributeName), $message);
    }

    public static function readAttribute($classOrObject, string $attributeName)
    {
        $reflectionProperty = new \ReflectionProperty($classOrObject, $attributeName);
        $reflectionProperty->setAccessible(TRUE);

        return $reflectionProperty->getValue($classOrObject);
    }
}
