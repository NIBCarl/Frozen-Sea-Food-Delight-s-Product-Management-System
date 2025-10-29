import { defineStore } from 'pinia';
import axios from 'axios';

export const useReportStore = defineStore('reports', {
  state: () => ({
    reportData: [],
    summary: {},
    loading: false,
    error: null,
  }),

  actions: {
    async generateReport(reportType, params = {}) {
      this.loading = true;
      this.error = null;
      this.reportData = [];
      this.summary = {};
      
      let endpoint = '';
      switch (reportType) {
        case 'inventory':
          endpoint = '/api/v1/reports/inventory';
          break;
        case 'products':
          endpoint = '/api/v1/reports/products';
          break;
        default:
          this.error = 'Invalid report type';
          this.loading = false;
          return;
      }

      try {
        const response = await axios.get(endpoint, { params });
        this.reportData = response.data.data;
        this.summary = response.data.summary;
      } catch (error) {
        this.error = error.response?.data?.message || `Failed to generate ${reportType} report`;
        console.error(this.error);
      } finally {
        this.loading = false;
      }
    },

    async exportReport(format, reportType, params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.get(`/api/v1/reports/export/${reportType}`, {
          params: { ...params, format },
          responseType: 'blob', // Important for file downloads
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        
        let filename = 'report';
        const contentDisposition = response.headers['content-disposition'];
        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename="?(.+)"?/);
            if (filenameMatch.length === 2)
                filename = filenameMatch[1];
        }

        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to export report';
        console.error(this.error);
      } finally {
        this.loading = false;
      }
    },
  },
});
