{set $fields = $fields | replace: '&quot;' : '"' | fromJSON}
{set $fieldsAliases = $fieldsAliases | replace: '&quot;' : '"' | fromJSON}
{set $questions = $fields['questions'] | fromJSON}
{set $answers = $fields['answers'] | fromJSON}
<h3>{$_pls['savedForm.form']}</h3>

{if $questions && ($questions | count)}
    {foreach $questions as $i => $question}
        <p><strong>{$question}</strong>: {$answers[$i] | join: ', '}</p>
    {/foreach}
{else}
    {foreach $fields as $k => $v}
        {if !($k in list ['fields', 'fieldsAliases'])}
            <p><strong>{$fieldsAliases[$k] ?: $k}</strong>: {$v | join: ', '}</p>
        {/if}
    {/foreach}
{/if}





