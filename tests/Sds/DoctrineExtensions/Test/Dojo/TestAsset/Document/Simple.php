<?php

namespace Sds\DoctrineExtensions\Test\Dojo\TestAsset\Document;

//Annotation imports
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Sds\DoctrineExtensions\Annotation\Annotations as Sds;

/**
 * @ODM\Document
 * @Sds\Serializer(@Sds\ClassName)
 * @Sds\Validator(class = "Sds/Test/ClassValidator1"),
 * @Sds\Validator(class = "Sds/Test/ClassValidator2", options = {"option1" = "a", "option2" = "b"})
 * @Sds\Generator({
 *     @Sds\Dojo\Model,
 *     @Sds\Dojo\Form,
 *     @Sds\Dojo\ModelValidator,
 *     @Sds\Dojo\JsonRest
 * })
 */
class Simple {

    /**
     * @ODM\Id(strategy="UUID")
     * @Sds\Serializer(@Sds\ClassName)
     */
    protected $id;

    /**
     * @ODM\String
     * @Sds\Validator\Required,
     * @Sds\Validator(class = "Sds/Test/NameValidator1"),
     * @Sds\Validator(class = "Sds/Test/NameValidator2", options = {"option1" = "b", "option2" = "b"})
     * @Sds\Generator(
     *     @Sds\Dojo\Input(
     *         params = {
     *             "label" = "NAME",
     *             "tooltip" = "The simple's name",
     *             "description" = "This is a longer description"
     *         }
     *     )
     * )
     */
    protected $name;

    /**
     * @ODM\String
     * @Sds\Validator(class = "Sds/Test/CountryValidator1")
     * @Sds\Generator(
     *     @Sds\Dojo\Input(
     *         mixins = {"Sds/Common/Form/ValidationTextarea"}
     *     )
     * )
     */
    protected $country;

    /**
     * @ODM\String
     */
    protected $camelCaseProperty;

    /**
     * @ODM\String
     * @Sds\Serializer(@Sds\Ignore)
     * @Sds\Validator\NotRequired
     */
    protected $ignoreProperty;

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = (string) $name;
    }

    public function getCountry() {
        return $this->country;
    }

    public function setCountry($country) {
        $this->country = $country;
    }

    public function getCamelCaseProperty() {
        return $this->camelCaseProperty;
    }

    public function setCamelCaseProperty($camelCaseProperty) {
        $this->camelCaseProperty = $camelCaseProperty;
    }

    public function getIgnoreProperty() {
        return $this->ignoreProperty;
    }

    public function setIgnoreProperty($ignoreProperty) {
        $this->ignoreProperty = $ignoreProperty;
    }
}
