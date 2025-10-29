<template>
  <Line :data="chartData" :options="chartOptions" />
</template>

<script setup>
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
} from 'chart.js';

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale
);

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
      label: 'Stock In',
      backgroundColor: 'rgba(65, 184, 131, 0.5)',
      borderColor: '#41B883',
      data: props.chartData.stockInData,
      fill: true,
      tension: 0.4,
    },
    {
      label: 'Stock Out',
      backgroundColor: 'rgba(228, 102, 81, 0.5)',
      borderColor: '#E46651',
      data: props.chartData.stockOutData,
      fill: true,
      tension: 0.4,
    },
  ],
}));

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    y: {
      beginAtZero: true,
    },
  },
};
</script>
