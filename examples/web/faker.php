<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';

use Gdbots\Pbj\Serializer\JsonSerializer;

// verifying json with PHP message
if (isset($_REQUEST['verify'])) {
    $json = file_get_contents('php://input');

    try {
        $serializer = new JsonSerializer();
        $message = $serializer->deserialize($json);

        var_dump($message);
    } catch (\Exception $e) {
        echo $e->getMessage();
    }

    exit(1);
}
?>

<script src="js/json-schema-faker.min.js"></script>
<script src="js/ref-parser.min.js"></script>

<style>
h5 {
    margin-bottom: 0;
    padding-bottom: 0;
}
pre {
  background-color: ghostwhite;
  border: 1px solid silver;
  margin: 0;
  padding: 10px 20px;
  overflow: auto;
  max-height: 250px;
}
.json-key {
  color: brown;
}
.json-value {
  color: navy;
}
.json-string {
  color: olive;
}
textarea {
  width: 100%;
  height: 250px;
}
button {
  margin: 10px 0 10px;
}
</style>

<h5>JSON Schema Form</h5>
<textarea id="json-schema" placeholder="Paste your json-schema"></textarea>
<button type="button" id="btn-generate">Generate</button>

<h5>Fake Data Object</h5>
<pre id="json-fake-object"></pre>
<button type="button" id="btn-verify">Verify PHP Message</button>
<pre id="json-fake-object-validation"></pre>

<h5>Parsed Schema Object</h5>
<pre id="json-schema-object"></pre>

<script>
  /**
   * @param {String} match
   * @param {String} pIndent
   * @param {String} pKey
   * @param {String} pVal
   * @param {String} pEnd
   */
  function jsonLineReplacer(match, pIndent, pKey, pVal, pEnd) {
    var key = '<span class=json-key>';
    var val = '<span class=json-value>';
    var str = '<span class=json-string>';
    var r = pIndent || '';
    if (pKey)
      r = r + key + pKey.replace(/[": ]/g, '') + '</span>: ';
    if (pVal)
      r = r + (pVal[0] == '"' ? str : val) + pVal + '</span>';
    return r + (pEnd || '');
  }

  /**
   * @param {Object} obj
   */
  function jsonPrettyPrint(obj) {
    var jsonLine = /^( *)("[\w]+": )?("[^"]*"|[\w.+-]*)?([,[{])?$/mg;
    return JSON.stringify(obj, null, 2)
      .replace(/&/g, '&amp;').replace(/\\"/g, '&quot;')
      .replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(jsonLine, jsonLineReplacer);
  }

  /**
   * Handle generator button onClick
   */
  document.getElementById('btn-generate').onclick = function() {
    try {
      var jsonSchema = JSON.parse(document.getElementById('json-schema').value);
    } catch (e) {
      document.getElementById('json-fake-object').innerHTML = e;
      return;
    }

    $RefParser
        .dereference(jsonSchema)
        .then(function(schema) {
            // force _schame value to schemaId
            var fixSchemaValue = function(obj) {
                Object.keys(obj).forEach(function(key) {
                    if ('object' === typeof obj[key]) {
                        fixSchemaValue(obj[key]);
                    }
                    if ('_schema' === key) {
                        obj[key].pattern = '^' + obj[key].default + '$';
                    }
                });
            };
            fixSchemaValue(schema);

            document.getElementById('json-schema-object').innerHTML = jsonPrettyPrint(schema);
            document.getElementById('json-fake-object').innerHTML = jsonPrettyPrint(jsf(schema));
        })
    ;

    document.getElementById('json-fake-object-validation').innerHTML = '';
  };

  /**
   * Handle generator button onClick
   */
  document.getElementById('btn-verify').onclick = function() {
    try {
      var jsonObj = eval('(' + document.getElementById('json-fake-object').innerHTML.replace(/<\/?[^>]+(>|$)/g, '') + ')');
    } catch (e) {
      document.getElementById('json-fake-object-validation').innerHTML = e;
      return;
    }

    var xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function() {
      document.getElementById('json-fake-object-validation').innerHTML = xhr.responseText;
    };

    xhr.open('post', 'faker.php?verify');
    xhr.send(JSON.stringify(jsonObj));
  }
</script>
