<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">Mouvements de stock</h1>
      <button
        v-if="canEdit"
        @click="showModal = true"
        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"
      >
        Nouveau mouvement
      </button>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <select v-model="filters.type" class="border border-gray-300 rounded-md px-3 py-2">
          <option value="">Tous les types</option>
          <option value="entree">Entrée</option>
          <option value="sortie">Sortie</option>
        </select>
        <input v-model="filters.date_from" type="date" class="border border-gray-300 rounded-md px-3 py-2" />
        <input v-model="filters.date_to" type="date" class="border border-gray-300 rounded-md px-3 py-2" />
        <button @click="loadMovements" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
          Filtrer
        </button>
      </div>
    </div>

    <!-- Liste -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantité</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
            <th v-if="canEdit" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="movement in movements.data" :key="movement.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ formatDate(movement.movement_date) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ movement.product?.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="movement.type === 'entree' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-1 text-xs font-semibold rounded-full">
                {{ movement.type === 'entree' ? 'Entrée' : 'Sortie' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ movement.quantity }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ movement.user?.name }}</td>
            <td v-if="canEdit" class="px-6 py-4 whitespace-nowrap text-sm font-medium">
              <button @click="deleteMovement(movement)" class="text-red-600 hover:text-red-900">Supprimer</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold mb-4">Nouveau mouvement</h3>
        <form @submit.prevent="saveMovement">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Produit</label>
            <select v-model="form.product_id" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
              <option value="">Sélectionner un produit</option>
              <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Type</label>
            <select v-model="form.type" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
              <option value="entree">Entrée</option>
              <option value="sortie">Sortie</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Type de mouvement</label>
            <select v-model="form.motion_type" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
              <option v-if="form.type === 'entree'" value="achat">Achat</option>
              <option v-if="form.type === 'entree'" value="retour">Retour</option>
              <option v-if="form.type === 'entree'" value="correction">Correction</option>
              <option v-if="form.type === 'sortie'" value="vente">Vente</option>
              <option v-if="form.type === 'sortie'" value="perte">Perte</option>
              <option v-if="form.type === 'sortie'" value="casse">Casse</option>
              <option v-if="form.type === 'sortie'" value="expiration">Expiration</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Quantité</label>
            <input v-model.number="form.quantity" type="number" required min="1" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Date</label>
            <input v-model="form.movement_date" type="date" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Motif</label>
            <textarea v-model="form.reason" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
          </div>
          <div class="flex justify-end space-x-2">
            <button type="button" @click="closeModal" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
              Annuler
            </button>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
              Enregistrer
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

export default {
  name: 'Movements',
  setup() {
    const movements = ref({ data: [] });
    const products = ref([]);
    const showModal = ref(false);
    const filters = ref({
      type: '',
      date_from: '',
      date_to: ''
    });
    const form = ref({
      product_id: '',
      type: 'entree',
      motion_type: 'achat',
      quantity: 1,
      movement_date: new Date().toISOString().split('T')[0],
      reason: ''
    });

    const user = computed(() => {
      const userData = localStorage.getItem('user');
      return userData ? JSON.parse(userData) : null;
    });

    const canEdit = computed(() => {
      return user.value && ['admin', 'gestionnaire'].includes(user.value.role);
    });

    const loadMovements = async () => {
      try {
        const params = {};
        if (filters.value.type) params.type = filters.value.type;
        if (filters.value.date_from) params.date_from = filters.value.date_from;
        if (filters.value.date_to) params.date_to = filters.value.date_to;
        
        const response = await axios.get('/stock-movements', { params });
        movements.value = response.data;
      } catch (error) {
        console.error('Erreur lors du chargement des mouvements:', error);
      }
    };

    const loadProducts = async () => {
      try {
        const response = await axios.get('/products', { params: { per_page: 1000 } });
        products.value = response.data.data;
      } catch (error) {
        console.error('Erreur lors du chargement des produits:', error);
      }
    };

    const saveMovement = async () => {
      try {
        await axios.post('/stock-movements', form.value);
        closeModal();
        loadMovements();
      } catch (error) {
        console.error('Erreur lors de l\'enregistrement:', error);
        alert(error.response?.data?.message || 'Erreur lors de l\'enregistrement');
      }
    };

    const deleteMovement = async (movement) => {
      if (!confirm('Êtes-vous sûr de vouloir supprimer ce mouvement?')) return;
      
      try {
        await axios.delete(`/stock-movements/${movement.id}`);
        loadMovements();
      } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        alert('Erreur lors de la suppression');
      }
    };

    const closeModal = () => {
      showModal.value = false;
      form.value = {
        product_id: '',
        type: 'entree',
        motion_type: 'achat',
        quantity: 1,
        movement_date: new Date().toISOString().split('T')[0],
        reason: ''
      };
    };

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR');
    };

    onMounted(() => {
      loadMovements();
      loadProducts();
    });

    return {
      movements,
      products,
      showModal,
      filters,
      form,
      canEdit,
      loadMovements,
      saveMovement,
      deleteMovement,
      closeModal,
      formatDate
    };
  }
}
</script>
