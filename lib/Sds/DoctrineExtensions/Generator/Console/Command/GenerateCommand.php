<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Generator\Console\Command;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Sds\DoctrineExtensions\Generator\GenerateEventArgs;
use Sds\DoctrineExtensions\Generator\Generator;
use Symfony\Component\Console;

/**
 * Command to generate files from Doctrine document metadata.
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class GenerateCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('sds:generate:all')
        ->setDescription('Generate files from Doctrine document metadata.')
        ->setHelp(<<<EOT
Generate files from Doctrine document metadata.
EOT
        );
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $documentManager = $this->getHelper('documentManager')->getDocumentManager();

        $metadataFactory = new ClassMetadataFactory();
        $metadataFactory->setConfiguration($documentManager->getConfiguration());
        $metadataFactory->setDocumentManager($documentManager);

        $metadatas = $metadataFactory->getAllMetadata();

        $eventManager = $documentManager->getEventManager();

        if (count($metadatas)) {

            foreach ($metadatas as $metadata) {
                $output->write(
                    sprintf('Processing document "<info>%s</info>"', $metadata->name) . PHP_EOL
                );

                $results = new ArrayObject();
                $eventManager->dispatchEvent(
                    Generator::event,
                    new GenerateEventArgs(
                        $metadata,
                        $documentManager,
                        $eventManager,
                        $results
                   )
                );

                $messages = [];
                foreach ($results as $result){
                    $messages[] = $result->getMessage();
                }
                $output->write(implode(PHP_EOL, $messages));
            }
        } else {
            $output->write('No Metadata to process.' . PHP_EOL);
        }
    }
}
