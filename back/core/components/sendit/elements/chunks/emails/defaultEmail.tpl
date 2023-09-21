{set $fields = $fields | replace: '&quot;' : '"' | fromJSON}
{set $fieldsAliases = $fieldsAliases | replace: '&quot;' : '"' | fromJSON}
<h3>{$_pls['savedForm.form']}</h3>

{foreach $fields as $k => $v}
    {if !($k in list ['fields', 'fieldsAliases'])}
    <p><strong>{$fieldsAliases[$k] ?: $k}</strong>: {$v | join: ', '}</p>
    {/if}
{/foreach}