<li>
    {if $available && $uri}<a href="{$uri}">{$name}</a>{else}{$name}{/if}
    {if $options}<small>({foreach $options as $option => $value}{$option}: {$value | join: ', '}{if !$value@last}; {/if}{/foreach})</small>{/if}
    — {$count} × {$price_formatted} = {$cost_formatted}
</li>
