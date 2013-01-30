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
class Form extends AbstractDojoGenerator
{

    const event = 'generatorDojoForm';

    public function getSubscribedEvents(){
        return [
            self::event,
        ];
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Generator\GenerateEventArgs $eventArgs
     */
    public function generatorDojoForm(GenerateEventArgs $eventArgs)
    {

        $metadata = $eventArgs->getMetadata();
        $eventManager = $eventArgs->getEventManager();
        $results = $eventArgs->getResults();
        $options = $eventArgs->getOptions();

        $path = $this->getPath($metadata->name);
        if (! $path){
            return;
        }

        $path .= '/Form.js';
        foreach ($results as $result){
            if ($result->getFileGenerated() == $path){
                //File has already been generated
                return;
            }
        }

        //generate multifield validator if required
        $validatorOptions = [];
        foreach ($metadata->generator as $config){
            if (
                $config['class'] == 'Sds\DoctrineExtensions\Dojo\Generator\MultiFieldValidator' &&
                ! isset($config['options'])
            ){
                $validatorOptions = $config['options'];
                break;
            }
        }

        $eventManager->dispatchEvent(
            MultiFieldValidator::event,
            new GenerateEventArgs(
                $metadata,
                $eventArgs->getDocumentManager(),
                $eventManager,
                $results,
                $validatorOptions
            )
        );

        //generate inputs
        foreach(Serializer::fieldListForUnserialize($metadata) as $field){

            //generate any required inputs
            $inputOptions = ['property' => $field];
            foreach ($metadata->generator as $config){
                if (
                    $config['class'] == 'Sds\DoctrineExtensions\Dojo\Generator\Input' &&
                    $config['options']['property'] == $field
                ){
                    $inputOptions = $config['options'];
                    break;
                }
            }

            $eventManager->dispatchEvent(
                Input::event,
                new GenerateEventArgs(
                    $metadata,
                    $eventArgs->getDocumentManager(),
                    $eventManager,
                    $results,
                    $inputOptions
                )
            );
        }

        $templateArgs = [];

        $midBase = str_replace('\\', '/', $metadata->name);
        $mid = $midBase . '/Form';
        $multiFieldValidatorMid = $midBase . '/MultiFieldValidator';
        $templateArgs['mid'] = $mid;

        foreach ($results as $result){
            if ($result->getMid() == $multiFieldValidatorMid){
                $hasMultiFieldValidator = true;
                break;
            }
        }

        $params = [];

        if ($hasMultiFieldValidator){
            $defaultMids = $this->defaultMixins['form']['withValidator'];
        } else {
            $defaultMids = $this->defaultMixins['form']['simple'];
        }
        if (isset($options->mixins)){
            $templateArgs['dependencyMids'] = $options->mixins;
        } else {
            $templateArgs['dependencyMids'] = $defaultMids;
        }
        $templateArgs['mixins'] = $this->namesFromMids($templateArgs['dependencyMids']);
        $templateArgs['dependencies'] = $this->namesFromMids($templateArgs['dependencyMids']);
        if ($hasMultiFieldValidator){
            $templateArgs['dependencyMids'][] = $multiFieldValidatorMid;
            $templateArgs['dependencies'][] = 'MultiFieldValidator';
            $params['validator'] = new Expr('new MultiFieldValidator');
        }
        $params['inputs'] = [];
        foreach(Serializer::fieldListForUnserialize($metadata) as $field){
            $templateArgs['dependencyMids'][] = $midBase . '/' . ucfirst($field) . '/Input';
            $templateArgs['dependencies'][] = ucfirst($field) . 'Input';
            $params['inputs'][] = new Expr('new ' . ucfirst($field) . 'Input');
        }

        $templateArgs['dependencyMids'] = ',' . $this->indent($this->implodeMids($templateArgs['dependencyMids']));
        $templateArgs['dependencies'] = ',' . $this->indent($this->implodeNames($templateArgs['dependencies']));
        $templateArgs['mixins'] = $this->indent($this->implodeNames($templateArgs['mixins']), 12) . $this->indent("\n", 8);
        $templateArgs['params'] = $this->implodeParams($params);
        $templateArgs['comment'] = $this->indent("// Will return a form for $metadata->name");

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
            'message' => "Form for $metadata->name generated to $path"
        ]);

    }
}
