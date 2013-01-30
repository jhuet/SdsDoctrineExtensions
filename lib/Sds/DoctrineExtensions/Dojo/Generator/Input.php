<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Dojo\Generator;

use Sds\DoctrineExtensions\Generator\GenerateEventArgs;
use Zend\Json\Expr;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class Input extends AbstractDojoGenerator
{

    const event = 'generatorDojoInput';

    public function getSubscribedEvents(){
        return [
            self::event,
        ];
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Generator\GenerateEventArgs $eventArgs
     */
    public function generatorDojoInput(GenerateEventArgs $eventArgs)
    {

        $metadata = $eventArgs->getMetadata();
        $eventManager = $eventArgs->getEventManager();
        $field = $eventArgs->getOptions()['property'];
        $results = $eventArgs->getResults();
        $options = $eventArgs->getOptions();

        $path = $this->getPath($metadata->name);
        if (! $path){
            return;
        }

        $path .= '/' . ucfirst($field) . '/Input.js';
        foreach ($results as $result){
            if ($result->getFileGenerated() == $path){
                //File has already been generated
                return;
            }
        }

        //generate any required validators
        $validatorOptions = ['property' => $field];
        foreach ($metadata->generator as $config){
            if (
                $config['class'] == 'Sds\DoctrineExtensions\Dojo\Generator\Validator' &&
                $config['options']['property'] == $field
            ){
                $validatorOptions = $config['options'];
                break;
            }
        }

        $eventManager->dispatchEvent(
            Validator::event,
            new GenerateEventArgs(
                $metadata,
                $eventArgs->getDocumentManager(),
                $eventManager,
                $results,
                $validatorOptions
            )
        );


        $templateArgs = [];

        $midBase = str_replace('\\', '/', $metadata->name)  . '/' . ucfirst($field);
        $mid = $midBase . '/Input';
        $validatorMid = $midBase . '/Validator';
        $templateArgs['mid'] = $mid;

        $hasValidator = false;
        foreach ($results as $result){
            if ($result->getMid() == $validatorMid){
                $hasValidator = true;
                break;
            }
        }

        $params = [];

        if ($hasValidator){
            switch ($metadata->fieldMappings[$field]['type']){
                case 'int':
                    $defaultMids = $this->defaultMixins['input']['intWithValidator'];
                    break;
                case 'float':
                    $defaultMids = $this->defaultMixins['input']['floatWithValidator'];
                    break;
                case 'custom_id':
                    $params['type'] = "'hidden'";
                case 'string':
                default:
                    $defaultMids = $this->defaultMixins['input']['stringWithValidator'];
                    break;
            }
            if (isset($options->mixins)){
                $templateArgs['dependencyMids'] = $options->mixins;
            } else {
                $templateArgs['dependencyMids'] = $defaultMids;
            }
            $templateArgs['dependencies'] = $this->namesFromMids($templateArgs['dependencyMids']);
            $templateArgs['mixins'] = $this->namesFromMids($templateArgs['dependencyMids']);

            $templateArgs['dependencyMids'][] = $validatorMid;
            $templateArgs['dependencies'][] = ucfirst($field) . 'Validator';
            $params['validator'] = new Expr('new ' . ucfirst($field) . 'Validator');
        } else {
            switch ($metadata->fieldMappings[$field]['type']){
                case 'int':
                    $defaultMids = $this->defaultMixins['input']['int'];
                    break;
                case 'float':
                    $defaultMids = $this->defaultMixins['input']['float'];
                    break;
                case 'custom_id':
                    $params['type'] = 'hidden';
                case 'string':
                default:
                    $defaultMids = $this->defaultMixins['input']['string'];
                    break;
            }

            if (isset($options->mixins)){
                $templateArgs['dependencyMids'] = $options->mixins;
            } else {
                $templateArgs['dependencyMids'] = $defaultMids;
            }

            $templateArgs['dependencies'] = $this->namesFromMids($templateArgs['dependencyMids']);
            $templateArgs['mixins'] = $this->namesFromMids($templateArgs['dependencyMids']);
        }

        $params['name'] = $field;

        //Camel case splitting regex
        $regex = '/# Match position between camelCase "words".
            (?<=[a-z]) # Position is after a lowercase,
            (?=[A-Z]) # and before an uppercase letter.
            /x';

        $params['label'] = ucfirst(implode(' ', preg_split($regex, $field))) . ":";

        if (isset($options->params)){
            foreach ($options->params as $key => $value){
                $params[$key] = $value;
            }
        }

        $templateArgs['dependencyMids'] = ',' . $this->indent($this->implodeMids($templateArgs['dependencyMids']));
        $templateArgs['dependencies'] = ',' . $this->indent($this->implodeNames($templateArgs['dependencies']));
        $templateArgs['mixins'] = $this->indent($this->implodeNames($templateArgs['mixins']), 12) . $this->indent("\n", 8);
        $templateArgs['params'] = $this->implodeParams($params);
        $templateArgs['comment'] = $this->indent("// Will return an input for the $field field");

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
            'message' => "Input for $metadata->name::$field generated to $path"
        ]);

    }
}
