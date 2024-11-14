<template>
  <div class="bs-books-search">
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
  <div class="bs-books-bookshelfs-empty" v-else>
    {{ emptyMsg }}
  </div>
  <div
      id="bs-books-aria-lve"
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
  text = mw.message( 'bs-books-overview-page-aria-live-filtered-rows', count ).toString();
  $( '#bs-books-aria-lve' ).html( text );
}

function foo() {
  console.log($( '[data-processs]' ));
}

</script>

<style lang="css">
:root {
  --bs-books-overview-page-focus-visible-color: #3E5389;
  --bs-books-overview-page-book-new: #BD1D1D;
}

.bs-books-search {
  width: 50%;
  margin-left: 20px;
}

.bs-books-bookshelfs-empty {
  padding: 20px;
}

#bs-books-aria-lve {
  height: 0;
  overflow: hidden;
}

@media ( max-width: 768px ) {
  .bs-books-search {
    width: 100%;
    margin-left: 0;
  }
}
</style>
