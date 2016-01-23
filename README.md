pbjc-php
=============

[![Build Status](https://api.travis-ci.org/gdbots/pbjc-php.svg)](https://travis-ci.org/gdbots/pbjc-php)
[![Code Climate](https://codeclimate.com/github/gdbots/pbjc-php/badges/gpa.svg)](https://codeclimate.com/github/gdbots/pbjc-php)

Compiler for converting pbj schemas into jsonschema, php, js, etc.

# Language Guide
This guide describes how to use the YAML language to structure your schema file syntax and how to generate data classes files.

### Defining A Schema
First let's look at a very simple example. Let's say you want to define a **mixin** schema, with slug and name fields. Here's the `.yml` file you use to define the schema.

```yaml
id: 'pbj:acme:demo:mixin:story:1-0-0'
mixin: true
fields:
  slug:
    type: string
    pattern: '/^[A-Za-z0-9_\-]+$/'
    required: true
  title:
    type: text
    required: true
php_options:
  namespace: 'Acme\Demo\Mixin'
```

Each schema required a few basic elements: id and fields.
The id is a unique identifier follow a basic schema-id format `pbj:vendor:package:category:message:version` (version = major-minor-patch).
The fields is an array of associated fields used by the schema. In the above example, the store schema contains a slug and a title.

Since we are creating a mixin schema, we set in the second line `mixin = true`.

In addition, we allow to add language specific options which will be used while generating the language output file.

#### Schema Field Types
A schema field can have one of the following types – the table shows the type specified in the `.yml` file, and the options allowed:

| Field Type | Example |
| -----------| -------------- |
| BigInt |  |
| Binary |  |
| Blob |  |
| Boolean |  |
| DateTime |  |
| Date |  |
| Decimal |  |
| Float |  |
| Identifier |  |
| Float |  |
| Int |  |
| MediumBlob |  |
| MediumInt |  |
| MediumText |  |
| Microtime |  |
| SignedBigInt |  |
| SignedInt |  |
| SignedMediumInt |  |
| SignedSmallInt |  |
| SignedTinyInt |  |
| SmallInt |  |
| String |  |
| Text |  |
| Timestamp |  |
| TimeUuid |  |
| TinyInt |  |
| UuidType |  |

#### Default Values
When a schema is parsed, if the encoded schema does not contain a particular singular element, the corresponding field in the parsed object is set to the default value for that field. These defaults are type-specific:

    - For strings, the default value is the empty string.
    - For bytes, the default value is empty bytes.
    - For bools, the default value is false.
    - For numeric types, the default value is zero.
    - For each of the other field types, the default value is null.

#### Enumerations
When you're defining a schema, you might want one of its fields to only have one of a pre-defined list of values. For example, let's say you want to add a `StoryStatus` enum field, where the values can be `PUBLISHED`, `DRAFT` or `DELETED`.

```yaml
fields:
  status:
    type: string-enum
    enum:
      DRAFT: 'draft'
      PUBLISHED: 'published'
      DELETED: 'deleted'
    default: 'draft'
    php_options:
      class_name 'Acme\Demo\Enum\StoryStatus'
```

From the above example you can see we defined the enum keys and values as well as a default value. If no default was set, the first key will be used. We also define the PHP class name that will be used when outputting to a PHP file. In this case, an output of the `draft` value will look like: `\Acme\Demo\Enum\StoryStatus::DRAFT`.

There are 2 kinds of enum types, `StringEnum` and `IntEnum`. We separated to simplified the field type and values.

> **Note:** major database for example MySQL, DynamoDB and other define enum based on type - string or int.

#### Using Message Types
You can use `Message` and `MessageRef` as field types. For example, let's say you wanted to include related messages in each Story schema:

```yaml
fields:
  related:
    type: message
    any_of: 'gdbots:pbj:mixin:related'
```

The `any_of` attribute define the message id that will be used to pull the message details.

### Full Schma

```yaml
id: 'pbj:vendor:package:category:message:1-0-0'
mixin: <bool> // optional
fields:
  name: //pattern = '/^([a-zA-Z_]{1}[a-zA-Z0-9_]+)$/'
    type: <Gdbots\Pbjc\Type\Type>
    rule: <Gdbots\Pbjc\Enum\FieldRule>
    required: <bool>
    min_length: <int>
    max_length: <int>
    pattern: <string>
    format: <Gdbots\Pbjc\Enum\Format>
    min: <int>
    max: <int>
    precision: <int>
    scale: <int>
    defualt: <mixed>
    use_type_default: <bool>
    class_name: <string>
    any_of: <string> or <array>
    overridable: <bool>
    enum:
      key1: value2
      key2: value2

    // optional: per language
    php_options:
      class_name: <string>
      // todo: add all other keys

// optional: contain list of embedded mixins
mixins:
  'mixin-id-1'
  'mixin-id-2'

// optional: per language
php_options:
  namespace: <string>
```
