<?php

namespace FormRelay\Mail\Model\Form;

use FormRelay\Core\Model\Form\FieldInterface;

class EmailField implements FieldInterface
{
    protected $address;
    protected $name;

    public function __construct(string $address, string $name = '')
    {
        $this->address = trim($address);
        $this->name = trim($name);
    }

    public function __toString(): string
    {
        if ($this->name) {
            return $this->name . ' <' . $this->address . '>';
        }
        return $this->address;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
