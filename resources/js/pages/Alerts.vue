<template>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Alertes</h1>

    <div class="space-y-4">
      <div v-for="alert in alerts.data" :key="alert.id" class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <h3 class="text-lg font-bold">{{ alert.title }}</h3>
            <p class="text-gray-600 mt-2">{{ alert.message }}</p>
            <p v-if="alert.product" class="text-sm text-gray-500 mt-1">Produit: {{ alert.product.name }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ formatDate(alert.created_at) }}</p>
          </div>
          <div class="flex space-x-2">
            <button
              @click="resolveAlert(alert)"
              class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm"
            >
              Résoudre
            </button>
            <button
              @click="dismissAlert(alert)"
              class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm"
            >
              Ignorer
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';

export default {
  name: 'Alerts',
  setup() {
    const alerts = ref({ data: [] });

    const loadAlerts = async () => {
      try {
        const response = await axios.get('/alerts', {
          params: { status: 'active' }
        });
        alerts.value = response.data;
      } catch (error) {
        console.error('Erreur lors du chargement des alertes:', error);
      }
    };

    const resolveAlert = async (alert) => {
      try {
        await axios.post(`/alerts/${alert.id}/resolve`);
        loadAlerts();
      } catch (error) {
        console.error('Erreur lors de la résolution:', error);
      }
    };

    const dismissAlert = async (alert) => {
      try {
        await axios.post(`/alerts/${alert.id}/dismiss`);
        loadAlerts();
      } catch (error) {
        console.error('Erreur lors de l\'ignorance:', error);
      }
    };

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR');
    };

    onMounted(() => {
      loadAlerts();
    });

    return {
      alerts,
      loadAlerts,
      resolveAlert,
      dismissAlert,
      formatDate
    };
  }
}
</script>
