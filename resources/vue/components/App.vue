<template>
  <div class="bs-process-search">
    <cdx-search-input
        :clearable="true"
        :placeholder="searchPlaceholderLabel"
        :aria-label="searchPlaceholderLabel"
        @update:model-value="getSearchResults"
    ></cdx-search-input>
  </div>
  <div class="bs-grid" v-if="hasData">
    <grid v-bind:cards="cards"></grid>
  </div>
  <div class="bs-grid-empty" v-else>
    {{ emptyMsg }}
  </div>
  <div
      id="bs-process-aria-live"
      aria-live="polite"
  >{{ ariaLiveInitial }}
  </div>
</template>

<script>
const Grid = require( './Grid.vue' );
const {CdxSearchInput} = require( '@wikimedia/codex' );

module.exports = exports = {
  name: 'CpdProcessOverview',
  props: {
    items: {
      type: Array,
      default: []
    }
  },
  components: {
    grid: Grid,
    CdxSearchInput: CdxSearchInput
  },
  data: function () {
    this.items.forEach( ( card ) => card.isVisible = true );

    return {
      cards: this.items,
      hasData: this.items.length > 0,
      emptyMsg: mw.message( 'bs-cpd-process-overview-no-results' ).text(),
      searchPlaceholderLabel: mw.message( 'bs-cpd-process-overview-search-placeholder' ).text(),
      ariaLiveInitial: mw.message( 'bs-cpd-process-overview-aria-live-filtered-rows', this.items.length ).text()
    };
  },
  methods: {
    getSearchResults: function ( search ) {
      if ( !this.cards ) {
        return;
      }

      search = search.toLowerCase();
      this.cards.forEach( ( card ) => card.isVisible = !!card.title.toLowerCase().includes( search ) );

      let visibleItems = this.cards.filter( card => card.isVisible === true );
      updateAriaLiveSection( visibleItems.length );
    }
  }
};

function updateAriaLiveSection( count ) {
  text = mw.message( 'bs-cpd-process-overview-aria-live-filtered-rows', count ).toString();
  $( '#bs-process-aria-live' ).html( text );
}

</script>

<style lang="css">
:root {
  --bs-process-overview-page-focus-visible-color: #3E5389;
  --bs-process-overview-page-new: #BD1D1D;
}

.bs-process-search {
  width: 50%;
  margin-left: 20px;
}

.bs-grid-empty {
  padding: 20px;
}

#bs-process-aria-live {
  height: 0;
  overflow: hidden;
}

@media ( max-width: 768px ) {
  .bs-process-search {
    width: 100%;
    margin-left: 0;
  }
}
</style>
