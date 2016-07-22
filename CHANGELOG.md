# CHANGELOG


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
