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
class Validator extends AbstractDojoGenerator
{

    const event = 'generatorDojoValidator';

    public function getSubscribedEvents(){
        return [
            self::event,
        ];
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Generator\GenerateEventArgs $eventArgs
     */
    public function generatorDojoValidator(GenerateEventArgs $eventArgs)
    {

        $metadata = $eventArgs->getMetadata();
        $field = $eventArgs->getOptions()['property'];
        $results = $eventArgs->getResults();

        $path = $this->getPath($metadata->name);
        if (! $path){
            return;
        }

        $path .= '/' . ucfirst($field) . '/Validator.js';
        foreach ($results as $result){
            if ($result->getFileGenerated() == $path){
                //File has already been generated
                return;
            }
        }

        if ( ! isset($metadata->validator['fields'][$field])){
            return;
        }


        if (count($metadata->validator['fields'][$field]) > 1){
            $templateArgs = [
                'dependencyMids' => $this->defaultMixins['validator']['group'],
                'dependencies' => $this->namesFromMids($this->defaultMixins['validator']['group']),
                'mixins' => $this->namesFromMids($this->defaultMixins['validator']['group']),
                'params' => ['field' => "$field"]
            ];

            foreach($metadata->validator['fields'][$field] as $validator){
                $validatorMid = $this->midFromClass($validator['class']);
                $validatorName = $this->nameFromMid($validatorMid);

                $templateArgs['dependencyMids'][] = $validatorMid;
                $templateArgs['dependencies'][] = $validatorName;

                if(isset($validator['options']) && count($validator['options']) > 0 ){
                    $params = json_encode($validator['options']);
                    $templateArgs['params']['validators'][] = new Expr("new $validatorName($params)");
                } else {
                    $templateArgs['params']['validators'][] = new Expr("new $validatorName");
                }
            }

        } else {
            $validator = $metadata->validator['fields'][$field][0];

            $mid = $this->midFromClass($validator['class']);
            $templateArgs = [
                'dependencyMids' => [$mid],
                'dependencies' => [$this->nameFromMid($mid)],
                'mixins' => [$this->nameFromMid($mid)],
            ];

            if (isset($validator['options'])){
                $templateArgs['params'] = array_merge(['field' => "$field"], $validator['options']);
            } else {
                $templateArgs['params'] = ['field' => "$field"];
            }
        }

        $mid = str_replace('\\', '/', $metadata->name)  . '/' . ucfirst($field) . '/Validator';
        $templateArgs['mid'] = $mid;
        $templateArgs['dependencyMids'] = ',' . $this->indent($this->implodeMids($templateArgs['dependencyMids']));
        $templateArgs['dependencies'] = ',' . $this->indent($this->implodeNames($templateArgs['dependencies']));
        $templateArgs['mixins'] = $this->indent($this->implodeNames($templateArgs['mixins']), 12) . $this->indent("\n", 8);
        $templateArgs['params'] = $this->implodeParams($templateArgs['params']);
        $templateArgs['comment'] = $this->indent("// Will return a validator that can be used to check\n// the $field field");

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
            'message' => "Validator for $metadata->name::$field generated to $path"
        ]);
    }
}
