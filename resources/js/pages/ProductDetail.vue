<template>
  <div class="container mx-auto px-4 py-8" v-if="product">
    <div class="mb-6">
      <router-link to="/products" class="text-indigo-600 hover:text-indigo-900">← Retour aux produits</router-link>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h1 class="text-3xl font-bold mb-4">{{ product.name }}</h1>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <p class="text-gray-600">Description</p>
          <p class="font-medium">{{ product.description || '-' }}</p>
        </div>
        <div>
          <p class="text-gray-600">Code-barres</p>
          <p class="font-medium">{{ product.barcode || '-' }}</p>
        </div>
        <div>
          <p class="text-gray-600">Stock actuel</p>
          <p class="font-medium text-2xl" :class="getStockClass()">{{ product.current_stock }}</p>
        </div>
        <div>
          <p class="text-gray-600">Prix</p>
          <p class="font-medium">{{ formatCurrency(product.price) }}</p>
        </div>
      </div>
    </div>

    <!-- Prédiction -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h2 class="text-xl font-bold mb-4">Prédiction de stock</h2>
      <div class="mb-4">
        <select v-model="predictionDays" @change="loadPrediction" class="border border-gray-300 rounded-md px-3 py-2">
          <option :value="7">7 jours</option>
          <option :value="30">30 jours</option>
          <option :value="90">3 mois</option>
        </select>
      </div>
      <div v-if="prediction" class="space-y-2">
        <p><strong>Stock prévu:</strong> {{ prediction.predicted_stock }}</p>
        <p><strong>Consommation prévue:</strong> {{ prediction.predicted_consumption }}</p>
        <p><strong>Méthode:</strong> {{ prediction.method }}</p>
        <div v-if="prediction.risk_of_rupture" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          <strong>⚠️ Risque de rupture!</strong> Commande recommandée: {{ prediction.recommended_order }} unités
        </div>
        <div v-else class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
          ✓ Aucun risque de rupture
        </div>
      </div>
    </div>

    <!-- Mouvements récents -->
    <div class="bg-white rounded-lg shadow p-6">
      <h2 class="text-xl font-bold mb-4">Mouvements récents</h2>
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantité</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Motif</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="movement in product.stock_movements" :key="movement.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ formatDate(movement.movement_date) }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="movement.type === 'entree' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-1 text-xs font-semibold rounded-full">
                {{ movement.type === 'entree' ? 'Entrée' : 'Sortie' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ movement.quantity }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ movement.reason || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

export default {
  name: 'ProductDetail',
  setup() {
    const route = useRoute();
    const product = ref(null);
    const prediction = ref(null);
    const predictionDays = ref(30);

    const loadProduct = async () => {
      try {
        const response = await axios.get(`/products/${route.params.id}`);
        product.value = response.data;
        loadPrediction();
      } catch (error) {
        console.error('Erreur lors du chargement du produit:', error);
      }
    };

    const loadPrediction = async () => {
      try {
        const response = await axios.get(`/products/${route.params.id}/predict`, {
          params: { days: predictionDays.value }
        });
        prediction.value = response.data;
      } catch (error) {
        console.error('Erreur lors du chargement de la prédiction:', error);
      }
    };

    const getStockClass = () => {
      if (!product.value) return '';
      if (product.value.current_stock <= product.value.stock_minimum) return 'text-red-600';
      if (product.value.current_stock <= product.value.stock_optimal) return 'text-yellow-600';
      return 'text-green-600';
    };

    const formatCurrency = (value) => {
      return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
    };

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR');
    };

    onMounted(() => {
      loadProduct();
    });

    return {
      product,
      prediction,
      predictionDays,
      loadPrediction,
      getStockClass,
      formatCurrency,
      formatDate
    };
  }
}
</script>
