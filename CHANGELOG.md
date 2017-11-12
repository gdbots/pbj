# CHANGELOG


## v0.3.1
* Fix bug with `FieldMustContainsAnyOfClasses` constraint that fails when `anyOf` returns null.
  Resolve by adding default empty array to `FieldDescriptor::anyOf`.


## v0.3.0
__BREAKING CHANGES__

* Compiler now requires php 7.1 or greater. 
* PHP namespaces are now handled by the compiler and not customized per message.
* The `pbjc.yml` allows for root vendor namespace customizations.
* Adds an es6 compiler (using the `js` language option).


## v0.2.6
* Use aliasing "use" statements everywhere mixins are use.
* Ignore adding trait classes to messages if schema doesn't include mixins and no insertion-points was define.


## v0.2.5
* Force aliasing "use" statements of mixins when generating message classes.


## v0.2.4
* Fixed inheritance validation with multi AnyOf classes.


## v0.2.3
* issue #40: Fixed inheritance validation as well as removing duplicate items from array's (like AnyOf).


## v0.2.2
* issue #38: Fixed identifier type json schema pattern to allow for "^[\w\/\.:-]+$".


## v0.2.1
* issue #36: Add "TrinaryType".  ref https://en.wikipedia.org/wiki/Three-valued_logic


## v0.2.0
* issue #35: BUG :: When a jsonschema for an enum is produced it must be a unique set.
* issue #33: Added support for dynamic-field type.


## v0.1.8
* issue #31: BUG :: Unable to change default on overridden field.


## v0.1.7
* issue #27: BUG :: Handle INF & NAN numeric values. Also removed min/max for decimals and floats fields.


## v0.1.6
* issue #25: BUG :: Ignore "overridable" on field inheritance, and fix json-schema fields order.


## v0.1.5
* issue #22: BUG :: Multi-valued fields don't produce proper json-schema.
* issue #21: BUG :: Using an int-enum with a default of 0 won't render.
* issue #20: BUG :: date-time field types render as numbers in json schema.


## v0.1.4
* issue #17: Generate all languages from pbjc.yml unless lang option is provided.
* issue #16: BUG :: Compiler produces default for enums when one isn't set.


## v0.1.3
* issue #14: BUG :: mixins using extends option won't compile.
* issue #13: Test case and example of map, set and list in json schema.
* issue #12: BUG :: any-of option with array produces incorrect php result.


## v0.1.2
* issue #9: Schema parse error when "category" is empty.
* issue #10: Fixed recursive schema requesting.


## v0.1.1
* issue #8: Remove DATED_SLUG format option.
* issue #7: Fixed invalid enum name when format has "-" bug.
* issue #6: Add `assertion` to field php options.
* issue #5: Fixed illegal enum name.


## v0.1.0
* Initial version.
