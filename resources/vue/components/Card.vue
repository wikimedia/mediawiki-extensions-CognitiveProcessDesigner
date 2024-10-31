<template>
  <div v-bind:class="cardClass">
    <a class="bs-card-anchor" v-bind:href="href" v-bind:aria-label="cardTitle" v-bind:title="cardTitle"
       rel="nofollow noindex">
      <div class="bs-card-image" v-bind:style="{ backgroundImage: 'url(' + image_url + ')' }"></div>
      <div class="bs-card-body">
        <div class="bs-card-title">{{ title }}</div>
        <div class="bs-card-subtitle">Verwendet in</div>
        <ul>
          <li v-for="usedIn in used_in_urls"><div v-html="usedIn"></div></li>
        </ul>
      </div>
    </a>
    <div class="bs-card-footer">
      <ul class="bs-card-actions">
        <action v-for="primaryAction in primaryActions"
                v-bind:text="primaryAction.text"
                v-bind:title="primaryAction.title"
                v-bind:href="primaryAction.href"
                v-bind:actionclass="primaryAction.class"
                v-bind:iconclass="primaryAction.iconClass"
        ></action>
    </div>
  </div>
</template>

<script>
const Action = require( './Action.vue' );
const {toRaw} = Vue;

module.exports = {
  name: 'Card',
  props: {
    title: String,
    url: String,
    image_url: String,
    edit_url: String,
    used_in_urls: Array
  },
  components: {
    'action': Action
  },
  data: function () {
    const primaryActions = [];
    console.log(this.used_in_urls);
    if ( this.edit_url ) {
      primaryActions.push( {
        text: mw.message( 'bs-cpd-process-overview-edit-action-text' ).escaped(),
        title: mw.message( 'bs-cpd-process-overview-edit-action-title', this.title ).escaped(),
        href: this.edit_url,
        class: 'bs-card-edit-action',
        iconClass: 'icon-edit'
      } );
    }

    return {
      cardClass: "bs-card",
      cardTitle: mw.message( 'bs-cpd-process-overview-card-title', this.title ).escaped(),
      primaryActions: primaryActions,
      href: this.url
    };
  },
}
</script>

<style lang="css">
.bs-card {
  position: relative;
  width: 320px;
  height: 450px;
  border: 1px solid #d7d7d7;
  margin: 20px 26px;
}

.bs-card.new {
  outline: var(--bs-books-overview-page-book-new) solid 3px;
}

.bs-card.new .bs-card-anchor {
  pointer-events: none;
  cursor: default;
}

.bs-card-anchor {
  display: block;
  width: 100%;
  height: calc(100% - 47px);
  text-decoration: none !important;
}

.bs-card:focus-within {
  outline: var(--bs-books-overview-page-focus-visible-color) solid 3px;
}

.bs-card-image {
  width: 100%;
  height: 220px;
  background-size: cover;
  background-repeat: no-repeat;
}

.bs-card-body {
  height: 163px;
  text-align: center;
  padding: 40px 10px;
  overflow: hidden;
  color: black !important;
}

.bs-card-title {
  width: 100%;
  font-weight: bold;
  font-size: 1.4em;
  margin-bottom: 5px;
}

.bs-card-subtitle {
  width: 100%;
  font-size: 1.1em;
}

.bs-card-footer {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 47px;
  width: 100%;
  padding: 10px 10px 0 10px;
}

.bs-card-actions {
  display: flex;
  justify-content: space-between;
  list-style: none;
  margin: 0;
}

.bs-card-actions > li {
  margin: 0;
}
</style>
