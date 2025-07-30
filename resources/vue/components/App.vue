<template>
  <div v-if="hasData">
    <div class="bs-process-search">
      <cdx-search-input
          v-model="searchInputValue"
          :clearable="true"
          :placeholder="searchPlaceholderLabel"
          :aria-label="searchPlaceholderLabel"
          @update:model-value="getSearchResults"
      ></cdx-search-input>
    </div>
    <div class="bs-grid">
      <grid v-bind:cards="cards"></grid>
    </div>
    <div
        id="bs-process-aria-live"
        aria-live="polite"
    >{{ ariaLiveInitial }}
    </div>
  </div>
  <div class="cpd-process-empty" v-else>
	<a href="" class="cpd-create-new-process">
		<span class="cpd-process-empty-image"></span>
		<span class="cpd-process-empty-label">{{ emptyMsg }}</span>
	</a>
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
    this.items.forEach( ( card ) => {
      card.title = mw.Title.newFromText( card.process ).getMainText();
      card.isVisible = true
    } );

    const initialSearchInputValue = '';

    return {
      searchInputValue: initialSearchInputValue,
      cards: this.items,
      hasData: this.items.length > 0,
      emptyMsg: mw.message( 'bs-cpd-process-overview-no-results-text' ).text(),
      searchPlaceholderLabel: mw.message( 'bs-cpd-process-search-placeholder' ).text(),
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
  const text = mw.message( 'bs-cpd-process-overview-aria-live-filtered-rows', count ).toString();
  $( '#bs-process-aria-live' ).html( text );
}

</script>

<style lang="css">
:root {
	--bs-process-overview-page-focus-visible-color: #3e5389;
	--bs-process-overview-page-new: #bd1d1d;
}

.bs-process-search {
	width: 50%;
	margin-left: 20px;
}

.cpd-process-empty {
	padding: 20px;
}

.cpd-process-empty-image {
	height: 180px;
	width: 180px;
	display: block;
	background-position: center;
	background-size: 100% 100%;
	background-image: url( ../../img/create-process.svg );
	background-color: rgba( 62, 83, 137, 0.1 );
	background-repeat: no-repeat;
	margin: 0 auto 40px;
	border-radius: 100%;
}

.cpd-process-empty-label {
	display: block;
	text-align: center;
	margin: 0;
	font-weight: bold;
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
