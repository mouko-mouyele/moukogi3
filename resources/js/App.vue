<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <Navbar v-if="isAuthenticated" />
    <router-view />
  </div>
</template>

<script>
import { computed } from 'vue';
import Navbar from './components/Navbar.vue';

export default {
  name: 'App',
  components: {
    Navbar
  },
  setup() {
    const isAuthenticated = computed(() => {
      return !!localStorage.getItem('token');
    });

    return {
      isAuthenticated
    };
  }
}
</script>
