<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions;

use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderCompilerPass;
use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\TransformerDefinition;
use Smartbox\Integration\CamelConfigBundle\Tests\BaseKernelTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class TransformerDefinitionTest.
 */
class TransformerDefinitionTest extends BaseKernelTestCase
{
    /**
     * @var TransformerDefinition
     */
    protected $processorDefinition;

    /**
     * @var FlowsBuilderCompilerPass|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $flowsBuilderCompilerPassMock;

    public function setUp()
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();

        $this->flowsBuilderCompilerPassMock = $this->getMockBuilder(FlowsBuilderCompilerPass::class)
            ->setMethods(['getBasicDefinition', 'registerService', 'buildItinerary'])
            ->getMock();

        $this->flowsBuilderCompilerPassMock->method('getBasicDefinition')->willReturn(new Definition());
        $this->flowsBuilderCompilerPassMock->method('buildItinerary')->willReturn(new Reference(1));

        $this->processorDefinition = new TransformerDefinition();
        $this->processorDefinition->setEvaluator($container->get('smartesb.util.evaluator'));
        $this->processorDefinition->setBuilder($this->flowsBuilderCompilerPassMock);
    }

    public function dataProviderForValidConfiguration()
    {
        return [
            [
                new \SimpleXMLElement("<transform><description>Some description of transformer processor</description><simple>msg.getBody().get('box').setDescription('test description')</simple></transform>"),
                [
                    [
                        'setDescription',
                        [
                            'Some description of transformer processor',
                        ],
                    ],
                    [
                        'setExpression',
                        [
                            "msg.getBody().get('box').setDescription('test description')",
                        ],
                    ],
                ],
            ],
            [
                new \SimpleXMLElement("<transform><description></description><simple>msg.getBody().get('box').setDescription('test description')</simple></transform>"),
                [
                    [
                        'setDescription',
                        [
                            '',
                        ],
                    ],
                    [
                        'setExpression',
                        [
                            "msg.getBody().get('box').setDescription('test description')",
                        ],
                    ],
                ],
            ],
            [
                new \SimpleXMLElement("<transform><simple>msg.getBody().get('box').setDescription('test description')</simple></transform>"),
                [
                    [
                        'setExpression',
                        [
                            "msg.getBody().get('box').setDescription('test description')",
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test the cases where the itinerary is not build, that's the description and single properties for a when clause and
     * the description in the otherwise case. The itinerary creation should be tested in the flowsBuilderCompilerPass class.
     *
     * @dataProvider dataProviderForValidConfiguration
     *
     * @param $config
     * @param array $expectedMethodCalls
     */
    public function testBuildProcessorForValidConfiguration($config, $expectedMethodCalls)
    {
        $transformerDefinition = $this->processorDefinition->buildProcessor($config, $this->flowsBuilderCompilerPassMock->determineProcessorId($config));

        $this->assertEquals($expectedMethodCalls, $transformerDefinition->getMethodCalls());
    }

    public function dataProviderForInvalidConfiguration()
    {
        return [
            [new \SimpleXMLElement('<transform></transform>')],
            [new \SimpleXMLElement('<transform><simple></simple></transform>')],
            [new \SimpleXMLElement('<transform><description>Some description of transformer processor</description></transform>')],
            [new \SimpleXMLElement('<transform><simple>incorrect expression</simple></transform>')],
            [new \SimpleXMLElement("<transform><simple>msg.getBody().get('box').setDescription('test description')abc</simple></transform>")],
            [new \SimpleXMLElement('<transform><wrong_node></wrong_node></transform>')],
            [new \SimpleXMLElement('<transform><description>Some description of transformer processor</description><wrong_node></wrong_node></transform>')],
            [new \SimpleXMLElement('<transform><wrong_node>this is content of unsupported node</wrong_node></transform>')],
        ];
    }

    /**
     * Tests exception when XML for transformer expression is invalid.
     *
     * @dataProvider dataProviderForInvalidConfiguration
     *
     * @param $config
     */
    public function testBuildProcessorForInvalidConfiguration($config)
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processorDefinition->buildProcessor($config, $this->flowsBuilderCompilerPassMock->determineProcessorId($config));
    }
}
