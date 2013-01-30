<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Dojo\Generator;

use Sds\DoctrineExtensions\Generator\GenerateEventArgs;
use Sds\DoctrineExtensions\Serializer\Serializer;
use Zend\Json\Expr;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class Model extends AbstractDojoGenerator
{

    const event = 'generatorDojoModel';

    public function getSubscribedEvents(){
        return [
            self::event,
        ];
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Generator\GenerateEventArgs $eventArgs
     */
    public function generatorDojoModel(GenerateEventArgs $eventArgs)
    {

        $metadata = $eventArgs->getMetadata();
        $eventManager = $eventArgs->getEventManager();
        $results = $eventArgs->getResults();
        $options = $eventArgs->getOptions();

        $path = $this->getPath($metadata->name) . '.js';
        if (! $path){
            return;
        }

        foreach ($results as $result){
            if ($result->getFileGenerated() == $path){
                //File has already been generated
                return;
            }
        }

        $templateArgs = [];

        $mid = str_replace('\\', '/', $metadata->name);
        $templateArgs['mid'] = $mid;

        $params = [];

        if (isset($options->mixins)){
            $templateArgs['dependencyMids'] = $options->mixins;
        } else {
            $templateArgs['dependencyMids'] = $this->defaultMixins['model'];
        }

        $templateArgs['mixins'] = $this->namesFromMids($templateArgs['dependencyMids']);
        $templateArgs['dependencies'] = $this->namesFromMids($templateArgs['dependencyMids']);

        $params['_fields'] = [];
        foreach(Serializer::fieldListForUnserialize($metadata) as $field){
            $params['_fields'][] = $field;
        }

        $params['_fields'][] = '_className';
        $params['_className'] = str_replace('\\', '\\\\', $metadata->name);

        $templateArgs['dependencyMids'] = ',' . $this->indent($this->implodeMids($templateArgs['dependencyMids']));
        $templateArgs['dependencies'] = ',' . $this->indent($this->implodeNames($templateArgs['dependencies']));
        $templateArgs['mixins'] = $this->indent($this->implodeNames($templateArgs['mixins']), 12) . $this->indent("\n", 8);
        $templateArgs['params'] = $this->implodeParams($params);
        $templateArgs['comment'] = $this->indent("// Will return a model for $metadata->name");

        $content = $this->populateTemplate(
            file_get_contents(__DIR__ . '/Template/Module.js.template'),
            $templateArgs
        );

        $dir = dirname($path);

        if ( ! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, $content);

        $results[] = new GeneratorResult([
            'fileGenerated' => $path,
            'mid' => $mid,
            'message' => "Model for $metadata->name generated to $path"
        ]);

    }
}
