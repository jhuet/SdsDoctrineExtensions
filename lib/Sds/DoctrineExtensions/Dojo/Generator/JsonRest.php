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
class JsonRest extends AbstractDojoGenerator
{

    const event = 'generatorDojoJsonRest';

    public function getSubscribedEvents(){
        return [
            self::event,
        ];
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Generator\GenerateEventArgs $eventArgs
     */
    public function generatorDojoJsonRest(GenerateEventArgs $eventArgs)
    {

        $metadata = $eventArgs->getMetadata();
        $eventManager = $eventArgs->getEventManager();
        $results = $eventArgs->getResults();
        $options = $eventArgs->getOptions();

        $path = $this->getPath($metadata->name);
        if (! $path){
            return;
        }

        $path .= '/JsonRest.js';
        foreach ($results as $result){
            if ($result->getFileGenerated() == $path){
                //File has already been generated
                return;
            }
        }

        //generate model if required
        $modelOptions = [];
        foreach ($metadata->generator as $config){
            if (
                $config['class'] == 'Sds\DoctrineExtensions\Dojo\Generator\Model' &&
                ! isset($config['options'])
            ){
                $modelOptions = $config['options'];
                break;
            }
        }

        $eventManager->dispatchEvent(
            Model::event,
            new GenerateEventArgs(
                $metadata,
                $eventArgs->getDocumentManager(),
                $eventManager,
                $results,
                $modelOptions
            )
        );

        $templateArgs = [];

        $midBase = str_replace('\\', '/', $metadata->name);
        $mid = $midBase . '/JsonRest';
        $templateArgs['mid'] = $mid;

        $params = [];

        if (isset($options->mixins)){
            $templateArgs['dependencyMids'] = $options->mixins;
        } else {
            $templateArgs['dependencyMids'] = $this->defaultMixins['store']['jsonRest'];
        }
        $templateArgs['mixins'] = $this->namesFromMids($templateArgs['dependencyMids']);
        $templateArgs['dependencies'] = $this->namesFromMids($templateArgs['dependencyMids']);

        $pieces = explode('/', $midBase);
        $model = $pieces[count($pieces) - 1];

        $templateArgs['dependencyMids'][] = $midBase;
        $templateArgs['dependencies'][] = $model;

        $params['name'] = $metadata->collection;
        $params['idProperty'] = $metadata->identifier;

        if (isset($metadata->rest)){
            $params['target'] = $metadata->rest['basePath'] . $metadata->rest['endPoint'];
        } else {
            $params['target'] = $metadata->collection;
        }
        $params['model'] = new Expr($model);

        $templateArgs['dependencyMids'] = ',' . $this->indent($this->implodeMids($templateArgs['dependencyMids']));
        $templateArgs['dependencies'] = ',' . $this->indent($this->implodeNames($templateArgs['dependencies']));
        $templateArgs['mixins'] = $this->indent($this->implodeNames($templateArgs['mixins']), 12) . $this->indent("\n", 8);
        $templateArgs['params'] = $this->implodeParams($params);
        $templateArgs['comment'] = $this->indent("// Will return create a dojo JsonRest store for $metadata->name");

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
            'message' => "JsonStore for $metadata->name generated to $path"
        ]);

    }
}
