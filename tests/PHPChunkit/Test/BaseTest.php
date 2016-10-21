<?php

namespace PHPChunkit\Test;

use PHPUnit_Framework_TestCase;

abstract class BaseTest extends PHPUnit_Framework_TestCase
{
    protected function buildPartialMock($className, array $mockedMethods, array $constructorArgs = null)
    {
        $builder = $this->getMockBuilder($className)
            ->setMethods($mockedMethods);

        if ($constructorArgs) {
            $builder->setConstructorArgs($constructorArgs);
        } else {
            $builder->disableOriginalConstructor();
        }

        return $builder->getMock();
    }

    protected function getRootDir()
    {
        return realpath(__DIR__.'/../../..');
    }

    protected function getTestsDirectory()
    {
        return realpath(__DIR__.'/../../../tests');
    }
}
