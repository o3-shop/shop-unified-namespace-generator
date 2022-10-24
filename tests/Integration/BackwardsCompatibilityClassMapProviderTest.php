<?php
/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify  
 * it under the terms of the GNU General Public License as published by  
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but 
 * WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU 
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\UnifiedNameSpaceGenerator\tests\Integration;

use \OxidEsales\UnifiedNameSpaceGenerator\BackwardsCompatibilityClassMapProvider;
use \OxidEsales\UnifiedNameSpaceGenerator\Exceptions\InvalidBackwardsCompatibilityClassMapException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * Class BackwardsCompatibilityClassMapProviderTest
 *
 * @package OxidEsales\UnifiedNameSpaceGenerator\tests
 */
class BackwardsCompatibilityClassMapProviderTest extends \PHPUnit_Framework_TestCase
{

    const ROOT_DIRECTORY = 'root';

    /**
     * @var vfsStreamDirectory
     */
    private $vfsStreamDirectory = null;

    /**
     * Test case that the BC map is missing
     */
    public function testGetClassMapDoesNotFindClassMap()
    {
        $this->setExpectedException(
            InvalidBackwardsCompatibilityClassMapException::class,
            '',
            BackwardsCompatibilityClassMapProvider::ERROR_CODE_MISSING_BACKWARDS_COMPATIBILITY_CLASS_MAP
        );

        $this->copyTestDataIntoVirtualFileSystem('case_noBackwardsCompatibilityMap');
        $factsMock = $this->getFactsMock('CE');

        $backwardsCompatibilityClassMapProvider = new BackwardsCompatibilityClassMapProvider($factsMock);
        $backwardsCompatibilityClassMapProvider->getClassMap();
    }

    /**
     * Test case that the BC map is no array.
     */
    public function testGetClassMapDoesNotFindFindArrayInClassMap()
    {
        $this->setExpectedException(
            InvalidBackwardsCompatibilityClassMapException::class,
            '',
            BackwardsCompatibilityClassMapProvider::ERROR_CODE_INVALID_BACKWARDS_COMPATIBILITY_CLASS_MAP
        );

        $this->copyTestDataIntoVirtualFileSystem('case_invalid');
        $factsMock = $this->getFactsMock('CE');

        $backwardsCompatibilityClassMapProvider = new BackwardsCompatibilityClassMapProvider($factsMock);
        $backwardsCompatibilityClassMapProvider->getClassMap();
    }

    /**
     * Test case that the BC map is no array.
     */
    public function testGetClassMapReturnsValidClassMap()
    {
        $this->copyTestDataIntoVirtualFileSystem('case_valid');
        $factsMock = $this->getFactsMock('CE');

        $backwardsCompatibilityClassMapProvider = new BackwardsCompatibilityClassMapProvider($factsMock);
        $this->assertEquals(
            [
                'OxidEsales\\Eshop\\Application\\Model\\Article'   => 'oxarticle',
                'OxidEsales\\Eshop\\Core\\Contract\\IConfigurable' => 'oxiconfigurable'
            ],
            $backwardsCompatibilityClassMapProvider->getClassMap()
        );
    }

    /**
     * Get path to virtual output directory and copy the give structure $structure inside
     *
     * @todo Code is duplicated in other test classes. Either move to separate class or use testing library
     *
     * @param int   $permissions Directory permissions
     * @param array $structure   Optional directory structure to create inside output folder
     *
     * @return string
     */
    private function getVirtualOutputDirectory($permissions = 0777, $structure = null)
    {
        if (!is_array($structure)) {
            $structure = ['generated' => []];
        }

        vfsStream::create($structure, $this->getVirtualFileSystem());
        $directory = $this->getVirtualFilesystemRootPath() . 'generated';
        chmod($directory, $permissions);

        return $directory . DIRECTORY_SEPARATOR;
    }

    /**
     * @todo Code is duplicated in other test classes. Either move to separate class or use testing library
     *
     * @param string $edition The O3-Shop Edition, which the facts should give back.
     *
     * @return \OxidEsales\Facts\Facts
     */
    private function getFactsMock($edition = 'CE')
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\OxidEsales\Facts\Facts $mock */
        $mock = $this->getMockBuilder(\OxidEsales\Facts\Facts::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getEdition',
                    'getShopRootPath'
                ]
            )
            ->getMock();
        $mock->method('getEdition')->willReturn($edition);
        $mock->method('getShopRootPath')->willReturn($this->getVirtualFilesystemRootPath());

        return $mock;
    }

    /**
     * @todo Code is duplicated in other test classes. Either move to separate class or use testing library
     *
     * @return vfsStreamDirectory
     */
    private function getVirtualFileSystem()
    {
        if (is_null($this->vfsStreamDirectory)) {
            $this->vfsStreamDirectory = vfsStream::setup(self::ROOT_DIRECTORY);
        }

        return $this->vfsStreamDirectory;
    }

    /**
     * @todo Code is duplicated in other test classes. Either move to separate class or use testing library
     *
     * @param string $testCaseDirectory The directory within the pysical directory testData you want to
     *                                  load into the file system
     */
    private function copyTestDataIntoVirtualFileSystem($testCaseDirectory)
    {
        try {
            $pathToTestData = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testData' . DIRECTORY_SEPARATOR . $testCaseDirectory;
            $virtualFileSystem = $this->getVirtualFileSystem();

            vfsStream::copyFromFileSystem(
                $pathToTestData,
                $virtualFileSystem
            );
        } catch (\InvalidArgumentException $exception) {
            $this->fail($exception->getMessage());
        }
    }

    /**
     * Returns the root url. It should be treated as usual file path.
     *
     * @todo Code is duplicated in other test classes. Either move to separate class or use testing library
     *
     * @return string
     */
    private function getVirtualFilesystemRootPath()
    {
        return vfsStream::url(self::ROOT_DIRECTORY) . DIRECTORY_SEPARATOR;
    }
}