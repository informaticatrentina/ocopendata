{foreach $fields as $field}
{if $field.description|ne('')}
 * {$field.name} ({$field.short_name}): {$field.description}
{if $field.attribute_type|ne('')}

     * Tipo di dato: {$field.attribute_type}
{/if}
{if $field.attribute_format|ne('')}

     * Formato del dato: {$field.attribute_format}
{/if}
{if $field.allowed_values|ne('')}

     * Valori ammessi: {$field.allowed_values}
{/if}


{/if}
{/foreach}