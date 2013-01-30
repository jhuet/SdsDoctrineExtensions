<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Dojo\Generator;

use Sds\DoctrineExtensions\Generator\GeneratorInterface;
use Zend\Json\Expr;
use Zend\Json\Json;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
abstract class AbstractDojoGenerator implements GeneratorInterface
{

    protected $destPaths;

    protected $defaultMixins;

    public function __construct($destPaths, $defaultMixins){
        $this->destPaths = $destPaths;
        $this->defaultMixins = $defaultMixins;
    }

    protected function getPath($name){
        foreach ($this->destPaths as $dest){
            if ($dest['filter'] == '' || strpos($name, $dest['filter']) !== false) {
                return $dest['path'] . '/' . str_replace('\\', '/', $name);;
                break;
            }
        }
    }

    protected function populateTemplate($template, array $args) {

        $populated = $template;
        foreach ($args as $key => $value) {
            $populated = str_replace('<'.$key.'>', $value, $populated);
        }
        return $populated;
    }

    protected function indent($string, $indent = 4){
        $indent = str_repeat(' ', $indent);
        return $indent . str_replace("\n", "\n" . $indent, $string);
    }

    protected function implodeMids(array $mids){
        return "\n'" . implode("',\n'", $mids) . "'";
    }

    protected function implodeParams(array $params){
        $tmp = [];
        foreach ($params as $key => $value){
            switch (true){
                case is_array($value):
                    $param = "$key: " . Json::prettyPrint(Json::encode(
                        $value,
                        false,
                        ['enableJsonExprFinder' => true]
                    ));
                    break;
                case ($value instanceof Expr):
                    $param = "$key: " . $value;
                    break;
                default:
                    $param = "$key: '$value'";
            }
            $tmp[] = $param;
        }
        return $this->indent(implode(",\n\n", $tmp), 12);
    }

    protected function implodeNames(array $names){
        return "\n" . implode(",\n", $names);
    }

    protected function namesFromMids(array $mids){
        $result = [];
        foreach($mids as $mid){
            $result[] = $this->nameFromMid($mid);
        }
        return $result;
    }

    protected function nameFromMid($mid){
        $pieces = explode('/', $mid);
        return $pieces[count($pieces) - 1];
    }

    protected function midFromClass($class){
        return str_replace('\\', '/', $class);
    }
}
