<template>
  <div v-bind:class="cardClass">
    <a class="bs-card-anchor" v-bind:href="href" v-bind:aria-label="cardTitle" v-bind:title="cardTitle"
       rel="nofollow noindex">
      <div class="bs-card-image" v-bind:style="image_url ? { backgroundImage: 'url(' + image_url + ')' }  : {}"></div>
      <div class="bs-card-body">
        <div class="bs-card-title">{{ title }}</div>
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
                v-bind:datatitle="primaryAction.dataTitle"
        ></action>
      </ul>
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
    process: String,
    db_key: String,
    url: String,
    image_url: String,
    edit_url: String,
    is_new: Boolean
  },
  components: {
    'action': Action
  },
  data: function () {
    const primaryActions = [];
    if ( this.edit_url ) {
      const textMsgKey = this.is_new ? 'bs-cpd-process-overview-create-action-text' : 'bs-cpd-process-overview-edit-action-text';
      const titleMsgKey = this.is_new ? 'bs-cpd-process-overview-create-action-title' : 'bs-cpd-process-overview-edit-action-title';
      primaryActions.push( {
        text: mw.message( textMsgKey ).escaped(),
        title: mw.message( titleMsgKey, this.process ).escaped(),
        href: this.edit_url,
        class: 'bs-card-edit-action',
        iconClass: 'bi-pencil-fill'
      } );
    }

    // Add the info action
    primaryActions.push( {
      text: mw.message( 'bs-cpd-process-overview-info-action-text' ).escaped(),
      title: mw.message( 'bs-cpd-process-overview-info-action-title', this.process ).escaped(),
      href: mw.util.getUrl( this.db_key, {
        action: 'info'
      } ),
      class: 'bs-card-info-action cpd-action-info',
      iconClass: 'bi-info-circle',
      dataTitle: this.db_key
    } );

    const cardClass = this.is_new ? 'bs-card new' : 'bs-card';

    return {
      cardClass,
      cardTitle: mw.message( 'bs-cpd-process-overview-card-title', this.process ).escaped(),
      primaryActions,
      href: this.url
    };
  },
}
</script>

<style lang="css">
.bs-card {
  position: relative;
  width: 325px;
  height: 450px;
  border: 1px solid #d7d7d7;
  margin: 20px 20px;
}

.bs-card.new {
  outline: var(--bs-process-overview-page-new) solid 3px;
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
  outline: var(--bs-process-overview-page-focus-visible-color) solid 3px;
}

.bs-card-image {
  width: 100%;
  height: 220px;
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center center;
  background-image: url('../../img/default-diagram.svg');
  background-origin: content-box;
  padding: 10px;
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

.bs-card-footer {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
}

.bs-card-actions {
  display: flex;
  justify-content: space-between;
  list-style: none;
  margin: 0;
  flex-wrap: wrap;
  padding: 15px 0;
}

.bs-card-actions > li {
  margin: 0;
}
</style>
