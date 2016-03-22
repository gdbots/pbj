<script src="https://cdnjs.cloudflare.com/ajax/libs/json-schema-faker/0.2.15/json-schema-faker.min.js"></script>
<script src="https://raw.githubusercontent.com/BigstickCarpet/json-schema-ref-parser/master/dist/ref-parser.min.js"></script>

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
  margin-bottom: 10px;
}
</style>

<h5>JSON Schema Form</h5>
<textarea id="json-schema" placeholder="Paste your json-schema"></textarea>
<button type="button" id="btn-generate">Generate</button>

<h5>Fake Data Object</h5>
<pre id="json-fake-object"></pre>

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
    var jsonSchema = JSON.parse(document.getElementById('json-schema').value);

    $RefParser
        .dereference(jsonSchema)
        .then(function(schema) {
            document.getElementById('json-schema-object').innerHTML = jsonPrettyPrint(schema);
            document.getElementById('json-fake-object').innerHTML = jsonPrettyPrint(jsf(schema));
        })
    ;
  };
</script>
