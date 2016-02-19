pbjc-php
=============

[![Build Status](https://api.travis-ci.org/gdbots/pbjc-php.svg)](https://travis-ci.org/gdbots/pbjc-php)
[![Code Climate](https://codeclimate.com/github/gdbots/pbjc-php/badges/gpa.svg)](https://codeclimate.com/github/gdbots/pbjc-php)

Compiler for converting pbj schemas into jsonschema, php, js, etc.

# Language Guide
This guide describes how to use the XML language to structure your schema file syntax and how to generate data classes files.

### Defining A Schema
First let's look at a very simple example. Let's say you want to define a **mixin** schema, with slug and name fields. Here's the `.xml` file you use to define the schema.

```xml
<schema-mapping xmlns="gdbots:pbjc:schema-mapping"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">

  <entity id="pbj:acme:blog:entity:article:1-0-0" mixin="true">
    <fields>
      <field name="slug" type="string" pattern="/^[A-Za-z0-9_\-]+$/" required="true" />
      <field name="title" type="text" required="true" />
    </fields>

    <php_options>
      <namespace>Acme\Blog\Entity</namespace>
    </php_options>
  </entity>
</schema-mapping>
```

Each schema required a few basic elements: id and fields.
The id is a unique identifier follow a basic schema-id format `pbj:vendor:package:category:message:version` (version = major-minor-patch).
The fields is an array of associated fields used by the schema. In the above example, the store schema contains a slug and a title.

Since we are creating a mixin schema, we set in the second line `mixin = true`.

In addition, we allow to add language specific options which will be used while generating the language output file.

#### Schema Field Types
A schema field can have one of the following types – the table shows the type specified in the `.xml` file, and the options allowed:

Field Type | Default| Min | Max | Max Bytes | Notes
-----------| ------ | --- | --- | --------- | -----
*BASE* | *null* | *-2147483648* | *2147483647* | *65535* |
big-int | 0 | | | |
binary | 0 | | | 255 |
blob | 0 | | | 255 |
boolean | false | | | |
date | | | | |
date-time | | | | |
decimal | 0.0 | -1 | INF | |
float | 0.0 | -1 | INF | |
geo-point | | | | |
identifier | | | | 100 |
float | | | | |
int | | 0 | 4294967295 | |
medium-blob | | | | 16777215 |
medium-int | | 0 | 16777215 | |
medium-text | | | | 16777215 |
microtime | | | | | | @see \Gdbots\Common\Microtime::create()
signed-big-int | BigNumber(0) | | | |
signed-int | | | | |
signed-medium-int | | -8388608 | 8388607 | |
signed-small-int | | -32768 | 32767 | |
signed-tiny-int | | -128 | 127 | |
small-int | | 0 | 65535 | |
string | | | | 255 |
text | | | | |
time-uuid | | | | |
timestamp | time() | | | | @see \Gdbots\Identifiers\TimeUuidIdentifier::generate()
tiny-int | | 0 | 255 | |
uuid | | | | | @see \Gdbots\Identifiers\UuidIdentifier::generate()

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
    <enum provider="gdbots:pbjx:request:request-failed-response:v1" name="reason" />
  </field>
</fields>

<enums>
  <enum name="reason">
    <option key="INVALID" value="invalid" />
    <option key="FAILED" value="failed" />
    <option key="DELETED" value="deleted" />
  </enum>
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
  <any_of>
    <curie>gdbots:pbj:mixin:request</curie_major>
  </any_of>
</field>
```

The `any_of` attribute define the message id that will be used to pull the message details.

### Full Schma Options

```xml
<schema-mapping xmlns="gdbots:pbjc:schema-mapping"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">

  <entity id="{pbj:vendor:package:category:message:major-minor-patch}" mixin="{bool}">
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
        use_type_default="{bool}"
        overridable="{bool}"
      >
        <default>{string}</default>

        <enum provider="{pbj:vendor:package:category:vmajor}" name="{string}" />

        <any_of>
          <curie>{pbj:vendor:package:category}</curie>
          <!-- ... -->
        </any_of>

        <php_options>
          <class_name>{string}</class_name>
          <default>{string}</default>
        </php_options>
      </field>
    </fields>

    <mixins>
      <curie_major>{pbj:vendor:package:category:vmajor}</curie_major>
      <!-- ... -->
    </mixins>

    <enums>
      <enum>
        <option key="{string}" value="{string}" />
        <!-- ... -->
      </enum>
      <php_options>
        <namespace>{string}</namespace>
      </php_options>
    <enums>

    <php_options>
      <namespace>{string}</namespace>
    </php_options>
  </entity>
</schema-mapping>
```

# Basic Usage

Before compiling you have to add the directory or directories where your
XML file exists:

```php
<?php

use Gdbots\Pbjc\SchemaStore;

SchemaStore::addDir('/your/schemas/path1');
SchemaStore::addDir('/your/schemas/path2');
//...
```

Once all directories are added, you can then start compiling:

```php
<?php

$compile = new Compiler();
$generator = $compile->run('php', 'vendor:package', '/put/your/output/folder');
```

> **Note:** if no output folder was provided no files will be generated.
