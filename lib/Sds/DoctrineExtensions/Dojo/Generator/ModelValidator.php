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
class ModelValidator extends AbstractDojoGenerator
{

    const event = 'generatorDojoModelValidator';

    public function getSubscribedEvents(){
        return [
            self::event,
        ];
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Generator\GenerateEventArgs $eventArgs
     */
    public function generatorDojoModelValidator(GenerateEventArgs $eventArgs)
    {

        $metadata = $eventArgs->getMetadata();
        $eventManager = $eventArgs->getEventManager();
        $results = $eventArgs->getResults();
        $options = $eventArgs->getOptions();

        $path = $this->getPath($metadata->name);
        if (! $path){
            return;
        }

        $path .= '/ModelValidator.js';
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

        //generate any single field validators
        foreach(Serializer::fieldListForUnserialize($metadata) as $field){

            //generate any required validators
            $inputOptions = ['property' => $field];
            foreach ($metadata->generator as $config){
                if (
                    $config['class'] == 'Sds\DoctrineExtensions\Dojo\Generator\Validator' &&
                    $config['options']['property'] == $field
                ){
                    $inputOptions = $config['options'];
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
                    $inputOptions
                )
            );
        }

        $templateArgs = [];

        $midBase = str_replace('\\', '/', $metadata->name);
        $mid = $midBase . '/ModelValidator';
        $multiFieldValidatorMid = $midBase . '/MultiFieldValidator';
        $templateArgs['mid'] = $mid;

        foreach ($results as $result){
            if ($result->getMid() == $multiFieldValidatorMid){
                $hasMultiFieldValidator = true;
                break;
            }
        }

        $params = ['validators' => []];

        if (isset($options->mixins)){
            $templateArgs['dependencyMids'] = $options->mixins;
        } else {
            $templateArgs['dependencyMids'] = $this->defaultMixins['validator']['model'];
        }

        $templateArgs['mixins'] = $this->namesFromMids($templateArgs['dependencyMids']);
        $templateArgs['dependencies'] = $this->namesFromMids($templateArgs['dependencyMids']);
        if ($hasMultiFieldValidator){
            $templateArgs['dependencyMids'][] = $multiFieldValidatorMid;
            $templateArgs['dependencies'][] = 'MultiFieldValidator';
            $params['validators'][] = new Expr('new MultiFieldValidator');
        }
        foreach(Serializer::fieldListForUnserialize($metadata) as $field){
            foreach ($results as $result){
                if ($result->getMid() == $midBase . '/' . ucfirst($field) . '/Validator'){
                    $templateArgs['dependencyMids'][] = $midBase . '/' . ucfirst($field) . '/Validator';
                    $templateArgs['dependencies'][] = ucfirst($field) . 'Validator';
                    $params['validators'][] = new Expr('new ' . ucfirst($field) . 'Validator');
                    break;
                }
            }
        }

        $templateArgs['dependencyMids'] = ',' . $this->indent($this->implodeMids($templateArgs['dependencyMids']));
        $templateArgs['dependencies'] = ',' . $this->indent($this->implodeNames($templateArgs['dependencies']));
        $templateArgs['mixins'] = $this->indent($this->implodeNames($templateArgs['mixins']), 12) . $this->indent("\n", 8);
        $templateArgs['params'] = $this->implodeParams($params);
        $templateArgs['comment'] = $this->indent("// Will return a validator to validate a complete model for $metadata->name");

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
            'message' => "ModelValidator for $metadata->name generated to $path"
        ]);

    }
}
