pbjc-php
=============

[![Build Status](https://api.travis-ci.org/gdbots/pbjc-php.svg)](https://travis-ci.org/gdbots/pbjc-php)
[![Code Climate](https://codeclimate.com/github/gdbots/pbjc-php/badges/gpa.svg)](https://codeclimate.com/github/gdbots/pbjc-php)

Compiler for converting pbj schemas into jsonschema, php, js, etc.

# Language Guide
This guide describes how to use the XML language to structure your schema file syntax and how to generate data classes files.

### References
Let's start by defining each of the elements and key options used across the compiler.

- **Schema:** The purpose of a Schema is to define a pbj message, with the fields and related mixins (other schemas used to extend the schema capability).

- **Enum:** An Enum is a collection of key-value, used in schema fields (see Enumerations below).

- **SchemaId:** A Schema fully qualified name (id).

  - Schema Id Format: `pbj:vendor:package:category:message:version`
  - Message Curie Format: `vendor:package:category:message`
  - Message Curie With Major Version Format: `vendor:package:category:message:v#`

### Defining A Schema
First let's look at a very simple example. Let's say you want to define a **mixin** schema, with slug and name fields. Here's the `.xml` file you use to define the schema.

```xml
<?xml version="1.0" encoding="UTF-8" ?>

<pbj-schema xmlns="http://gdbots.io/pbj/xsd"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://gdbots.io/pbj/xsd http://gdbots.io/pbj/xsd/schema.xsd">

  <schema id="pbj:acme:blog:entity:article:1-0-0" mixin="true">
    <fields>
      <field name="slug" type="string" pattern="/^[A-Za-z0-9_\-]+$/" required="true" />
      <field name="title" type="text" required="true" />
    </fields>

    <php-options>
      <namespace>Acme\Blog\Entity</namespace>
    </php-options>
  </schema>
</pbj-schema>
```

Each schema required a few basic elements: id and fields.
The id is a unique identifier follow a basic schema-id format `pbj:vendor:package:category:message:version` (version = major-minor-patch).
The fields is an array of associated fields used by the schema. In the above example, the store schema contains a slug and a title.

Since we are creating a mixin schema, we set in the second line `mixin = true`.

In addition, we allow to add language specific options which will be used while generating the language output file.

#### Schema Field Types
The following list contains all available field types:

    - big-int
    - binary
    - blob
    - boolean
    - date
    - date-time
    - decimal
    - float
    - geo-point
    - identifier
    - float
    - int
    - medium-blob
    - medium-int
    - medium-text
    - microtime
    - signed-big-int
    - signed-int
    - signed-medium-int
    - signed-small-int
    - signed-tiny-int
    - small-int
    - string
    - text
    - time-uuid
    - timestamp
    - tiny-int
    - uuid

#### Default Values
When a schema is parsed, if the encoded schema does not contain a particular singular element, the corresponding field in the parsed object is set to the default value for that field. These defaults are type-specific:

    - For strings, the default value is the empty string.
    - For bytes, the default value is empty bytes.
    - For bools, the default value is false.
    - For numeric types, the default value is zero.
    - For each of the other field types, the default value is null.

#### Enumerations
When you're defining a schema, you might want one of its fields to only have one of a pre-defined list of values. For example, let's say you want to add a `Reason` enum field, where the values can be `INVALID`, `FAILED` or `DELETED`.

```xml
<fields>
  <field name="failure_reason" type="string-enum">
    <default>invalid</default>
    <enum id="acme:blog:publish-status" />
  </field>
</fields>
```

The define the enum in `enums.xml`:

```xml
<enums namespace="acme:blog">
  <enum name="publish-status" type="string">
    <option key="PUBLISHED" value="published" />
    <option key="DRAFT" value="draft" />
    <option key="PENDING" value="pending" />
    <option key="EXPIRED" value="expired" />
    <option key="DELETED" value="deleted" />
  </enum>

  <php-options>
    <namespace>Acme\Schemas\Blog\Enum</namespace>
  </php-options>
<enums>
```

From the above example you can see we defined the enum keys and values for a specific schema and called it directly from the field.

> **Note:** We can also define the PHP namespace where the enum class will be generated to.

There are 2 kinds of enum types, `StringEnum` and `IntEnum`. We separated to simplified the field type and values.

> **Note:** major database for example MySQL, DynamoDB and other define enum based on type - string or int.

#### Using Message Types
You can use `Message` and `MessageRef` as field types. For example, let's say you wanted to include related messages in each Story schema:

```xml
<field name="failed_request" type="message">
  <any-of>
    <curie>gdbots:pbjx:mixin:request</curie>
  </any-of>
</field>
```

The `any-of` attribute define the message id that will be used to pull the message details.

### Full Schma Options

```xml
<?xml version="1.0" encoding="UTF-8" ?>

<pbj-schema xmlns="http://gdbots.io/pbj/xsd"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://gdbots.io/pbj/xsd http://gdbots.io/pbj/xsd/schema.xsd">

  <schema
    id="{pbj:vendor:package:category:message:major-minor-patch}"
    mixin="{bool}"
    extends="{pbj:vendor:package:category:vmajor}"
  >
    <fields>
      <field
        name="{/^([a-zA-Z_]{1}[a-zA-Z0-9_]+)$/}"
        type="{\Gdbots\Pbjc\Type\Type}"
        required="{bool}"
        min="{int}"
        max="{int}"
        precision="{int}"
        scale="{int}"
        rule="{\Gdbots\Pbjc\Enum\FieldRule}"
        pattern="{string}"
        format="{Gdbots\Pbjc\Enum\Format}"
        use-type-default="{bool}"
        overridable="{bool}"
      >
        <default>{string}</default>

        <enum id="{vendor:package:enum}" />

        <any-of>
          <curie>{pbj:vendor:package:category}</curie>
          <!-- ... -->
        </any-of>

        <php-options>
          <classname>{string}</classname>
          <default>{string}</default>
        </php-options>
      </field>
    </fields>

    <mixins>
      <curie-major>{pbj:vendor:package:category:vmajor}</curie-major>
      <!-- ... -->
    </mixins>

    <php_options>
      <namespace>{string}</namespace>
    </php-options>
  </schema>
</pbj-schema>
```

```xml
<?xml version="1.0" encoding="UTF-8" ?>

<pbj-enums xmlns="http://gdbots.io/pbj/xsd"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://gdbots.io/pbj/xsd http://gdbots.io/pbj/xsd/enums.xsd">

  <enums namespace="{vendor:package}">
    <enum name="{string}" type="int|string">
      <option key="{string}" value="{string}" />
      <!-- ... -->
    </enum>

    <php-options>
      <namespace>{string}</namespace>
    </php-options>
  <enums>
</enums-mapping>
```

> **Note:** For each `php-options` you can also add dynamic tags. For example:

```xml
<php-options>
  <namespace>Acme\Blog\Entity</namespace>
  <insertion-points>
    <imports>
        <![CDATA[
use Gdbots\Pbj\MessageRef;
        ]]>
    </imports>
    <methods>
        <![CDATA[
/**
 * @param string $tag
 * @return MessageRef
 */
public function generateMessageRef($tag = null)
{
    return new MessageRef(static::schema()->getCurie(), $this->get('command_id'), $tag);
}
        ]]>
    </methods>
  </insertion-points>
</php-options>
```

# Basic Usage

```sh
pbjc --language[=LANGUAGE] --config[=CONFIG]
```

Option | Notes
------ | -----
-l or --language[=LANGUAGE] | The generated language [default: "php"]
-c or --config[=CONFIG] | The pbjc config yaml file

Define compile settings in `pbjc.yml` file:

```yaml
namespaces:
  - <vendor1>:<package1>
  - <vendor2>:<package2>

languages:
  php:
    output: <div>
    manifest: <dir>/<filename>
```

> **Note:** by default the compiler searches for `pbjc.yml` in the root folder.
