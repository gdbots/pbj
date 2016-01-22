<?php

namespace Gdbots\Pbjc\Type;

interface Type
{
    /**
     * @return Type
     */
    public static function create();

    /**
     * @return \Gdbots\Pbjc\Enum\TypeName
     */
    public function getTypeName();

    /**
     * Shortcut to returning the value of the TypeName
     *
     * @return string
     */
    public function getTypeValue();

    /**
     * @return mixed
     */
    public function getDefault();

    /**
     * @return bool
     */
    public function isBoolean();

    /**
     * @return bool
     */
    public function isBinary();

    /**
     * @return bool
     */
    public function isNumeric();

    /**
     * @return bool
     */
    public function isString();

    /**
     * @return bool
     */
    public function isMessage();

    /**
     * Returns the minimum value supported by an integer type.
     *
     * @return int
     */
    public function getMin();

    /**
     * Returns the maximum value supported by an integer type.
     *
     * @return int
     */
    public function getMax();

    /**
     * Returns the maximum number of bytes supported by the string or binary type.
     *
     * @return int
     */
    public function getMaxBytes();

    /**
     * @return bool
     */
    public function allowedInSet();
}
