<template>
  <div class="bs-cards-container">
    <card v-for="card in cards"
          v-show="card.isVisible"
          v-bind:title="card.title"
          v-bind:process="card.process"
          v-bind:db_key="card.db_key"
          v-bind:url="card.url"
          v-bind:edit_url="card.edit_url"
          v-bind:image_url="card.image_url"
          v-bind:is_new="card.is_new"
    ></card>
  </div>
</template>

<script>
var Card = require( './Card.vue' );

module.exports = exports = {
  name: 'Grid',
  props: {
    cards: Array
  },
  components: {
    'card': Card
  },
  data: function () {
    return {
      cards: this.cards
    };
  },
};
</script>

<style lang="css">
.bs-process-overview > h2 {
	padding: 0 26px;
}

.bs-process-overview > h2:focus-visible {
	outline: 1px solid var( --bs-process-overview-page-focus-visible-color );
}

.bs-cards-container {
	display: flex;
	justify-content: start;
	flex-wrap: wrap;
}

@media ( max-width: 768px ) {
	.bs-process-overview > h2 {
		padding: 0;
	}

	.bs-cards-container {
		justify-content: center;
	}
}
</style>
