<template>
  <Pie :data="chartData" :options="chartOptions" />
</template>

<script setup>
import { computed } from 'vue';
import { Pie } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  CategoryScale,
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale);

const props = defineProps({
  chartData: {
    type: Object,
    required: true,
  },
});

const chartData = computed(() => ({
  labels: props.chartData.labels,
  datasets: [
    {
      backgroundColor: [
        '#41B883',
        '#E46651',
        '#00D8FF',
        '#DD1B16',
        '#FFC107',
        '#673AB7',
        '#03A9F4',
        '#8BC34A',
      ],
      data: props.chartData.data,
    },
  ],
}));

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
};
</script>
