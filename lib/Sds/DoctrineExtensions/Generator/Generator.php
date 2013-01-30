<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Generator;

/**
 * Generate file from mapping information.
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class Generator implements GeneratorInterface
{

    const event = 'generatorGenerate';

    public function getSubscribedEvents(){
        return [
            self::event,
        ];
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Generator\GenerateEventArgs $eventArgs
     */
    public function generatorGenerate(GenerateEventArgs $eventArgs)
    {
        $metadata = $eventArgs->getMetadata();

        if (! $metadata->isMappedSuperclass &&
            ! $metadata->reflClass->isAbstract()
        ) {
            if (isset($metadata->generator)){
                $eventManager = $eventArgs->getEventManager();
                foreach ($metadata->generator as $config){
                    if ($eventManager->hasListeners($config['class']::event)) {
                        $eventManager->dispatchEvent(
                            $config['class']::event,
                            new GenerateEventArgs(
                                $metadata,
                                $eventArgs->getDocumentManager(),
                                $eventManager,
                                $eventArgs->getResults(),
                                $config['options']
                           )
                        );
                    }
                }
            }
        }

        $eventArgs->getResults()[] = new GeneratorResult(['message' => 'Generate all complete']);
    }
}
