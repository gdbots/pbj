<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Validator\Exception\ConstraintDefinitionException;
use Gdbots\Pbjc\Validator\Exception\InvalidOptionsException;
use Gdbots\Pbjc\Validator\Exception\MissingOptionsException;

/**
 * Contains the properties of a constraint definition.
 *
 * A constraint can be defined on a class, an option or a getter method.
 * The Constraint class encapsulates all the configuration required for
 * validating this class, option or getter result successfully.
 *
 * Constraint instances are immutable and serializable.
 */
abstract class Constraint
{
    /**
     * Initializes the constraint with options.
     *
     * You should pass an associative array. The keys should be the names of
     * existing properties in this class. The values should be the value for these
     * properties.
     *
     * Alternatively you can override the method getDefaultOption() to return the
     * name of an existing property. If no associative array is passed, this
     * property is set instead.
     *
     * You can force that certain options are set by overriding
     * getRequiredOptions() to return the names of these options. If any
     * option is not set here, an exception is thrown.
     *
     * @param mixed $options The options (as associative array)
     *                       or the value for the default
     *                       option (any other type)
     *
     * @throws InvalidOptionsException       When you pass the names of non-existing
     *                                       options
     * @throws MissingOptionsException       When you don't pass any of the options
     *                                       returned by getRequiredOptions()
     * @throws ConstraintDefinitionException When you don't pass an associative
     *                                       array, but getDefaultOption() returns
     *                                       null
     */
    public function __construct($options = null)
    {
        $invalidOptions = array();
        $missingOptions = array_flip((array) $this->getRequiredOptions());
        $knownOptions = get_object_vars($this);

        if (is_array($options) && count($options) >= 1 && isset($options['value']) && !property_exists($this, 'value')) {
            $options[$this->getDefaultOption()] = $options['value'];
            unset($options['value']);
        }

        if (is_array($options) && count($options) > 0 && is_string(key($options))) {
            foreach ($options as $option => $value) {
                if (array_key_exists($option, $knownOptions)) {
                    $this->$option = $value;
                    unset($missingOptions[$option]);
                } else {
                    $invalidOptions[] = $option;
                }
            }
        } elseif (null !== $options && !(is_array($options) && count($options) === 0)) {
            $option = $this->getDefaultOption();

            if (null === $option) {
                throw new ConstraintDefinitionException(
                    sprintf('No default option is configured for constraint %s', get_class($this))
                );
            }

            if (array_key_exists($option, $knownOptions)) {
                $this->$option = $options;
                unset($missingOptions[$option]);
            } else {
                $invalidOptions[] = $option;
            }
        }

        if (count($invalidOptions) > 0) {
            throw new InvalidOptionsException(
                sprintf('The options "%s" do not exist in constraint %s', implode('", "', $invalidOptions), get_class($this)),
                $invalidOptions
            );
        }

        if (count($missingOptions) > 0) {
            throw new MissingOptionsException(
                sprintf('The options "%s" must be set for constraint %s', implode('", "', array_keys($missingOptions)), get_class($this)),
                array_keys($missingOptions)
            );
        }
    }

    /**
     * Returns the name of the default option.
     *
     * Override this method to define a default option.
     *
     * @return string
     *
     * @see __construct()
     */
    public function getDefaultOption()
    {
    }

    /**
     * Returns the name of the required options.
     *
     * Override this method if you want to define required options.
     *
     * @return array
     *
     * @see __construct()
     */
    public function getRequiredOptions()
    {
        return array();
    }

    /**
     * Returns the name of the class that validates this constraint.
     *
     * By default, this is the fully qualified name of the constraint class
     * suffixed with "Validator". You can override this method to change that
     * behaviour.
     *
     * @return string
     */
    public function validatedBy()
    {
        return sprintf('%sValidator', get_class($this));
    }
}
