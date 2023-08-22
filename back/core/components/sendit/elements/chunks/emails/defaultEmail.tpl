{set $fields = $fields | replace: '&quot;' : '"' | fromJSON}
{set $fieldsAliases = $fieldsAliases | replace: '&quot;' : '"' | fromJSON}

{foreach $fields as $k => $v}
    {if !($k in list ['fields', 'fieldsAliases'])}
    <p><strong>{$fieldsAliases ? $fieldsAliases[$k] : $k}</strong>: {$v | join: ', '}</p>
    {/if}
{/foreach}