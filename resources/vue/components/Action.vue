<template>
  <li><a
      v-bind:href="href"
      v-bind:class="actionclass"
      v-bind:title="title"
      v-bind:data-title="datatitle"
      rel="nofollow noindex"><i
      v-bind:class="iconclass"></i><span>{{ text }}</span></a></li>
</template>

<script>
module.exports = {
  name: 'Action',
  props: {
    text: String,
    title: String,
    href: String,
    actionclass: String,
    iconclass: String,
    datatitle: String,
  }
}
</script>

<style lang="css">
.bs-card-actions > li > a {
  display: inline-block;
  height: 100%;
  padding: 0 15px;
  color: #666 !important;
}

.bs-card-actions > li > a:focus-visible {
  outline: var(--bs-process-overview-page-focus-visible-color) solid 1px;
}

.bs-card-actions > li > a i {
  display: inline-block;
  margin-right: 10px;
}
</style>
