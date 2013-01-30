<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Dojo\Generator;

use Sds\DoctrineExtensions\Generator\GenerateEventArgs;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class MultiFieldValidator extends AbstractDojoGenerator
{

    const event = 'generatorDojoMultiFieldValidator';

    public function getSubscribedEvents(){
        return [
            self::event,
        ];
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Generator\GenerateEventArgs $eventArgs
     */
    public function generatorDojoMultiFieldValidator(GenerateEventArgs $eventArgs)
    {

        $metadata = $eventArgs->getMetadata();
        $results = $eventArgs->getResults();

        $path = $this->getPath($metadata->name);
        if (! $path){
            return;
        }

        $path .= '/MultiFieldValidator.js';
        foreach ($results as $result){
            if ($result->getFileGenerated() == $path){
                //File has already been generated
                return;
            }
        }

        if ( ! isset($metadata->validator['document'])){
            return;
        }


        if (count($metadata->validator['document']) > 1){
            $templateArgs = [
                'dependencyMids' => $this->defaultMixins['validator']['validatorGroup'],
                'dependencies' => $this->namesFromMids($this->defaultMixins['validator']['validatorGroup']),
                'mixins' => $this->namesFromMids($this->defaultMixins['validator']['validatorGroup'])
            ];

            foreach($metadata->validator['document'] as $validator){
                $validatorMid = $this->midFromClass($validator['class']);
                $validatorName = $this->nameFromMid($validatorMid);

                $templateArgs['dependencyMids'][] = $validatorMid;
                $templateArgs['dependencies'][] = $validatorName;

                if(isset($validator['options']) && count($validator['options']) > 0 ){
                    $params = json_encode($validator['options'], JSON_PRETTY_PRINT);
                    $templateArgs['params']['validators'][] = "new $validatorName($params)";
                } else {
                    $templateArgs['params']['validators'][] = "new $validatorName";
                }
            }

        } else {
            $validator = $metadata->validator['document'][0];

            $mid = $this->midFromClass($validator['class']);
            $templateArgs = [
                'dependencyMids' => [$mid],
                'dependencies' => [$this->nameFromMid($mid)],
                'mixins' => [$this->nameFromMid($mid)],
            ];

            if (isset($validator['options'])){
                $templateArgs['params'] = array_merge(['field' => "'$field'"], $validator['options']);
            } else {
                $templateArgs['params'] = [];
            }
        }

        $mid = str_replace('\\', '/', $metadata->name)  . '/MultiFieldValidator';
        $templateArgs['mid'] = $mid;
        $templateArgs['dependencyMids'] = ',' . $this->indent($this->implodeMids($templateArgs['dependencyMids']));
        $templateArgs['dependencies'] = ',' . $this->indent($this->implodeNames($templateArgs['dependencies']));
        $templateArgs['mixins'] = $this->indent($this->implodeNames($templateArgs['mixins']), 12) . $this->indent("\n", 8);
        $templateArgs['params'] = $this->implodeParams($templateArgs['params']);
        $templateArgs['comment'] = $this->indent("// Will return a multi field validator");

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
            'message' => "MultiFieldValidator for $metadata->name generated to $path"
        ]);
    }
}
