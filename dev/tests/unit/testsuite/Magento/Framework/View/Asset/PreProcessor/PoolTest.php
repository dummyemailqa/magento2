<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\View\Asset\PreProcessor;

class PoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\View\Asset\PreProcessor\Pool
     */
    protected $processorPool;

    /**
     * @var \Magento\Framework\View\Asset\PreProcessorFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processorFactory;

    /**
     * @var \Magento\Framework\View\Asset\PreProcessor\Chain|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processorChain;

    protected function setUp()
    {
        $this->processorChain = $this->getMockBuilder('Magento\Framework\View\Asset\PreProcessor\Chain')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->processorFactory = $this->getMockBuilder('Magento\Framework\View\Asset\PreProcessorFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->processorPool = new Pool(
            $this->processorFactory,
            [
                'less' => [
                    'css' =>
                        [
                            'Magento\Framework\Css\PreProcessor\Less',
                            'Magento\Framework\View\Asset\PreProcessor\ModuleNotation'
                        ],
                    'less' =>
                        [
                            'Magento\Framework\Less\PreProcessor\Instruction\MagentoImport',
                            'Magento\Framework\Less\PreProcessor\Instruction\Import',
                        ],
                    ],
                'css' => [
                    'css' => ['Magento\Framework\View\Asset\PreProcessor\ModuleNotation']
                ],
            ]
        );
    }

    /**
     * @param string $sourceContentType
     * @param string $targetContentType
     * @param array $expectedResult
     *
     * @dataProvider getPreProcessorsDataProvider
     */
    public function testProcess($sourceContentType, $targetContentType, array $expectedResult)
    {

        $this->processorChain->expects($this->any())
            ->method('getOrigContentType')
            ->willReturn($sourceContentType);
        $this->processorChain->expects($this->any())
            ->method('getTargetContentType')
            ->willReturn($targetContentType);
        $processorMaps = [];
        foreach($expectedResult as $processor) {
            $processorMock = $this->getMock($processor, ['process'], [], '', false);
            $processorMock->expects($this->any())
                ->method('process')
                ->with($this->processorChain);
            $processorMaps[] = [$processor, [], $processorMock];
        }
        $this->processorFactory->method('create')->willReturnMap($processorMaps);

        $this->processorPool->process($this->processorChain);
    }

    public function getPreProcessorsDataProvider()
    {
        return [
            'css => css' => [
                'css', 'css',
                [
                    'Magento\Framework\View\Asset\PreProcessor\ModuleNotation'
                ],
            ],
            //all undefined types will be processed by Passthrough preprocessor
            'css => less' => [
                'css', 'less',
                ['Magento\Framework\View\Asset\PreProcessor\Passthrough'],
            ],
            'less => css' => [
                'less', 'css',
                [
                    'Magento\Framework\Css\PreProcessor\Less',
                    'Magento\Framework\View\Asset\PreProcessor\ModuleNotation'
                ],
            ],
            'less => less' => [
                'less', 'less',
                [
                    'Magento\Framework\Less\PreProcessor\Instruction\MagentoImport',
                    'Magento\Framework\Less\PreProcessor\Instruction\Import',
                ],
            ],
            //all undefined types will be processed by Passthrough preprocessor
            'txt => log (undefined)' => [
                'txt', 'log',
                ['Magento\Framework\View\Asset\PreProcessor\Passthrough'],
            ],
        ];
    }
}
