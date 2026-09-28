{* CSS/JS des Plugins, einmal pro Seite bzw. pro nachgeladenem Filter-Dialog, mit Plugin-Version als Cache-Buster
   (siehe Bootstrap::assignAssets()). Die Skripte sind idempotent (window.mrfRangeFilter). *}
{if isset($mrfAssets) && !isset($mrfAssetsPrinted)}
    <link rel="stylesheet" href="{$mrfAssets.css|escape:'html'}">
    {if $mrfAssets.js !== ''}<script defer src="{$mrfAssets.js|escape:'html'}"></script>{/if}
    {assign var=mrfAssetsPrinted value=true scope='global'}
{/if}
