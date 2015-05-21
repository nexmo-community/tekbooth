<?php
namespace TekBooth\Service\GoPro;

class Model
{
    /**
     * Not sure what this is, perhaps a hardware code?
     * @var string
     */
    protected $code;

    /**
     * The Model Number
     * @var string
     */
    protected $model;

    /**
     * The Human Friendly Name
     * @var string
     */
    protected $name;

    public function __construct($raw)
    {
        $parts = explode(chr(12), $raw);
        $this->code = $parts[0];
        $parts = explode(chr(20), $parts[1]);
        $this->model = $parts[0];
        $this->name = $parts[1];
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    function __toString()
    {
        return $this->getName();
    }
}