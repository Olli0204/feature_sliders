{* Schieberegler mit zwei Eingabefeldern; Daten kommen fertig aus RangeFilter::getSliderData(). *}
<div class="mrf-slider js-mrf-slider"
     data-mrf-param="{$mrfSlider.param|escape:'html'}"
     data-mrf-url="{$mrfSlider.baseUrl|escape:'html'}"
     data-mrf-min="{$mrfSlider.min|escape:'html'}"
     data-mrf-max="{$mrfSlider.max|escape:'html'}"
     data-mrf-from="{$mrfSlider.from|escape:'html'}"
     data-mrf-to="{$mrfSlider.to|escape:'html'}"
     data-mrf-step="{$mrfSlider.step|escape:'html'}">
    <div class="mrf-inputs">
        <div class="input-group mrf-input-group">
            <input type="text" inputmode="decimal" autocomplete="off"
                   class="form-control mrf-input js-mrf-from"
                   value="{$mrfSlider.from|escape:'html'}"
                   aria-label="{$mrfSlider.labels.from|escape:'html'}">
            {if $mrfSlider.unit !== ''}
                <div class="input-group-append">
                    <span class="input-group-text">{$mrfSlider.unit|escape:'html'}</span>
                </div>
            {/if}
        </div>
        <div class="input-group mrf-input-group">
            <input type="text" inputmode="decimal" autocomplete="off"
                   class="form-control mrf-input js-mrf-to"
                   value="{$mrfSlider.to|escape:'html'}"
                   aria-label="{$mrfSlider.labels.to|escape:'html'}">
            {if $mrfSlider.unit !== ''}
                <div class="input-group-append">
                    <span class="input-group-text">{$mrfSlider.unit|escape:'html'}</span>
                </div>
            {/if}
        </div>
    </div>
    <div class="mrf-track price-range-slide js-mrf-track"></div>
    {if $mrfSlider.active}
        <a class="mrf-reset js-mrf-reset" href="{$mrfSlider.baseUrl|escape:'html'}" rel="nofollow">
            <span class="fa fa-times"></span> {$mrfSlider.labels.reset|escape:'html'}
        </a>
    {/if}
</div>
