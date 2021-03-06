<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ThrowExceptionDefinition.
 */
class ThrowExceptionDefinition extends ProcessorDefinition
{
    const REF = 'ref';
    const MESSAGE = 'message';
    const PREFIX = 'throw_exception';

    /**
     * {@inheritdoc}
     */
    public function buildProcessor($configNode, $id)
    {
        $def = parent::buildProcessor($configNode, $id);

        // Description
        $description = (string) $configNode->{'ref'};
        $def->addMethodCall('setDescription', [$description]);

        // Ref
        $ref = (string) $configNode->attributes()->{'ref'};
        $exceptionClass = $this->builder->getParameter($ref.'.class');

        if (!$exceptionClass) {
            throw new \RuntimeException("The exception reference $ref was not found");
        }

        if (
            empty($exceptionClass) ||
            !class_exists($exceptionClass) ||
            !is_a($exceptionClass, 'Exception', true)
        ) {
            throw new \RuntimeException("$exceptionClass is not a valid exception class");
        }

        $def->addMethodCall('setExceptionClass', [$exceptionClass]);

        $message = (string) $configNode->attributes()->{self::MESSAGE};
        $def->addMethodCall('setExceptionMessage', [$message]);

        return $def;
    }
}
